@extends('layouts.admin', ['title' => 'Pengguna', 'subtitle' => 'Atur siapa saja yang bisa masuk ke panel admin dan apa aksesnya.'])

@section('actions')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.pengguna.create') }}"><x-icon name="plus" class="ic-sm" />Tambah pengguna</a>
@endsection

@section('content')
{{-- FR-ADM-16 --}}
<div class="grid gap-2 sm:gap-4 grid-cols-3">
    <div class="stat"><b>{{ $users->count() }}</b><span>Pengguna</span></div><div class="stat"><b>{{ $users->where('active', true)->count() }}</b><span>Aktif</span></div><div class="stat"><b>{{ $users->where('role', 'super_admin')->count() }}</b><span>Super admin</span></div>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th scope="col">Pengguna</th><th scope="col">Peran</th><th scope="col">Akses</th><th scope="col">Status</th><th scope="col">Terakhir masuk</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
            @foreach($users as $u)
                @php($self = $u->is(auth()->user()))
                <tr>
                    <td><div class="flex items-center gap-3"><span class="avatar">{{ mb_substr($u->name, 0, 1) }}</span><div><b class="block font-medium">{{ $u->name }}@if($self) <span class="caption">(kamu)</span>@endif</b><span class="text-[13px] text-ink-subtle">&#64;{{ $u->username }}</span></div></div></td>
                    <td>{{ $u->roleLabel() }}</td>
                    <td class="text-ink-muted">{{ collect($u->perms())->map(fn ($p) => config("dmk.perm_labels.{$p}"))->join(', ') }}</td>
                    <td><span class="badge badge-dot {{ $u->active ? 'badge-success' : '' }}">{{ $u->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-ink-muted">{{ $u->last_login_at ? tgl($u->last_login_at, 'datetime') : 'Belum pernah' }}</td>
                    <td class="text-right whitespace-nowrap">
                        <div class="inline-flex gap-2">
                            <a class="icon-btn" href="{{ route('admin.pengguna.edit', $u) }}" aria-label="Ubah {{ $u->name }}" title="Ubah"><x-icon name="edit" class="ic-sm" /></a>
                            @unless($self)
                                <form method="POST" action="{{ route('admin.pengguna.toggle', $u) }}">@csrf<button class="icon-btn" type="submit" aria-label="{{ $u->active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $u->name }}" title="{{ $u->active ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon :name="$u->active ? 'lock' : 'check'" class="ic-sm" /></button></form>
                                <form method="POST" action="{{ route('admin.pengguna.destroy', $u) }}" data-confirm="Hapus pengguna?" data-confirm-text="Akun {{ $u->name }} (&#64;{{ $u->username }}) tidak bisa masuk lagi setelah dihapus." data-confirm-label="Hapus pengguna">
                                    @csrf @method('DELETE')<button class="icon-btn is-danger" type="submit" aria-label="Hapus {{ $u->name }}" title="Hapus"><x-icon name="trash" class="ic-sm" /></button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="caption m-0">@foreach(config('dmk.roles') as $r)<b class="text-ink-muted font-medium">{{ $r['label'] }}</b>: {{ collect($r['perms'])->map(fn ($p) => config("dmk.perm_labels.{$p}"))->join(', ') }}@unless($loop->last) · @endunless @endforeach</p>
@endsection
