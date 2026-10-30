<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Promo;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\BookingException;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Services\ReservationService;
use App\Support\OpHours;
use App\Support\Phone;
use App\Support\QuotePresenter;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ReservationController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    public function create(Request $request, PromoService $promos)
    {
        $types = RoomType::active()->with('activePackages')->get()
            ->filter(fn ($t) => $this->availability->units($t->id)->isNotEmpty())->values();
        $today = OpHours::today();
        $max = $this->maxDate();

        // Default date: today, or tomorrow once the last slot of today has started.
        $date = OpHours::isPast($today, OpHours::close() - 1) ? CarbonImmutable::parse($today)->addDay()->toDateString() : $today;
        if (($q = $request->query('tanggal')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $q) && $q >= $today && $q <= $max) {
            $date = $q;
        }
        $date = old('date', $date);

        $type = $types->firstWhere('id', (int) old('room_type_id', $request->query('tipe')))
            ?? $types->firstWhere('slug', $request->query('tipe'))
            ?? $types->first();
        $pkg = $type?->activePackages->firstWhere('id', (int) old('package_id', $request->query('paket')));
        $mode = $pkg ? (string) $pkg->id : ($type?->rentable ? 'sewa' : (string) $type?->activePackages->first()?->id);

        return view('site.reservation', [
            'types' => $types,
            'today' => $today,
            'maxDate' => $max,
            'config' => [
                'state' => [
                    'date' => $date,
                    'typeId' => $type?->id,
                    'mode' => $mode,
                    'hour' => old('start_hour', $request->query('jam')) !== null ? (int) old('start_hour', $request->query('jam')) : null,
                    'hours' => (int) old('hours', 2),
                    'lc' => (bool) old('lc', false),
                ],
                'types' => $types->map(fn (RoomType $t) => [
                    'id' => $t->id, 'name' => $t->name, 'rentable' => $t->rentable, 'cap' => $t->publicCap(), 'maxPax' => $t->maxPax(),
                    'rateDay' => $t->rate_day, 'rateNight' => $t->rate_night,
                    'packages' => $t->activePackages->map(fn (Package $p) => [
                        'id' => $p->id, 'name' => $p->name, 'brand' => $p->brand, 'price' => rp($p->price), 'hours' => $p->hours,
                        'start_hour' => $p->start_hour, 'start_label' => $p->start_hour !== null ? hl($p->start_hour) : null, 'items' => $p->items ?? [],
                    ])->values(),
                ])->values(),
                'days' => collect(range(0, 6))->map(function ($i) use ($today) {
                    $d = CarbonImmutable::parse($today)->addDays($i)->locale('id');

                    return ['date' => $d->toDateString(), 'top' => $i === 0 ? 'Hari ini' : ($i === 1 ? 'Besok' : $d->translatedFormat('D')), 'd' => $d->day, 'mon' => $d->translatedFormat('M')];
                }),
                'maxHours' => (int) config('dmk.max_rent_hours'),
                'urls' => ['availability' => route('reservasi.ketersediaan'), 'price' => route('reservasi.harga')],
                'error' => session('errors')?->first('booking') ?? '',
            ],
            'roomCards' => $types,
            'promoSvc' => $promos,
        ]);
    }

    /** JSON grid + start options for one type/mode (SRS §6.1). */
    public function availability(Request $request, PromoService $promos)
    {
        $date = $this->clampDate($request->query('date'));
        $type = RoomType::where('active', true)->find($request->integer('tipe'));
        $pkg = $type && $request->query('mode') !== 'sewa' ? $type->activePackages()->find($request->integer('mode')) : null;

        // Guests only see whether a slot is bookable, never how many units are left or exist.
        $grid = collect($this->availability->grid($date, $promos, $pkg))->map(fn ($row) => [
            'id' => $row['type']->id,
            'name' => $row['type']->name,
            'sub' => $row['type']->rentable ? rpk($row['type']->rate_day).'–'.rpk($row['type']->rate_night).'/jam' : 'paket',
            'cells' => array_map(fn ($c) => [
                'hour' => $c['hour'], 'label' => $c['label'], 'early' => $c['early'], 'past' => $c['past'],
                'open' => $c['free'] > 0, 'promo' => $c['promo'],
            ], $row['cells']),
        ]);

        $starts = [];
        if ($type) {
            foreach (OpHours::hours() as $h) {
                $ok = ($pkg || $type->rentable) && $this->availability->startOk($type, $pkg, $date, $h);
                $starts[] = [
                    'hour' => $h, 'label' => hl($h), 'past' => OpHours::isPast($date, $h), 'ok' => $ok,
                    'max' => $ok ? ($pkg ? $pkg->hours : $this->availability->maxDuration($type->id, $date, $h)) : 0,
                ];
            }
        }

        // Promos running on the chosen date, listed under the grid.
        $labels = $promos->targetLabels();
        $running = $promos->active()->filter(fn (Promo $p) => $p->ds() <= $date && $date <= $p->de())
            ->sortBy(fn (Promo $p) => $p->de())->values()
            ->map(function (Promo $p) use ($labels) {
                [$big, $unit] = $p->figure();

                return [
                    'id' => $p->id, 'name' => $p->name, 'big' => $big, 'unit' => $unit, 'state' => $p->state(), 'x' => $p->kind === 'bayar_x' ? $p->x : null,
                    'text' => $p->description ?: $p->ruleText(), 'period' => $p->periodLabel(), 'for' => $p->targetSummary($labels), 'targets' => $p->targets(),
                ];
            });

        return response()->json(['date' => $date, 'label' => tgl($date, 'day'), 'hours' => array_map('hl', OpHours::hours()), 'grid' => $grid, 'starts' => $starts, 'promos' => $running]);
    }

    /** Live price, computed by the same service that stores the reservation (T-28). */
    public function price(Request $request, PricingService $pricing)
    {
        $in = $request->validate([
            'room_type_id' => ['required', 'integer'], 'package_id' => ['nullable', 'integer'], 'date' => ['required', 'date_format:Y-m-d'],
            'start_hour' => ['required', 'integer', 'between:0,40'], 'hours' => ['required', 'integer', 'between:1,24'], 'lc' => ['boolean'],
        ]);
        $type = RoomType::findOrFail($in['room_type_id']);
        $pkg = ! empty($in['package_id']) ? Package::findOrFail($in['package_id']) : null;
        if ($err = $this->availability->ruleError($type, $pkg, $in['date'], $in['start_hour'], $in['hours'])) {
            return response()->json(['message' => $err], 422);
        }
        $q = $pricing->calculate($type, $pkg, $in['date'], $in['start_hour'], $in['hours'], (bool) ($in['lc'] ?? false));

        return response()->json(QuotePresenter::forQuote($q, $in['date'], $in['start_hour']));
    }

    public function store(Request $request, ReservationService $service)
    {
        $type = RoomType::find($request->integer('room_type_id'));
        $data = $request->validate([
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_hour' => ['required', 'integer'],
            'hours' => ['required', 'integer', 'between:1,'.config('dmk.max_rent_hours')],
            'lc' => ['nullable', 'boolean'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', function ($attr, $value, $fail) {
                if (! Phone::valid($value)) {
                    $fail('Isi nomor WhatsApp aktif, contoh 081234567890.');
                }
            }],
            'pax' => ['required', 'integer', 'min:1', 'max:'.($type?->maxPax() ?? 30)],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Isi nomor WhatsApp aktif, contoh 081234567890.',
            'pax.max' => 'Maksimal :max orang untuk '.($type?->name ?? 'ruangan ini').'.',
            'pax.*' => 'Isi jumlah orang yang benar.',
            'start_hour.required' => 'Pilih jam mulai.',
        ]);

        // Anti-spam: max N reservations per hour from one IP (FR-RES-12).
        $key = 'reserve:'.$request->ip();
        $limit = (int) config('dmk.rate_limit.reservations_per_hour');
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $wait = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return back()->withInput()->withErrors(['booking' => "Terlalu banyak reservasi dari jaringan ini. Coba lagi sekitar {$wait} menit lagi."]);
        }

        try {
            $r = $service->create([...$data, 'lc' => $request->boolean('lc'), 'package_id' => $data['package_id'] ?? null], 'online', null, $request->ip());
        } catch (BookingException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }
        RateLimiter::hit($key, 3600);
        $request->session()->push('my_reservations', $r->code);

        return redirect()->route('reservasi.sukses', $r->code);
    }

    public function success(Request $request, string $code)
    {
        // Only the browser that made the reservation sees it here; others use Cek Status.
        if (! in_array($code, $request->session()->get('my_reservations', []), true)) {
            return redirect()->route('cek-status', ['kode' => $code]);
        }
        $r = Reservation::with('roomType', 'room')->where('code', $code)->firstOrFail();
        $waText = "Halo DM Karaoke & Cafe, saya sudah reservasi lewat website.\nKode: {$r->code}\nNama: {$r->name}\nTanggal: ".tgl($r->date)."\nJam: {$r->timeLabel()}";

        return view('site.reservation-success', ['r' => $r, 'waLink' => Phone::waLink($waText)]);
    }

    private function maxDate(): string
    {
        return CarbonImmutable::parse(OpHours::today())->addDays((int) Settings::get('max_days'))->toDateString();
    }

    private function clampDate(?string $date): string
    {
        $today = OpHours::today();
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $today) {
            return $today;
        }

        return min($date, $this->maxDate());
    }
}
