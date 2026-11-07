<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\WaLog;
use App\Services\BookingException;
use App\Services\ReservationService;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\OpHours;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

/** SRS §9 functional and integration tests T-20 .. T-30. */
class ReservationFlowTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    private const DATE = '2026-11-10';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-01 10:00:00');
        $this->seedVenue();
        RateLimiter::clear('reserve:127.0.0.1');
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'room_type_id' => $this->vip->id, 'package_id' => null, 'date' => self::DATE, 'start_hour' => 20, 'hours' => 2,
            'lc' => 0, 'name' => 'Tamu Uji', 'phone' => '081234567890', 'pax' => 4, 'note' => null,
        ], $over);
    }

    private function book(array $over = []): Reservation
    {
        app()->forgetScopedInstances();
        $in = $this->payload($over);

        return app(ReservationService::class)->create([...$in, 'lc' => (bool) $in['lc']], 'online', null, '127.0.0.1');
    }

    public function test_t20_last_unit_cannot_be_double_booked(): void
    {
        foreach (range(1, 4) as $i) {
            $this->book(['name' => "Tamu {$i}"]);
        }
        $this->book(['name' => 'Tamu 5']); // last VIP unit
        try {
            $this->book(['name' => 'Tamu 6']);
            $this->fail('Expected a conflict');
        } catch (BookingException $e) {
            $this->assertTrue($e->conflict);
        }
        $this->assertSame(5, Reservation::count());
        $this->assertSame(5, Reservation::distinct('room_id')->count('room_id'));
    }

    public function test_t21_full_hour_limits_duration_and_grid(): void
    {
        foreach (range(1, 3) as $i) {
            $this->book(['room_type_id' => $this->std->id, 'start_hour' => 22, 'hours' => 1, 'name' => "S{$i}"]);
        }
        $res = $this->getJson('/reservasi/ketersediaan?date='.self::DATE."&tipe={$this->std->id}&mode=sewa")->assertOk();
        $row = collect($res->json('grid'))->firstWhere('id', $this->std->id);
        $this->assertFalse(collect($row['cells'])->firstWhere('hour', 22)['open']);
        $this->assertTrue(collect($row['cells'])->firstWhere('hour', 21)['open']);
        $this->assertSame(2, collect($res->json('starts'))->firstWhere('hour', 20)['max']);

        // The full hour cannot be booked through the public form either.
        $this->post('/reservasi', $this->payload(['room_type_id' => $this->std->id, 'start_hour' => 22, 'hours' => 1]))
            ->assertSessionHasErrors('booking');
    }

    public function test_t21b_public_grid_hides_unit_counts(): void
    {
        $this->book(['room_type_id' => $this->std->id, 'start_hour' => 20, 'hours' => 1]);
        $res = $this->getJson('/reservasi/ketersediaan?date='.self::DATE."&tipe={$this->std->id}&mode=sewa")->assertOk();
        $row = collect($res->json('grid'))->firstWhere('id', $this->std->id);

        $this->assertArrayNotHasKey('total', $row);
        $this->assertStringNotContainsString('ruang', $row['sub']);
        $this->assertSame(['hour', 'label', 'early', 'past', 'open', 'promo'], array_keys($row['cells'][0]));
    }

    public function test_t21c_grid_promo_marks_follow_selected_package(): void
    {
        $kawai = $this->pkgNamed('Kawai VIP DM II');
        $range = ['date_start' => '2026-11-01', 'date_end' => '2026-11-30'];
        $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, ...$range], [$this->std->rentKey(), $this->vip->rentKey()]);
        $this->promo(['name' => 'Paket Siang', 'kind' => 'persen', 'value' => 10, 'time_start' => 14, 'time_end' => 17, ...$range], [$kawai->pkgKey()]);

        $marks = function (string $mode, int $typeId) {
            $grid = $this->getJson('/reservasi/ketersediaan?date='.self::DATE."&tipe={$typeId}&mode={$mode}")->assertOk()->json('grid');

            return collect($grid)->mapWithKeys(fn ($r) => [$r['id'] => collect($r['cells'])->where('promo', true)->pluck('hour')->all()]);
        };

        // Rental: the rental promo shows on both rentable rows.
        $rent = $marks('sewa', $this->vip->id);
        $this->assertContains(20, $rent[$this->std->id]);
        $this->assertContains(20, $rent[$this->vip->id]);

        // Package: rental-only "bayar X" disappears; only the package's own promo, its row, its window.
        $pkg = $marks((string) $kawai->id, $this->vip->id);
        $this->assertSame([14, 15, 16], $pkg[$this->vip->id]);
        $this->assertSame([], $pkg[$this->std->id]);
        $this->assertSame([], $pkg[$this->master->id]);
    }

    public function test_t21d_grid_lists_promos_running_on_chosen_date(): void
    {
        $this->promo(['name' => 'Jalan', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-05', 'date_end' => '2026-11-15'], [$this->vip->rentKey()]);
        $this->promo(['name' => 'Nanti', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-20', 'date_end' => '2026-11-25'], [$this->vip->rentKey()]);
        $this->promo(['name' => 'Mati', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-05', 'date_end' => '2026-11-15', 'active' => false], [$this->std->rentKey()]);

        $promos = $this->getJson('/reservasi/ketersediaan?date='.self::DATE."&tipe={$this->vip->id}&mode=sewa")->assertOk()->json('promos');

        $this->assertSame(['Jalan'], array_column($promos, 'name'));
        $this->assertSame([$this->vip->rentKey()], $promos[0]['targets']);
        $this->assertSame('Sewa VIP Room', $promos[0]['for']);
    }

    public function test_t22_fourth_reservation_from_same_ip_is_rejected(): void
    {
        foreach ([14, 16, 18] as $h) {
            $this->post('/reservasi', $this->payload(['start_hour' => $h]))->assertRedirect();
        }
        $this->post('/reservasi', $this->payload(['start_hour' => 22]))->assertSessionHasErrors('booking');
        $this->assertSame(3, Reservation::count());
    }

    public function test_t23_after_midnight_belongs_to_previous_operational_day(): void
    {
        $this->travelTo('2026-11-11 01:00:00');
        $this->assertSame('2026-11-10', OpHours::today());

        $r = $this->book(['date' => '2026-11-10', 'start_hour' => 26, 'hours' => 1]);
        $this->assertSame('2026-11-10', $r->ymd());
        $this->assertSame('02.00–03.00', $r->timeLabel());
    }

    public function test_t24_cancel_frees_the_unit_and_is_logged(): void
    {
        $admin = User::factory()->create();
        foreach (range(1, 5) as $i) {
            $last = $this->book(['name' => "T{$i}"]);
        }
        app(ReservationService::class)->changeStatus($last, 'dibatalkan', $admin);
        $this->assertDatabaseHas('reservation_logs', ['reservation_id' => $last->id, 'from_status' => 'menunggu', 'to_status' => 'dibatalkan', 'user_id' => $admin->id]);
        $this->assertInstanceOf(Reservation::class, $this->book(['name' => 'Pengganti']));
    }

    public function test_admin_pay_x_promo_accepts_only_packages_that_fit(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $kawai = $this->pkgNamed('Kawai VIP DM II');
        $fun = $this->pkgNamed('Fun Package');
        $form = ['name' => '2 bayar 1', 'kind' => 'bayar_x', 'x' => 2, 'y' => 1, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'active' => 1];

        $this->actingAs($admin)->post('/admin/promo', [...$form, 'targets' => [$this->vip->rentKey(), $fun->pkgKey()]])
            ->assertSessionHasErrors('targets');

        $this->actingAs($admin)->post('/admin/promo', [...$form, 'targets' => [$this->vip->rentKey(), $kawai->pkgKey()]])
            ->assertRedirect('/admin/promo');
        $this->assertEqualsCanonicalizing([$this->vip->rentKey(), $kawai->pkgKey()], \App\Models\Promo::firstOrFail()->targets());
    }

    public function test_t25_role_without_permission_gets_403(): void
    {
        $konten = User::factory()->role('admin_konten')->create();
        $this->actingAs($konten)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($konten)->get('/admin/reservasi')->assertForbidden();
        $this->actingAs($konten)->get('/admin/promo')->assertOk();

        $pesan = User::factory()->role('admin_pesan')->create();
        $this->actingAs($pesan)->get('/admin/pengaturan')->assertForbidden();
        $this->actingAs($pesan)->get('/admin/pesan')->assertOk();
    }

    public function test_t26_gateway_failure_keeps_reservation_and_retries(): void
    {
        $failing = new class implements WhatsAppGateway
        {
            public int $calls = 0;

            public function send(string $to, string $message): array
            {
                $this->calls++;

                return ['ok' => false, 'status' => 'failed', 'response' => 'timeout'];
            }
        };
        $this->app->instance(WhatsAppGateway::class, $failing);

        $this->post('/reservasi', $this->payload())->assertRedirect();
        $r = Reservation::firstOrFail();
        $this->assertSame('menunggu', $r->status);
        $this->assertSame(2, WaLog::where('reservation_id', $r->id)->where('status', 'failed')->count());

        for ($i = 0; $i < 4; $i++) {
            $this->travel(6)->minutes();
            app(WhatsAppService::class)->retryDue();
        }
        $this->assertSame(3, (int) WaLog::max('attempts'));
        $this->assertSame(6, $failing->calls); // 2 messages × 3 attempts
    }

    public function test_t27_status_check_requires_matching_phone(): void
    {
        $r = $this->book();
        $this->post('/cek-status', ['code' => $r->code, 'phone' => '089999999999'])->assertOk()->assertSee('Reservasi tidak ditemukan')->assertDontSee('Tamu Uji');
        $this->post('/cek-status', ['code' => strtolower($r->code), 'phone' => '+62 812-3456-7890'])->assertOk()->assertSee('Tamu Uji');
    }

    public function test_t28_quoted_price_equals_stored_price(): void
    {
        $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30'], [$this->vip->rentKey()]);
        $quote = $this->postJson('/reservasi/harga', ['room_type_id' => $this->vip->id, 'date' => self::DATE, 'start_hour' => 18, 'hours' => 4, 'lc' => false])->assertOk();

        $this->post('/reservasi', $this->payload(['start_hour' => 18, 'hours' => 4]))->assertRedirect();
        $this->assertSame($quote->json('total_raw'), Reservation::firstOrFail()->total);
        // 18.00 day 60k + 3 × 120k night; "3 bayar 2" frees the cheapest paid hour (60k).
        $this->assertSame(60000 + 120000 * 3 - 60000, Reservation::firstOrFail()->total);
    }

    public function test_t28b_guest_picks_one_of_several_promos_and_that_one_is_stored(): void
    {
        $range = ['date_start' => '2026-11-01', 'date_end' => '2026-11-30'];
        $three = $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, ...$range], [$this->vip->rentKey()]);
        $four = $this->promo(['name' => '4 bayar 2', 'kind' => 'bayar_x', 'x' => 4, 'y' => 2, ...$range], [$this->vip->rentKey()]);
        $ask = ['room_type_id' => $this->vip->id, 'date' => self::DATE, 'start_hour' => 18, 'hours' => 4, 'lc' => false];

        $auto = $this->postJson('/reservasi/harga', $ask)->assertOk();
        $this->assertSame($four->id, $auto->json('promo_id'));
        $this->assertSame([$four->id, $three->id], array_column($auto->json('promos'), 'id'));

        $picked = $this->postJson('/reservasi/harga', [...$ask, 'promo_id' => $three->id])->assertOk();
        $this->assertSame($three->id, $picked->json('promo_id'));

        $this->post('/reservasi', $this->payload(['start_hour' => 18, 'hours' => 4, 'promo_id' => $three->id]))->assertRedirect();
        $r = Reservation::firstOrFail();
        $this->assertSame($three->id, $r->promo_id);
        $this->assertSame($picked->json('total_raw'), $r->total);
    }

    public function test_admin_can_save_and_activate_overlapping_promos(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, 'date_start' => '2026-11-01', 'date_end' => '2026-11-18'], [$this->std->rentKey(), $this->vip->rentKey()]);
        $form = ['name' => '4 bayar 2', 'kind' => 'bayar_x', 'x' => 4, 'y' => 2, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'targets' => [$this->vip->rentKey()]];

        $this->actingAs($admin)->post('/admin/promo', [...$form, 'active' => 1])->assertRedirect('/admin/promo')->assertSessionHasNoErrors();
        $this->actingAs($admin)->postJson('/admin/promo/pratinjau', [...$form, 'active' => true])
            ->assertOk()->assertJsonPath('overlaps.0.name', '3 bayar 2');

        $draft = \App\Models\Promo::create(['name' => 'Draf', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'active' => false]);
        $draft->syncTargets([$this->vip->rentKey()]);
        $this->actingAs($admin)->post("/admin/promo/{$draft->id}/toggle")->assertSessionHasNoErrors();
        $this->assertTrue($draft->fresh()->active);
    }

    public function test_t29_rate_change_does_not_touch_existing_reservation(): void
    {
        $r = $this->book();
        $this->vip->update(['rate_night' => 150000]);
        $this->assertSame(240000, $r->fresh()->total);
    }

    public function test_t30_master_room_capacity_never_shown_publicly(): void
    {
        $this->master->update(['cap' => 15]);
        foreach (['/', '/harga', '/reservasi'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('15 orang', $html, $url);
        }
    }

    public function test_family_package_rejected_when_not_starting_at_15_via_http(): void
    {
        $fun = $this->pkgNamed('Fun Package');
        $this->post('/reservasi', $this->payload(['room_type_id' => $this->std->id, 'package_id' => $fun->id, 'start_hour' => 16]))
            ->assertSessionHasErrors('booking');
        $this->post('/reservasi', $this->payload(['room_type_id' => $this->std->id, 'package_id' => $fun->id, 'start_hour' => 15]))
            ->assertRedirect();
        $this->assertSame(115000, Reservation::firstOrFail()->total);
    }

    public function test_settings_token_is_encrypted_at_rest(): void
    {
        Settings::set(['wa_token' => 'rahasia']);
        $raw = json_decode(\DB::table('settings')->where('key', 'wa_token')->value('value'));
        $this->assertNotSame('rahasia', $raw);
        $this->assertSame('rahasia', Settings::get('wa_token'));
    }
}
