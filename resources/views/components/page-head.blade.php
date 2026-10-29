@props(['crumb', 'eyebrow', 'title', 'lead' => null])
<section class="page-head">
    <div class="wrap">
        <nav class="crumbs mb-3" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span aria-hidden="true">/</span><span>{{ $crumb }}</span></nav>
        <x-heading :eyebrow="$eyebrow" :title="$title" :lead="$lead" tag="h1" />
    </div>
</section>
