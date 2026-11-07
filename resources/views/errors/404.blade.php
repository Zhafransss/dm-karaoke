@extends('layouts.public', [
    'nav' => null,
    'title' => 'Halaman tidak ditemukan | DM Karaoke & Cafe',
    'description' => 'Halaman yang kamu cari tidak ditemukan. Lihat harga, jadwal, dan reservasi karaoke di DM Karaoke & Cafe Rawalo, Banyumas.',
    'noindex' => true,
])

@section('content')
<x-page-head crumb="404" eyebrow="Error 404" title="Halaman <em>tidak ditemukan</em>" lead="Alamat yang kamu buka tidak ada atau sudah dipindah. Coba salah satu halaman di bawah ini." />

<section class="band"><div class="wrap">
    <div class="flex flex-wrap justify-center gap-4">
        <a class="btn btn-primary" href="{{ route('home') }}">Ke beranda</a>
        <a class="btn btn-outline" href="{{ route('harga') }}">Lihat daftar harga</a>
        <a class="btn btn-outline" href="{{ route('reservasi') }}">Cek jadwal &amp; reservasi</a>
        <a class="btn btn-ghost" href="{{ route('galeri') }}">Galeri</a>
    </div>
</div></section>
@endsection
