<?php

namespace Tests\Feature;

use App\Models\Promo;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Support\OpHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

/** SRS §9 test cases T-01 .. T-15. */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    private const DATE = '2026-11-10';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-01 10:00:00');
        $this->seedVenue();
    }

    private function price($type, $pkg, int $hour, int $hours, bool $lc = false, string $date = self::DATE): array
    {
        app()->forgetScopedInstances();

        return app(PricingService::class)->calculate($type, $pkg, $date, $hour, $hours, $lc);
    }

    public function test_t01_vip_day_without_lc(): void
    {
        $this->assertSame(120000, $this->price($this->vip, null, 15, 2)['total']);
    }

    public function test_t02_vip_day_with_lc_is_free(): void
    {
        $this->assertSame(0, $this->price($this->vip, null, 15, 2, true)['total']);
    }

    public function test_t03_vip_crossing_day_end_without_lc(): void
    {
        $q = $this->price($this->vip, null, 18, 3);
        $this->assertSame(300000, $q['total']);
        $this->assertCount(2, $q['lines']);
    }

    public function test_t04_vip_crossing_day_end_with_lc(): void
    {
        $this->assertSame(240000, $this->price($this->vip, null, 18, 3, true)['total']);
    }

    public function test_t05_standard_night_same_with_or_without_lc(): void
    {
        $this->assertSame(160000, $this->price($this->std, null, 20, 2)['total']);
        $this->assertSame(160000, $this->price($this->std, null, 20, 2, true)['total']);
    }

    public function test_t06_standard_past_midnight(): void
    {
        $q = $this->price($this->std, null, 23, 3);
        $this->assertSame(240000, $q['total']);
        $this->assertSame('01.00', hl(25));
        $this->assertTrue(OpHours::isEarlyMorning(24));
    }

    public function test_t07_family_package_price(): void
    {
        $q = $this->price($this->std, $this->pkgNamed('Fun Package'), 15, 1);
        $this->assertSame(115000, $q['total']);
        $this->assertSame(3, $q['hours']);
    }

    public function test_t08_family_package_must_start_at_15(): void
    {
        $svc = app(AvailabilityService::class);
        $fun = $this->pkgNamed('Fun Package');
        $this->assertNotNull($svc->ruleError($this->std, $fun, self::DATE, 14, 3));
        $this->assertNotNull($svc->ruleError($this->std, $fun, self::DATE, 16, 3));
        $this->assertNull($svc->ruleError($this->std, $fun, self::DATE, 15, 3));
    }

    public function test_t09_master_room_without_package_is_rejected(): void
    {
        $this->assertNotNull(app(AvailabilityService::class)->ruleError($this->master, null, self::DATE, 20, 3));
        $this->expectException(\InvalidArgumentException::class);
        $this->price($this->master, null, 20, 3);
    }

    public function test_t10_percent_promo_on_rate_after_day_night(): void
    {
        $this->promo(['name' => 'Diskon 10', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'time_start' => 18, 'time_end' => 21], [$this->vip->rentKey()]);
        // 18.00 day 60k + 19.00, 20.00 night 120k each = 300k, all inside the window → 30k off
        $q = $this->price($this->vip, null, 18, 3);
        $this->assertSame(30000, $q['discount']);
        $this->assertSame(270000, $q['total']);

        // Only hours inside the window are discounted: 20.00–22.00 → only 20.00 is eligible
        $q = $this->price($this->vip, null, 20, 2);
        $this->assertSame(12000, $q['discount']);
        $this->assertSame('1 dari 2 jam', $q['promo_note']);
    }

    public function test_t10b_package_promo_applies_when_start_in_window(): void
    {
        $kawai = $this->pkgNamed('Kawai VIP DM II');
        $this->promo(['name' => 'Paket Siang', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'time_start' => 14, 'time_end' => 17], [$kawai->pkgKey()]);
        $this->assertSame(103000, $this->price($this->vip, $kawai, 16, 3)['discount']);
        $this->assertSame(0, $this->price($this->vip, $kawai, 17, 3)['discount']);
    }

    public function test_t11_pay_x_promo_night(): void
    {
        $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30'], [$this->vip->rentKey()]);
        $q = $this->price($this->vip, null, 20, 3);
        $this->assertSame(360000, $q['base']);
        $this->assertSame(240000, $q['total']);
    }

    public function test_t11b_pay_x_on_package_frees_room_hours_at_hourly_rate(): void
    {
        $two = $this->pkg($this->std, 'Anggur Uji', 525000, ['hours' => 2]);
        $kawai = $this->pkgNamed('Kawai VIP DM II'); // 3 hours, VIP
        $range = ['date_start' => '2026-11-01', 'date_end' => '2026-11-30'];
        $this->promo(['name' => '2 bayar 1', 'kind' => 'bayar_x', 'x' => 2, 'y' => 1, ...$range], [$two->pkgKey(), $kawai->pkgKey()]);

        // Day hours are valued at the rate without LC (LC is part of the package).
        $q = $this->price($this->std, $two, 16, 2);
        $this->assertSame(40000, $q['discount']);
        $this->assertSame(485000, $q['total']);
        $this->assertSame('2 jam bayar 1 jam', $q['promo_note']);
        $this->assertSame(40000, $this->price($this->std, $two, 18, 2)['discount']); // 18 day + 19 night: cheapest is free
        $this->assertSame(80000, $this->price($this->std, $two, 20, 2)['discount']);
        // A 3-hour package still gets one free hour (once per transaction).
        $this->assertSame(120000, $this->price($this->vip, $kawai, 20, 3)['discount']);
    }

    public function test_t11c_pay_x_skips_packages_that_do_not_fit(): void
    {
        $two = $this->pkg($this->std, 'Anggur Uji', 525000, ['hours' => 2]);
        $fun = $this->pkgNamed('Fun Package');
        $vibe = $this->pkgNamed('Vibe');
        $range = ['date_start' => '2026-11-01', 'date_end' => '2026-11-30'];

        // Shorter than X.
        $three = $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, ...$range], [$two->pkgKey()]);
        $this->assertNull($this->price($this->std, $two, 20, 2)['promo_id']);
        $three->delete();

        // Not enough package hours inside the window (only 15.00 of 15.00–17.00 is inside 14–16).
        $p = $this->promo(['name' => '2 bayar 1', 'kind' => 'bayar_x', 'x' => 2, 'y' => 1, 'time_start' => 14, 'time_end' => 16, ...$range], [$two->pkgKey()]);
        $this->assertNull($this->price($this->std, $two, 15, 2)['promo_id']);
        $this->assertSame(40000, $this->price($this->std, $two, 14, 2)['discount']);

        // Family (room already free) and package-only Master Room never qualify.
        $this->assertFalse($p->packageEligible($fun));
        $this->assertFalse($p->packageEligible($vibe));
    }

    public function test_t12_pay_x_does_not_count_lc_free_hours(): void
    {
        $this->promo(['name' => '3 bayar 2', 'kind' => 'bayar_x', 'x' => 3, 'y' => 2, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30'], [$this->vip->rentKey()]);
        $q = $this->price($this->vip, null, 18, 3, true);
        $this->assertNull($q['promo_id']);
        $this->assertSame(240000, $q['total']);
    }

    public function test_t13_only_one_promo_and_window_containing_start_wins(): void
    {
        $early = $this->promo(['name' => 'Sore', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'time_start' => 14, 'time_end' => 17], [$this->vip->rentKey()]);
        $late = $this->promo(['name' => 'Malam', 'kind' => 'persen', 'value' => 20, 'date_start' => '2026-11-01', 'date_end' => '2026-11-30', 'time_start' => 20, 'time_end' => 23], [$this->vip->rentKey()]);

        $q = $this->price($this->vip, null, 16, 5); // 16..20, touches both windows
        $this->assertSame($early->id, $q['promo_id']);

        $q = $this->price($this->vip, null, 20, 2);
        $this->assertSame($late->id, $q['promo_id']);
    }

    public function test_t14_overlapping_active_promo_on_same_target_is_detected(): void
    {
        $existing = $this->promo(['name' => 'A', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-01', 'date_end' => '2026-11-15'], [$this->vip->rentKey()]);

        $new = new Promo(['name' => 'B', 'kind' => 'nominal', 'value' => 50000, 'date_start' => '2026-11-10', 'date_end' => '2026-11-20', 'active' => true]);
        $new->pendingTargets = [$this->vip->rentKey(), $this->std->rentKey()];
        $this->assertSame([$existing->id], app(PromoService::class)->conflicts($new)->pluck('id')->all());

        // Same target but hours do not overlap -> allowed
        $existing->update(['time_start' => 14, 'time_end' => 17]);
        $new->time_start = 20;
        $new->time_end = 23;
        $this->assertCount(0, app(PromoService::class)->conflicts($new));

        // Inactive promos never conflict
        $new->active = false;
        $this->assertCount(0, app(PromoService::class)->conflicts($new));
    }

    public function test_t15_visit_date_outside_promo_period(): void
    {
        $this->promo(['name' => 'A', 'kind' => 'persen', 'value' => 10, 'date_start' => '2026-11-01', 'date_end' => '2026-11-05'], [$this->vip->rentKey()]);
        $this->assertSame(0, $this->price($this->vip, null, 20, 2)['discount']);
        $this->assertSame(24000, $this->price($this->vip, null, 20, 2, false, '2026-11-05')['discount']);
    }
}
