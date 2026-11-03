<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-01 10:00:00');
        $this->seedVenue();
    }

    public function test_footer_links_to_all_social_accounts(): void
    {
        $page = $this->get('/')->assertOk();
        $page->assertSee('href="https://instagram.com/DmKaraokeofficial"', false)
            ->assertSee('href="https://www.tiktok.com/@dm.karaoke.cafe4"', false)
            ->assertSee('href="https://www.facebook.com/share/14vSn3oSKq6/"', false)
            ->assertSee('TikTok &#64;dm.karaoke.cafe4', false)->assertSee('Facebook DM Karaoke &amp; Cafe', false);
        // also exposed to search engines
        $page->assertSee('"https://www.tiktok.com/@dm.karaoke.cafe4"', false)->assertSee('"https://www.facebook.com/share/14vSn3oSKq6/"', false);
    }

    /** Component attributes are escaped by the component, so writing "&amp;" in them shows up literally. */
    public function test_public_pages_do_not_double_escape_ampersands(): void
    {
        foreach (['/', '/harga', '/promo', '/event', '/galeri', '/kontak', '/reservasi', '/cek-status', '/ruangan/'.$this->vip->slug] as $url) {
            $this->get($url)->assertOk()->assertDontSee('&amp;amp;', false);
        }
        $this->get('/reservasi')->assertSee('Jadwal &amp; Reservasi', false);
        $this->get('/kontak')->assertSee('Pesan &amp; Saran', false);
    }
}
