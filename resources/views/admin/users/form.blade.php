@extends('layouts.admin', ['title' => $u->exists ? 'Ubah pengguna' : 'Tambah pengguna', 'subtitle' => 'Pengguna'])

@php($self = $u->exists && $u->is(auth()->user()))

@section('content')
<form method="POST" action="{{ $u->exists ? route('admin.pengguna.update', $u) : route('admin.pengguna.store') }}" class="card grid gap-x-4 gap-y-6 sm:grid-cols-2 max-w-[640px]">
    @csrf
    @if($u->exists) @method('PUT') @endif
    <div class="field sm:col-span-2"><label class="label" for="us-name">Nama lengkap</label><input class="input" id="us-name" name="name" value="{{ old('name', $u->name) }}" autocomplete="off" required maxlength="100"></div>
    <div class="field"><label class="label" for="us-user">Username</label><input class="input" id="us-user" name="username" value="{{ old('username', $u->username) }}" autocomplete="off" autocapitalize="none" placeholder="huruf kecil tanpa spasi" required></div>
    <div class="field"><label class="label" for="us-role">Peran</label>
        <select class="input" id="us-role" name="role" @disabled($self)>@foreach(config('dmk.roles') as $k => $r)<option value="{{ $k }}" @selected(old('role', $u->role) === $k)>{{ $r['label'] }}</option>@endforeach</select>
        @if($self)<span class="help">Peran akunmu sendiri tidak bisa diubah.</span>@endif</div>
    <div class="field sm:col-span-2"><label class="label" for="us-pass">{{ $u->exists ? 'Kata sandi baru (opsional)' : 'Kata sandi' }}</label>
        <input class="input" id="us-pass" name="password" type="password" autocomplete="new-password" placeholder="{{ $u->exists ? 'Kosongkan bila tidak diubah' : 'Minimal 8 karakter' }}"></div>
    @unless($self)<label class="switch sm:col-span-2"><input type="checkbox" name="active" value="1" @checked(old('active', $u->active ?? true))> Akun aktif dan bisa masuk</label>@endunless
    <div class="sm:col-span-2 flex justify-end gap-3"><a class="btn btn-quiet btn-sm" href="{{ route('admin.pengguna.index') }}">Batal</a><button class="btn btn-primary btn-sm" type="submit">{{ $u->exists ? 'Simpan perubahan' : 'Tambah pengguna' }}</button></div>
</form>
@endsection
