<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GalleryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-01 10:00:00');
        $this->seedVenue();
        $this->vip->update(['description' => 'Ruang lebih luas untuk rombongan dan perayaan.']);
    }

    /** All JSON-LD blocks of a page, keyed by @type. */
    private function schemas(string $url): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $this->get($url)->assertOk()->getContent(), $m);

        return collect($m[1])->map(fn ($j) => json_decode($j, true, flags: JSON_THROW_ON_ERROR))->keyBy('@type')->all();
    }

    private function event(array $over = []): Event
    {
        return Event::create([...['title' => 'Malam Akustik', 'slug' => 'malam-akustik', 'body' => 'Live musik akustik setiap Jumat malam di DM Karaoke.', 'date' => '2026-11-13', 'start_time' => '19.00', 'end_time' => '00.00', 'published' => true], ...$over]);
    }

    public function test_every_indexable_page_has_a_snippet_sized_description_and_share_tags(): void
    {
        $this->event();
        foreach (['/', '/harga', '/reservasi', '/promo', '/event', '/galeri', '/kontak', '/ruangan/'.$this->vip->slug, '/ruangan/'.$this->master->slug, '/event/malam-akustik'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match('/name="description" content="([^"]*)"/', $html, $d);
            $len = mb_strlen(html_entity_decode($d[1]));
            $this->assertGreaterThanOrEqual(60, $len, "{$url} description too short ({$len})");
            $this->assertLessThanOrEqual(160, $len, "{$url} description too long ({$len})");
            $this->assertStringContainsString('name="twitter:title"', $html, $url);
            $this->assertStringContainsString('name="twitter:image"', $html, $url);
            $this->assertStringContainsString('max-image-preview:large', $html, $url);
            $this->assertSame(1, substr_count($html, '<h1'), "{$url} must have exactly one h1");
        }
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('images/og-default.jpg', $home);
        $this->assertStringContainsString('og:image:width" content="1200"', $home);
        $this->assertFileExists(public_path('images/og-default.jpg'));
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('images/og-default.jpg')), 0, 2));
    }

    public function test_page_titles_are_unique_and_mention_the_place(): void
    {
        $titles = collect(['/', '/harga', '/reservasi', '/promo', '/event', '/galeri', '/kontak', '/ruangan/'.$this->vip->slug])
            ->map(function ($url) {
                preg_match('#<title>(.*?)</title>#', $this->get($url)->getContent(), $m);

                return html_entity_decode($m[1]);
            });
        $this->assertSame($titles->count(), $titles->unique()->count(), 'duplicate titles: '.$titles->implode(' / '));
        $titles->each(fn ($t) => $this->assertLessThanOrEqual(65, mb_strlen($t), $t));
        $this->assertTrue($titles->every(fn ($t) => str_contains($t, 'Rawalo') || str_contains($t, 'Banyumas') || str_contains($t, 'DM Karaoke')));
    }

    public function test_business_data_has_an_id_and_inner_pages_have_breadcrumbs(): void
    {
        $home = $this->schemas('/');
        $this->assertStringEndsWith('#business', $home['EntertainmentBusiness']['@id']);

        $crumbs = $this->schemas('/harga')['BreadcrumbList']['itemListElement'];
        $this->assertSame(['Beranda', 'Harga'], array_column($crumbs, 'name'));
        $this->assertSame([1, 2], array_column($crumbs, 'position'));
        $this->assertArrayNotHasKey('BreadcrumbList', $home);
    }

    public function test_event_pages_describe_the_event_for_search_results(): void
    {
        $this->event();
        $e = $this->schemas('/event/malam-akustik');
        $this->assertSame('2026-11-13T19:00:00+07:00', $e['Event']['startDate']);
        $this->assertSame('2026-11-14T00:00:00+07:00', $e['Event']['endDate']); // 00.00 is after midnight
        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $e['Event']['eventAttendanceMode']);
        $this->assertSame('Rawalo', $e['Event']['location']['address']['addressLocality']);
        $this->assertSame(['Beranda', 'Event', 'Malam Akustik'], array_column($e['BreadcrumbList']['itemListElement'], 'name'));

        $this->event(['title' => 'Siang', 'slug' => 'siang', 'start_time' => '13.00', 'end_time' => '22.00']);
        $this->assertSame('2026-11-13T22:00:00+07:00', $this->schemas('/event/siang')['Event']['endDate']);
    }

    public function test_room_pages_expose_a_priced_service(): void
    {
        $s = $this->schemas('/ruangan/'.$this->vip->slug)['Service'];
        $this->assertSame('VIP Room', $s['name']);
        $this->assertSame(60000, $s['offers']['price']);
        $this->assertSame('IDR', $s['offers']['priceCurrency']);
        $this->assertStringEndsWith('#business', $s['provider']['@id']);

        $this->assertSame(2600000, $this->schemas('/ruangan/'.$this->master->slug)['Service']['offers']['price']);
        $desc = html_entity_decode((string) preg_replace('/.*name="description" content="([^"]*)".*/s', '$1', $this->get('/ruangan/'.$this->vip->slug)->getContent()));
        $this->assertStringContainsString('Kapasitas hingga 10 orang', $desc);
        $this->assertStringContainsString('Rp 60.000 per jam', $desc);
        $this->assertStringNotContainsString('…', $desc);
    }

    public function test_sitemap_has_lastmod_and_images(): void
    {
        $this->event();
        GalleryItem::create(['caption' => 'Satu', 'category' => 'Area', 'type' => 'image', 'image' => 'gallery/satu.webp', 'sort' => 0, 'published' => true]);
        GalleryItem::create(['caption' => 'Dua', 'category' => 'Area', 'type' => 'image', 'image' => 'gallery/dua.webp', 'sort' => 1, 'published' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $byLoc = [];
        foreach ($doc->url as $u) {
            $byLoc[(string) $u->loc] = $u;
        }
        $this->assertArrayHasKey(route('home'), $byLoc);
        $this->assertSame('2026-11-01', (string) $byLoc[route('harga')]->lastmod);
        $this->assertSame('', (string) ($byLoc[route('kontak')]->lastmod ?? ''));
        $this->assertStringContainsString('gallery/satu.webp', $xml);
        $this->assertStringNotContainsString('gallery/dua.webp', $xml); // unpublished
        $this->assertArrayHasKey(route('ruangan.show', $this->vip->slug), $byLoc);
        $this->assertArrayHasKey(route('event.show', 'malam-akustik'), $byLoc);
        $this->assertArrayNotHasKey(route('cek-status'), $byLoc);
    }

    public function test_robots_blocks_private_areas_and_lists_the_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        foreach (['Disallow: /admin', 'Disallow: /cek-status', 'Disallow: /reservasi/harga', 'Sitemap: '.route('sitemap')] as $line) {
            $this->assertStringContainsString($line, $body);
        }
        $this->assertStringNotContainsString('Disallow: /ruangan', $body);
        $this->assertStringNotContainsString('Disallow: /uploads', $body);
    }

    public function test_html_is_gzipped_only_when_the_browser_accepts_it(): void
    {
        $plain = $this->get('/')->assertOk();
        $this->assertNull($plain->headers->get('Content-Encoding'));

        $zipped = $this->withHeader('Accept-Encoding', 'gzip, deflate, br')->get('/')->assertOk();
        $this->assertSame('gzip', $zipped->headers->get('Content-Encoding'));
        $this->assertStringContainsString('Accept-Encoding', $zipped->headers->get('Vary'));
        $body = $zipped->baseResponse->getContent();
        $this->assertSame((string) strlen($body), $zipped->headers->get('Content-Length'));
        $this->assertSame($plain->getContent(), gzdecode($body));
        $this->assertLessThan(strlen($plain->getContent()), strlen($body));

        // tiny responses are left alone
        $this->assertNull($this->withHeader('Accept-Encoding', 'gzip')->get('/robots.txt')->headers->get('Content-Encoding'));
    }

    public function test_unknown_pages_get_a_helpful_noindex_404(): void
    {
        $page = $this->get('/halaman-tidak-ada')->assertNotFound();
        $page->assertSee('Halaman <em>tidak ditemukan</em>', false)->assertSee('noindex, nofollow', false)
            ->assertSee(route('harga'), false)->assertSee(route('reservasi'), false);
        $this->get('/ruangan/tidak-ada')->assertNotFound()->assertSee('noindex, nofollow', false);
    }

    public function test_private_pages_are_noindex_and_search_console_code_is_optional(): void
    {
        $this->get('/cek-status')->assertSee('noindex, nofollow', false);
        $this->get('/')->assertDontSee('google-site-verification', false)->assertSee('index, follow', false);

        config(['dmk.seo.google_verification' => 'abc123', 'dmk.seo.bing_verification' => 'def456']);
        $this->get('/')->assertSee('<meta name="google-site-verification" content="abc123">', false)->assertSee('<meta name="msvalidate.01" content="def456">', false);
    }
}
