<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\BookingException;
use App\Services\PricingService;
use App\Services\ReservationService;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\OpHours;
use App\Support\Phone;
use App\Support\QuotePresenter;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** FR-ADM-05..07, FR-ADM-09. */
class ReservationController extends Controller
{
    public function __construct(private ReservationService $service, private AvailabilityService $availability) {}

    public function index(Request $request)
    {
        $today = OpHours::today();
        $when = in_array($request->query('waktu'), ['mendatang', 'hari-ini', 'semua'], true) ? $request->query('waktu') : 'mendatang';
        $status = array_key_exists((string) $request->query('status'), config('dmk.statuses')) ? $request->query('status') : null;
        $typeId = $request->integer('tipe') ?: null;
        $q = trim((string) $request->query('q'));

        $base = Reservation::query()
            ->when($when === 'hari-ini', fn ($b) => $b->whereDate('date', $today))
            ->when($when === 'mendatang', fn ($b) => $b->whereDate('date', '>=', $today))
            ->when($typeId, fn ($b) => $b->where('room_type_id', $typeId))
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('code', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")->orWhere('phone', 'like', '%'.Phone::normalize($q).'%')));

        $counts = (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $list = (clone $base)->with('roomType', 'room')
            ->when($status, fn ($b) => $b->where('status', $status))
            ->when($when === 'semua', fn ($b) => $b->orderByDesc('date')->orderByDesc('start_hour'), fn ($b) => $b->orderBy('date')->orderBy('start_hour'))
            ->paginate(30)->withQueryString();

        return view('admin.reservations.index', [
            'list' => $list, 'counts' => $counts, 'when' => $when, 'status' => $status, 'typeId' => $typeId, 'q' => $q,
            'types' => RoomType::orderBy('sort')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $types = RoomType::active()->with('activePackages')->get();
        $room = $request->integer('unit') ? Room::find($request->integer('unit')) : null;
        $type = $room ? $types->firstWhere('id', $room->room_type_id) : $types->first();
        $now = now();
        $defHour = $now->hour >= OpHours::open() ? $now->hour : ($now->hour + 24 < OpHours::close() ? $now->hour + 24 : OpHours::open());

        return view('admin.reservations.create', [
            'config' => [
                'state' => [
                    'typeId' => old('room_type_id', $type?->id),
                    'roomId' => old('room_id', $room?->id ?? ''),
                    'mode' => old('package_id') ? (string) old('package_id') : ($type?->rentable ? 'sewa' : (string) $type?->activePackages->first()?->id),
                    'date' => old('date', $request->query('tanggal', OpHours::today())),
                    'hour' => (int) old('start_hour', $request->query('jam', min(max(OpHours::open(), $defHour), OpHours::close() - 1))),
                    'hours' => (int) old('hours', 2),
                    'lc' => (bool) old('lc', false),
                ],
                'types' => $types->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'rentable' => $t->rentable,
                    'packages' => $t->activePackages->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'brand' => $p->brand, 'price' => rp($p->price), 'hours' => $p->hours, 'start_hour' => $p->start_hour])->values()])->values(),
                'urls' => ['preview' => route('admin.reservasi.preview')],
            ],
            'hours' => OpHours::hours(),
            'maxHours' => (int) config('dmk.max_rent_hours'),
        ]);
    }

    /** Units free/busy for the chosen range plus the price, for the admin form. */
    public function preview(Request $request, PricingService $pricing)
    {
        $in = $request->validate([
            'room_type_id' => ['required', 'integer'], 'package_id' => ['nullable', 'integer'], 'date' => ['required', 'date_format:Y-m-d'],
            'start_hour' => ['required', 'integer'], 'hours' => ['required', 'integer', 'min:1'], 'lc' => ['boolean'],
            'room_id' => ['nullable', 'integer'], 'ignore' => ['nullable', 'integer'],
        ]);
        $type = RoomType::findOrFail($in['room_type_id']);
        $pkg = ! empty($in['package_id']) ? Package::find($in['package_id']) : null;
        $hours = $pkg ? $pkg->hours : $in['hours'];
        $map = $this->availability->busyMap($type->id, $in['date'], $in['ignore'] ?? null);
        $units = $this->availability->units($type->id)->map(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'busy' => ! $this->availability->roomFree($map, $u->id, $in['start_hour'], $hours),
        ])->values();

