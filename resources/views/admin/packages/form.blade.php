@extends('layouts.admin', ['title' => $p->exists ? 'Ubah paket' : 'Tambah paket', 'subtitle' => $p->exists ? $p->name : 'Paket'])

@section('content')
{{-- FR-ADM-11 --}}
<form method="POST" action="{{ $p->exists ? route('admin.paket.update', $p) : route('admin.paket.store') }}" class="card grid gap-x-4 gap-y-6 md:grid-cols-2 max-w-[860px]">
    @csrf
    @if($p->exists) @method('PUT') @endif
    <div class="field"><label class="label" for="k-type">Ruangan</label>
        <select class="input" id="k-type" name="room_type_id">@foreach($types as $t)<option value="{{ $t->id }}" @selected(old('room_type_id', $p->room_type_id) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="field"><label class="label" for="k-brand">Kelompok paket</label>
        <input class="input" id="k-brand" name="brand" list="k-brands" value="{{ old('brand', $p->brand) }}" placeholder="Contoh: Paket Kawai DM" required maxlength="80">
        <datalist id="k-brands">@foreach($brands as $b)<option value="{{ $b }}">@endforeach</datalist></div>
    <div class="field md:col-span-2"><label class="label" for="k-name">Nama paket</label><input class="input" id="k-name" name="name" value="{{ old('name', $p->name) }}" required maxlength="100"></div>
    <div class="field"><label class="label" for="k-price">Harga (Rp)</label><input class="input" id="k-price" name="price" type="number" min="1" step="1000" value="{{ old('price', $p->price) }}" inputmode="numeric" required></div>
    <div class="field"><label class="label" for="k-hours">Durasi room (jam)</label><input class="input" id="k-hours" name="hours" type="number" min="1" max="12" value="{{ old('hours', $p->hours) }}" inputmode="numeric" required></div>
    <div class="field"><label class="label" for="k-start">Jam mulai</label>
        <select class="input" id="k-start" name="start_hour"><option value="">Bebas (jam operasional)</option>@foreach($hours as $h)<option value="{{ $h }}" @selected((string) old('start_hour', $p->start_hour) === (string) $h)>{{ hl($h) }}</option>@endforeach</select>
        <span class="help">Paket Family: 15.00.</span></div>
    <div class="field justify-end"><label class="switch"><input type="checkbox" name="family" value="1" @checked(old('family', $p->family))> Tampilkan sebagai Paket Family</label></div>
    <div class="field md:col-span-2"><label class="label" for="k-items">Isi paket (satu baris satu item)</label><textarea class="input" id="k-items" name="items" rows="6">{{ old('items', implode("\n", $p->items ?? [])) }}</textarea></div>
    <label class="switch md:col-span-2"><input type="checkbox" name="active" value="1" @checked(old('active', $p->active))> Aktif dan bisa dipesan</label>
    <div class="md:col-span-2 flex justify-end gap-3">
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.paket.index') }}">Batal</a>
        <button class="btn btn-primary btn-sm" type="submit">{{ $p->exists ? 'Simpan perubahan' : 'Tambah paket' }}</button>
    </div>
</form>
@endsection
