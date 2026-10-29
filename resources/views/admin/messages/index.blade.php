@extends('layouts.admin', ['title' => 'Pesan', 'subtitle' => 'Pesan dan saran dari halaman Kontak.'])

@section('actions')
    @if($counts['baru'])
        <form method="POST" action="{{ route('admin.pesan.read-all') }}">@csrf<button class="btn btn-quiet btn-sm" type="submit"><x-icon name="check" class="ic-sm" />Tandai semua dibaca</button></form>
    @endif
@endsection

@section('content')
{{-- FR-ADM-15 --}}
<nav class="flex flex-wrap gap-2" aria-label="Filter pesan">
    @foreach(['semua' => 'Semua', 'baru' => 'Belum dibaca', 'dibaca' => 'Sudah dibaca'] as $k => $l)
        <a class="chip" href="{{ route('admin.pesan.index', $k === 'semua' ? [] : ['tab' => $k]) }}" @if($tab === $k) aria-current="page" @endif>{{ $l }} ({{ $counts[$k] }})</a>
    @endforeach
</nav>
@if($list->isEmpty())
    <div class="empty">{{ $tab === 'baru' ? 'Semua pesan sudah dibaca.' : 'Belum ada pesan.' }}</div>
@else
<div class="flex flex-col gap-3">
    @foreach($list as $m)
        @php($isOpen = $open === $m->id)
        <article class="bg-surface-raised border rounded-md min-w-0 {{ $m->isRead() && ! $isOpen ? 'border-line' : 'border-gold-deep' }}">
            <a class="grid grid-cols-[12px_minmax(0,1fr)] sm:grid-cols-[12px_minmax(0,1fr)_auto] gap-x-4 gap-y-1 items-start px-6 py-4 no-underline text-inherit"
               href="{{ route('admin.pesan.index', array_filter(['tab' => $tab === 'semua' ? null : $tab, 'buka' => $isOpen ? null : $m->id])) }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                <span class="w-2 h-2 mt-2 rounded-full {{ $m->isRead() ? '' : 'bg-gold shadow-[0_0_10px_rgba(201,165,76,.7)]' }}" aria-hidden="true"></span>
                <span class="min-w-0">
                    <span class="flex flex-wrap items-center gap-3"><b class="text-[15px] font-medium">{{ $m->name }}</b><span class="badge {{ ['Kritik' => 'badge-velvet', 'Saran' => 'badge-gold'][$m->kind] ?? '' }}">{{ $m->kind }}</span>@unless($m->isRead())<span class="sr-only">Belum dibaca</span>@endunless</span>
                    @unless($isOpen)<span class="block mt-1 text-sm leading-[22px] text-ink-muted truncate">{{ $m->message }}</span>@endunless
                </span>
                <span class="col-start-2 sm:col-start-auto text-xs leading-[18px] text-ink-subtle whitespace-nowrap">{{ tgl($m->created_at, 'datetime') }}</span>
            </a>
            @if($isOpen)
                <div class="flex flex-col gap-4 px-6 pb-6 sm:pl-[52px]">
                    <p class="m-0 whitespace-pre-wrap text-[15px] leading-6 max-w-[70ch]">{{ $m->message }}</p>
                    <div class="text-[13px] leading-5 text-ink-subtle">Kontak: @if($m->contact)<b class="text-ink-muted font-medium select-all">{{ $m->contact }}</b>@else tidak dicantumkan @endif</div>
                    <div class="flex flex-wrap gap-3">
                        @if($m->contactIsPhone())
                            <a class="btn btn-primary btn-sm" href="{{ \App\Support\Phone::waLink('Halo '.$m->name.', terima kasih atas pesannya untuk DM Karaoke & Cafe. ', $m->contact) }}" target="_blank" rel="noopener"><x-icon name="chat" class="ic-sm" />Balas via WhatsApp</a>
                        @elseif($m->contact)
                            <button class="btn btn-quiet btn-sm" type="button" onclick="copyText(@js($m->contact), this)"><x-icon name="copy" class="ic-sm" />Salin kontak</button>
                        @endif
                        <form method="POST" action="{{ route('admin.pesan.toggle', $m) }}">@csrf<button class="btn btn-quiet btn-sm" type="submit"><x-icon :name="$m->isRead() ? 'mail' : 'read'" class="ic-sm" />{{ $m->isRead() ? 'Tandai belum dibaca' : 'Tandai dibaca' }}</button></form>
                        <form method="POST" action="{{ route('admin.pesan.destroy', $m) }}" data-confirm="Hapus pesan?" data-confirm-text="Pesan dari {{ $m->name }} akan dihapus permanen." data-confirm-label="Hapus pesan">
                            @csrf @method('DELETE')<button class="btn btn-quiet btn-sm" type="submit"><x-icon name="trash" class="ic-sm" />Hapus</button>
                        </form>
                    </div>
                </div>
            @endif
        </article>
    @endforeach
</div>
{{ $list->links() }}
@endif
@endsection