        if ($err = $this->availability->ruleError($type, $pkg, $in['date'], $in['start_hour'], $hours, true)) {
            return response()->json(['units' => $units, 'message' => $err], 422);
        }
        $chosen = ! empty($in['room_id']) ? $units->firstWhere('id', (int) $in['room_id']) : $units->firstWhere('busy', false);
        $message = ! $chosen ? "Semua unit {$type->name} sudah terisi di jam ini." : ($chosen['busy'] ? 'Unit yang dipilih sudah terisi di jam ini.' : '');
        $q = $pricing->calculate($type, $pkg, $in['date'], $in['start_hour'], $hours, (bool) ($in['lc'] ?? false));

        return response()->json(['units' => $units, 'message' => $message, 'quote' => QuotePresenter::forQuote($q, $in['date'], $in['start_hour'], $type)]);
    }

    public function store(Request $request)
    {
        $type = RoomType::find($request->integer('room_type_id'));
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'], 'package_id' => ['nullable', 'exists:packages,id'],
            'room_id' => ['nullable', 'exists:rooms,id'], 'date' => ['required', 'date_format:Y-m-d'],
            'start_hour' => ['required', 'integer'], 'hours' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['dikonfirmasi', 'menunggu'])], 'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', fn ($a, $v, $fail) => $v && ! Phone::valid($v) ? $fail('Format nomor tidak valid, contoh 081234567890.') : null],
            'pax' => ['required', 'integer', 'min:1', 'max:'.($type?->maxPax() ?? 30)], 'note' => ['nullable', 'string', 'max:500'],
        ], ['name.required' => 'Nama wajib diisi.', 'pax.max' => 'Maksimal :max orang untuk ruangan ini.']);

        try {
            $r = $this->service->create([...$data, 'lc' => $request->boolean('lc')], 'admin', $request->user());
        } catch (BookingException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('admin.jadwal', ['tanggal' => $r->ymd()])->with('ok', "Reservasi {$r->code} disimpan di {$r->room->name}.");
    }

    public function show(Reservation $reservation)
    {
        $r = $reservation->load('roomType', 'room', 'logs.user', 'package');
        $tpl = match ($r->status) {
            'dikonfirmasi' => Settings::get('tpl_confirm'),
            'dibatalkan' => Settings::get('tpl_reject'),
            default => 'Halo {nama}, ini dari DM Karaoke & Cafe terkait reservasi {kode} untuk {tanggal} pukul {jam}. ',
        };

        $units = Room::where('room_type_id', $r->room_type_id)->where(fn ($q) => $q->where('active', true)->orWhere('id', $r->room_id))->orderBy('sort')->get();

        return view('admin.reservations.show', [
            'r' => $r,
            'waLink' => $r->phone ? Phone::waLink(WhatsAppService::render($tpl, $r->templateVars()), $r->phone) : null,
            'units' => $units,
            'hours' => OpHours::hours(),
            'moveConfig' => [
                'state' => ['roomId' => $r->room_id, 'date' => $r->ymd(), 'hour' => $r->start_hour],
                'reservation' => ['id' => $r->id, 'type' => $r->room_type_id, 'package' => $r->package_id, 'hours' => $r->hours, 'lc' => (bool) $r->lc],
                'units' => $units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'active' => (bool) $u->active, 'busy' => false])->values(),
                'url' => route('admin.reservasi.preview'),
            ],
        ]);
    }

    public function status(Request $request, Reservation $reservation)
    {
        $to = $request->validate(['status' => ['required', Rule::in(array_keys(config('dmk.statuses')))]])['status'];
        try {
            $this->service->changeStatus($reservation, $to, $request->user());
        } catch (BookingException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
        $msg = [
            'dikonfirmasi' => 'Reservasi dikonfirmasi.', 'dibatalkan' => 'Reservasi dibatalkan dan jamnya kembali tersedia.',
            'selesai' => 'Reservasi ditandai selesai.', 'tidak_datang' => 'Reservasi ditandai tidak datang dan jamnya kembali tersedia.',
        ][$to];
        if (in_array($to, ['dikonfirmasi', 'dibatalkan'], true)) {
            $msg .= Settings::get('wa_auto_status') && $reservation->phone ? ' Pesan WhatsApp ke tamu sedang dikirim.' : ' Kabari tamu lewat tombol WhatsApp.';
        }

        return redirect()->route('admin.reservasi.show', $reservation)->with('ok', $msg);
    }

    public function move(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'], 'date' => ['required', 'date_format:Y-m-d'], 'start_hour' => ['required', 'integer'],
        ]);
        try {
            $this->service->reschedule($reservation, $data['room_id'], $data['date'], $data['start_hour'], $request->boolean('reprice'), $request->user());
        } catch (BookingException $e) {
            return back()->withErrors(['move' => $e->getMessage()]);
        }

        return redirect()->route('admin.reservasi.show', $reservation)->with('ok', 'Jadwal reservasi diperbarui.');
    }
}
