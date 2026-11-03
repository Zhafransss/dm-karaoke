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
