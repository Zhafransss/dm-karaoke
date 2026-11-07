<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Promo;
use App\Models\RoomType;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Support\OpHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Promo CMS with live preview and overlap information (FR-ADM-12, FR-ADM-13). */
class PromoController extends Controller
{
    public function __construct(private PromoService $promos) {}

    public function index()
    {
        $today = OpHours::today();
        $order = ['berjalan' => 0, 'terjadwal' => 1, 'nonaktif' => 2, 'berakhir' => 3];
        $list = Promo::with('targetRows')->get()->sortBy(fn ($p) => [$order[$p->state($today)], $p->ds()])->values();

        return view('admin.promos.index', ['list' => $list, 'today' => $today, 'labels' => $this->promos->targetLabels()]);
    }

    public function create()
    {
        $today = OpHours::today();

        return view('admin.promos.form', $this->formData(new Promo([
            'kind' => 'persen', 'x' => 3, 'y' => 2, 'date_start' => $today, 'date_end' => CarbonImmutable::parse($today)->addDays(14)->toDateString(), 'active' => true,
        ])));
    }

    public function edit(Promo $promo)
    {
        return view('admin.promos.form', $this->formData($promo->load('targetRows')));
    }

    public function store(Request $request)
    {
        return $this->save($request, new Promo);
    }

    public function update(Request $request, Promo $promo)
    {
        return $this->save($request, $promo);
    }

    public function toggle(Promo $promo)
    {
        $promo->update(['active' => ! $promo->active]);

        return back()->with('ok', $promo->name.($promo->active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return redirect()->route('admin.promo.index')->with('ok', 'Promo dihapus. Reservasi yang sudah memakai promo ini tetap menyimpan harganya.');
    }

    /** Example calculation + overlapping promos for the form, using the real PricingService. */
    public function preview(Request $request, PricingService $pricing)
    {
        $p = $this->fromRequest($request, $request->input('id') ? Promo::find($request->input('id')) : null);
        $valid = $p->kind === 'bayar_x' ? ($p->x >= 2 && $p->y >= 1 && $p->y < $p->x) : $p->value > 0;
        $preview = '';
        $targets = $p->targets();
        if ($targets && $valid) {
            [$kind, $ref] = explode(':', $targets[0]);
            $pkg = $kind === 'pkg' ? Package::find($ref) : null;
            $type = RoomType::find($pkg ? $pkg->room_type_id : $ref);
            if ($type && ($pkg || $type->rentable)) {
                $hour = $p->time_start ?? ($pkg?->start_hour ?? max(OpHours::open(), 20));
                $hours = $p->kind === 'bayar_x' ? $p->x : 3;
                $q = $pricing->calculate($type, $pkg, OpHours::today(), $hour, $hours, false, $p);
                $label = $this->promos->targetLabels()[$targets[0]] ?? '';
                $preview = "{$label} ".OpHours::range($hour, $q['hours'])." ({$q['hours']} jam): ".rp($q['base']).' → '.rp($q['total'])
                    .($q['discount'] > 0 ? '' : ' (promo tidak berlaku di contoh ini)').($q['promo_note'] ? " ({$q['promo_note']})" : '');
            }
        }
        $overlaps = ($p->ds() && $p->de() && $p->de() >= $p->ds()) ? $this->overlapRows($p) : [];

        return response()->json(['preview' => $preview, 'overlaps' => $overlaps]);
    }

    private function save(Request $request, Promo $promo)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(config('dmk.promo_kinds')))],
            'value' => ['exclude_if:kind,bayar_x', 'required', 'integer', 'min:1', $request->input('kind') === 'persen' ? 'max:100' : 'max:100000000'],
            'x' => ['exclude_unless:kind,bayar_x', 'required', 'integer', 'between:2,12'],
            'y' => ['exclude_unless:kind,bayar_x', 'required', 'integer', 'min:1', 'lt:x'],
            'targets' => ['required', 'array', 'min:1'], 'targets.*' => ['string', 'regex:/^(rent|pkg):\d+$/'],
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'time_start' => ['exclude_unless:window,1', 'required', 'integer'],
            'time_end' => ['exclude_unless:window,1', 'required', 'integer', 'gt:time_start'],
        ], [
            'name.required' => 'Nama promo wajib diisi.', 'value.*' => 'Isi nilai potongan yang benar (persen 1–100).',
            'y.lt' => 'Jam bayar harus lebih kecil dari jam sewa.', 'targets.required' => 'Pilih minimal satu target.',
            'date_end.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.', 'time_end.gt' => 'Jam selesai harus setelah jam mulai.',
        ]);
        $p = $this->fromRequest($request, $promo);
        $pkgIds = collect($p->targets())->filter(fn ($t) => str_starts_with($t, 'pkg:'))->map(fn ($t) => (int) substr($t, 4));
        $invalid = Package::with('roomType')->whereIn('id', $pkgIds)->get()->reject(fn (Package $pk) => $p->packageEligible($pk));
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages(['targets' => "Promo bayar {$p->x} jam hanya untuk paket minimal {$p->x} jam (bukan Paket Family atau Master Room). Lepas centang: ".$invalid->pluck('name')->join(', ').'.']);
        }
        $targets = $p->targets();
        $p->pendingTargets = null;
        $p->save();
        $p->syncTargets($targets);

        return redirect()->route('admin.promo.index')->with('ok', $promo->wasRecentlyCreated ? 'Promo disimpan.' : 'Promo diperbarui.');
    }

    private function fromRequest(Request $request, ?Promo $promo): Promo
    {
        $p = $promo ?? new Promo;
        $kind = $request->input('kind', 'persen');
        $window = $request->boolean('window');
        $p->fill([
            'name' => trim((string) $request->input('name')),
            'description' => $request->input('description'),
            'kind' => $kind,
            'value' => $kind === 'bayar_x' ? 0 : (int) $request->input('value'),
            'x' => $kind === 'bayar_x' ? (int) $request->input('x') : null,
            'y' => $kind === 'bayar_x' ? (int) $request->input('y') : null,
            'date_start' => $this->ymd($request->input('date_start')),
            'date_end' => $this->ymd($request->input('date_end')),
            'time_start' => $window ? (int) $request->input('time_start') : null,
            'time_end' => $window ? (int) $request->input('time_end') : null,
            'active' => $request->boolean('active'),
        ]);
        $targets = array_values(array_filter((array) $request->input('targets', []), fn ($t) => is_string($t)));
        $p->pendingTargets = $targets;

        return $p;
    }

    private function ymd(mixed $value): string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : OpHours::today();
    }

    private function overlapRows(Promo $p): array
    {
        $labels = $this->promos->targetLabels();

        return $this->promos->overlaps($p)->map(fn (Promo $q) => [
            'name' => $q->name,
            'targets' => collect(array_intersect($q->targets(), $p->targets()))->map(fn ($k) => $labels[$k] ?? $k)->join(', '),
            'period' => $q->periodLabel(),
        ])->all();
    }

    private function formData(Promo $p): array
    {
        return [
            'p' => $p,
            'types' => RoomType::with('packages')->orderBy('sort')->get(),
            'hours' => OpHours::hours(),
            'targets' => old('targets', $p->targets()),
        ];
    }
}
