{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@if($u['lastmod'])
        <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
@foreach($u['images'] as $img)
        <image:image><image:loc>{{ $img }}</image:loc></image:image>
@endforeach
    </url>
@endforeach
</urlset>
