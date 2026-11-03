<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\ImageService;
use App\Services\MediaService;
use App\Services\PromoService;
use App\Support\OpHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Room types, rates and units (FR-ADM-10). */
class RoomTypeController extends Controller
{
    public function __construct(private ImageService $images, private MediaService $media) {}

    public function index(PromoService $promos)
    {
        return view('admin.rooms.index', [
            'types' => RoomType::with('rooms', 'packages')->orderBy('sort')->orderBy('id')->get(),
            'promoSvc' => $promos,
            'today' => OpHours::today(),
        ]);
    }

    public function create()
    {
        return view('admin.rooms.form', ['t' => new RoomType(['cap' => 6, 'show_cap' => true, 'rentable' => true, 'rate_day' => 40000, 'rate_night' => 80000, 'active' => true, 'facilities' => ['', '', '']]),
            'units' => collect([new Room(['name' => 'Ruang 1', 'active' => true])])]);
    }

    public function edit(RoomType $roomType)
    {
        return view('admin.rooms.form', ['t' => $roomType->load('media'), 'units' => $roomType->rooms]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new RoomType);
    }

    public function update(Request $request, RoomType $roomType)
    {
        return $this->save($request, $roomType);
    }

    public function destroy(RoomType $roomType)
    {
        // Types with reservation history are only deactivated (SRS §5 Integritas).
        if (Reservation::where('room_type_id', $roomType->id)->exists()) {
            return back()->withErrors(['delete' => "{$roomType->name} sudah punya riwayat reservasi. Nonaktifkan lewat tombol Ubah agar tidak bisa dipesan lagi, riwayatnya tetap aman."]);
        }
        $mediaFiles = $roomType->media->map(fn ($m) => [$m->path, $m->type]);
        DB::transaction(function () use ($roomType) {
            $keys = [$roomType->rentKey(), ...$roomType->packages->map->pkgKey()];
            DB::table('promo_targets')->whereIn('target_key', $keys)->delete();
            DB::table('blocks')->whereIn('room_id', $roomType->rooms->pluck('id'))->delete();
            $roomType->packages()->delete();
            $roomType->rooms()->delete();
            $roomType->delete();
        });
        $this->images->delete($roomType->image);
        $mediaFiles->each(fn ($m) => $this->media->delete($m[0], $m[1]));

        return redirect()->route('admin.ruangan.index')->with('ok', 'Tipe ruangan dihapus.');
    }

    private function save(Request $request, RoomType $t)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'cap' => ['nullable', 'integer', 'min:1', 'max:200'],
            'rate_day' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'rate_night' => [$request->boolean('rentable') ? 'required' : 'nullable', 'integer', $request->boolean('rentable') ? 'min:1' : 'min:0'],
            'facilities' => ['array', 'max:3'], 'facilities.*' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'units' => ['required', 'array', 'min:1'], 'units.*.id' => ['nullable', 'integer'], 'units.*.name' => ['required', 'string', 'max:60'],
        ], [
            'name.required' => 'Nama wajib diisi.', 'rate_night.*' => 'Isi tarif malam lebih dari 0.',
            'units.required' => 'Setiap ruangan butuh minimal satu unit.', 'units.*.name.required' => 'Setiap unit harus punya nama.',
        ]);
        $units = collect($data['units'])->map(fn ($u, $i) => ['id' => $u['id'] ?? null, 'name' => trim($u['name']), 'active' => $request->boolean("units.{$i}.active")]);
        if ($units->pluck('name')->map(fn ($n) => Str::lower($n))->duplicates()->isNotEmpty()) {
            return back()->withInput()->withErrors(['units' => 'Nama unit tidak boleh sama.']);
        }

        $existing = $t->exists ? $t->rooms->keyBy('id') : collect();
        $keepIds = $units->pluck('id')->filter()->map(fn ($v) => (int) $v);
        $removed = $existing->keys()->diff($keepIds);
        $deactivated = $units->filter(fn ($u) => $u['id'] && ! $u['active'])->pluck('id');
        $busy = $this->futureActive($removed->merge($deactivated));
        if ($busy->isNotEmpty()) {
            return back()->withInput()->withErrors(['units' => $busy->join(', ').' masih punya reservasi aktif. Pindahkan reservasinya dulu.']);
        }
        if (Reservation::whereIn('room_id', $removed)->exists()) {
            return back()->withInput()->withErrors(['units' => 'Unit yang sudah punya riwayat reservasi tidak bisa dihapus. Nonaktifkan saja.']);
        }

        DB::transaction(function () use ($t, $data, $request, $units, $removed) {
            $t->fill([
                'name' => $data['name'], 'description' => $data['description'] ?? null,
                'cap' => $data['cap'] ?? null, 'show_cap' => $request->boolean('show_cap'), 'rentable' => $request->boolean('rentable'),
                'rate_day' => (int) ($data['rate_day'] ?? 0), 'rate_night' => (int) ($data['rate_night'] ?? 0),
                // Paid in full up front means there is no deposit.
                'prepaid' => $request->boolean('prepaid'), 'deposit' => $request->boolean('prepaid') ? 0 : (int) ($data['deposit'] ?? 0),
                'facilities' => array_values(array_map(fn ($f) => trim((string) $f), $data['facilities'] ?? [])),
                'featured' => $request->boolean('featured'), 'active' => $request->boolean('active'),
            ]);
            if (! $t->exists) {
                $t->slug = $this->uniqueSlug($data['name']);
                $t->sort = (int) RoomType::max('sort') + 1;
            }
            if ($request->hasFile('image')) {
                $old = $t->image;
                $t->image = $this->images->store($request->file('image'), 'rooms');
                $this->images->delete($old);
            }
            $t->save();

            Room::whereIn('id', $removed)->delete();
            foreach ($units->values() as $i => $u) {
                $room = $u['id'] ? Room::where('room_type_id', $t->id)->find($u['id']) : new Room(['room_type_id' => $t->id]);
                $room?->fill(['name' => $u['name'], 'active' => $u['active'], 'sort' => $i + 1])->save();
            }
        });

        return redirect()->route('admin.ruangan.index')->with('ok', $t->wasRecentlyCreated ? 'Ruangan ditambahkan.' : 'Ruangan diperbarui.');
    }

    private function futureActive($roomIds)
    {
        return Room::whereIn('id', $roomIds)->get()->filter(fn ($room) => Reservation::occupying()->where('room_id', $room->id)
            ->whereDate('date', '>=', OpHours::today())->exists())->pluck('name');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'ruangan';
        $slug = $base;
        $n = 2;
        while (RoomType::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
