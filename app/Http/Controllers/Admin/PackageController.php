<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PromoTarget;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Support\OpHours;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Package CMS (FR-ADM-11). Prices are fixed and independent of day/night rates. */
class PackageController extends Controller
{
    public function index()
    {
        return view('admin.packages.index', ['types' => RoomType::with('packages')->orderBy('sort')->get()]);
    }

    public function create(Request $request)
    {
        return view('admin.packages.form', $this->formData(new Package([
            'room_type_id' => $request->integer('tipe') ?: RoomType::orderBy('sort')->value('id'), 'hours' => 3, 'items' => [], 'active' => true,
        ])));
    }

    public function edit(Package $package)
    {
        return view('admin.packages.form', $this->formData($package));
    }

    public function store(Request $request)
    {
        return $this->save($request, new Package);
    }

    public function update(Request $request, Package $package)
    {
        return $this->save($request, $package);
    }

    public function toggle(Package $package)
    {
        $package->update(['active' => ! $package->active]);

        return back()->with('ok', $package->name.($package->active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function destroy(Package $package)
    {
        if (Reservation::where('package_id', $package->id)->exists()) {
            return back()->withErrors(['delete' => "{$package->name} sudah punya riwayat reservasi. Nonaktifkan saja lewat sakelar Aktif, riwayatnya tetap aman."]);
        }
        PromoTarget::where('target_key', $package->pkgKey())->delete();
        $package->delete();

        return redirect()->route('admin.paket.index')->with('ok', 'Paket dihapus. Promo yang mengarah ke paket ini ikut dilepas.');
    }

    private function formData(Package $p): array
    {
        return [
            'p' => $p,
            'types' => RoomType::orderBy('sort')->get(),
            'brands' => Package::query()->distinct()->orderBy('brand')->pluck('brand'),
            'hours' => OpHours::hours(),
        ];
    }

    private function save(Request $request, Package $p)
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'brand' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:1'],
            'hours' => ['required', 'integer', 'between:1,12'],
            'start_hour' => ['nullable', 'integer', Rule::in(OpHours::hours())],
            'items' => ['nullable', 'string', 'max:2000'],
        ], [
            'brand.required' => 'Isi nama kelompok paket.', 'name.required' => 'Nama paket wajib diisi.',
            'price.*' => 'Isi harga lebih dari 0.', 'hours.*' => 'Isi antara 1 dan 12 jam.',
        ]);
        $p->fill([
            ...$data,
            'items' => collect(preg_split('/\r?\n/', (string) ($data['items'] ?? '')))->map(fn ($x) => trim($x))->filter()->values()->all(),
            'start_hour' => $data['start_hour'] ?? null,
            'family' => $request->boolean('family'),
            'active' => $request->boolean('active'),
        ]);
        if (! $p->exists) {
            $p->sort = (int) Package::max('sort') + 1;
        }
        $p->save();

        return redirect()->route('admin.paket.index')->with('ok', $p->wasRecentlyCreated ? 'Paket ditambahkan.' : 'Paket diperbarui.');
    }
}
