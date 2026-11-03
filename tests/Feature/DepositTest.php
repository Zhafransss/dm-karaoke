<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Reservation;
use App\Models\User;
use App\Models\WaLog;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

/** Deposit per room type; Master Room is paid in full up front (information only). */
class DepositTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    private const DATE = '2026-11-10';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-01 10:00:00');
        $this->seedVenue();
    }

    public function test_room_types_describe_their_own_deposit_or_prepayment(): void
    {
        $this->assertSame('Rp 500.000', $this->std->depositLabel());
        $this->assertSame('Rp 750.000', $this->vip->depositLabel());
        $this->assertStringContainsString('Deposit Rp 750.000', $this->vip->depositText());
        $this->assertSame('dibayar di tempat', $this->vip->paymentShort());

        $this->assertTrue($this->master->prepaid);
        $this->assertSame('', $this->master->depositLabel());
        $this->assertSame('Wajib lunas di awal, tanpa deposit.', $this->master->depositText());
        $this->assertSame('wajib dilunasi di awal', $this->master->paymentShort());

        $this->vip->update(['deposit' => 0]);
        $this->assertStringContainsString('diinformasikan admin', $this->vip->fresh()->depositText());
    }

    public function test_price_endpoint_uses_the_deposit_of_the_chosen_room_type(): void
    {
        $lc = fn ($type) => $this->postJson('/reservasi/harga', ['room_type_id' => $type->id, 'date' => self::DATE, 'start_hour' => 15, 'hours' => 2, 'lc' => true])->assertOk();

        $this->assertStringContainsString('Deposit Rp 500.000', $lc($this->std)->json('lc_note'));
        $this->assertStringContainsString('Deposit Rp 750.000', $lc($this->vip)->json('lc_note'));
        $this->assertSame('Deposit Rp 750.000', $lc($this->vip)->json('rows.0.amount'));
        $this->assertSame('', $lc($this->vip)->json('pay_note'));
    }

    public function test_master_room_quote_says_it_must_be_paid_in_full_first(): void
    {
        $pkg = Package::where('name', 'Vibe')->firstOrFail();
        $res = $this->postJson('/reservasi/harga', ['room_type_id' => $this->master->id, 'package_id' => $pkg->id, 'date' => self::DATE, 'start_hour' => 20, 'hours' => 3, 'lc' => false])->assertOk();

        $this->assertStringContainsString('Wajib lunas di awal', $res->json('when'));
        $this->assertStringNotContainsString('Dibayar di tempat', $res->json('when'));
        $this->assertStringContainsString('wajib lunas di awal (tanpa deposit)', $res->json('pay_note'));
        $this->assertSame('', $res->json('lc_note'));
    }

    public function test_whatsapp_messages_state_the_payment_terms_per_room_type(): void
    {
        $book = fn (array $in) => app(ReservationService::class)->create([...$in, 'date' => self::DATE, 'start_hour' => 20, 'lc' => false, 'pax' => 4, 'name' => 'Tamu', 'phone' => '081234567890', 'note' => null], 'online', null, '127.0.0.1');

        $book(['room_type_id' => $this->vip->id, 'package_id' => null, 'hours' => 2]);
        $book(['room_type_id' => $this->master->id, 'package_id' => Package::where('name', 'Vibe')->value('id'), 'hours' => 3]);

        $bodies = WaLog::where('recipient', 'guest')->orderBy('id')->pluck('body');
        $this->assertStringContainsString('(dibayar di tempat)', $bodies[0]);
        $this->assertStringContainsString('(wajib dilunasi di awal)', $bodies[1]);
    }

    public function test_public_pages_show_deposit_per_type_and_the_master_exception(): void
    {
        $prices = $this->get('/harga')->assertOk();
        $prices->assertSee('Deposit Rp 500.000 berupa uang', false)->assertSee('Deposit Rp 750.000 berupa uang', false)
            ->assertSee('Wajib lunas di awal, tanpa deposit.')->assertSee('kecuali ruangan yang wajib lunas di awal');

        $this->get('/ruangan/'.$this->vip->slug)->assertOk()->assertSee('Deposit Rp 750.000');
        $this->get('/ruangan/'.$this->master->slug)->assertOk()->assertSee('Wajib lunas di awal, tanpa deposit.');
    }

    /** Whole path a guest takes: submit the form, land on the success page, check the status. */
    public function test_guest_flow_end_to_end_for_master_and_vip(): void
    {
        RateLimiter::clear('reserve:127.0.0.1');
        $form = fn (array $in) => [...['date' => self::DATE, 'start_hour' => 20, 'hours' => 3, 'lc' => 0, 'name' => 'Tamu Uji', 'phone' => '081234567890', 'pax' => 4, 'note' => null], ...$in];

        // Master Room: package only, paid in full up front
        $this->post('/reservasi', $form(['room_type_id' => $this->master->id, 'package_id' => Package::where('name', 'Vibe')->value('id')]))->assertRedirect();
        $master = Reservation::firstOrFail();
        $this->withSession(['my_reservations' => [$master->code]])->get(route('reservasi.sukses', $master->code))->assertOk()
            ->assertSee('wajib lunas di awal (tanpa deposit)')->assertSee('Wajib lunas di awal, tanpa deposit.')->assertDontSee('Pembayaran dilakukan di tempat');

        // VIP rental with LC at daytime: pays the deposit on site
        $this->post('/reservasi', $form(['room_type_id' => $this->vip->id, 'package_id' => null, 'start_hour' => 15, 'hours' => 2, 'lc' => 1]))->assertRedirect();
        $vip = Reservation::where('room_type_id', $this->vip->id)->firstOrFail();
        $this->withSession(['my_reservations' => [$vip->code]])->get(route('reservasi.sukses', $vip->code))->assertOk()->assertSee('Pembayaran dilakukan di tempat')->assertSee('Deposit Rp 750.000')->assertDontSee('wajib lunas di awal');

        // Status check page once confirmed
        $master->update(['status' => 'dikonfirmasi']);
        $vip->update(['status' => 'dikonfirmasi']);
        $this->post('/cek-status', ['code' => $master->code, 'phone' => '081234567890'])->assertOk()->assertSee('Datang sesuai jadwal ya.')->assertDontSee('pembayaran di tempat');
        $this->post('/cek-status', ['code' => $vip->code, 'phone' => '081234567890'])->assertOk()->assertSee('pembayaran di tempat');
    }

    public function test_admin_sets_deposit_per_type_and_prepaid_clears_the_deposit(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $form = fn ($type, array $over) => [...['name' => $type->name, 'rate_day' => $type->rate_day, 'rate_night' => $type->rate_night ?: 1, 'units' => [['id' => $type->rooms->first()->id, 'name' => $type->rooms->first()->name, 'active' => 1]], 'active' => 1], ...$over];

        $this->actingAs($admin)->put(route('admin.ruangan.update', $this->vip), $form($this->vip, ['deposit' => 1000000, 'rentable' => 1]))->assertSessionHasNoErrors();
        $this->assertSame(1000000, $this->vip->fresh()->deposit);
        $this->assertFalse($this->vip->fresh()->prepaid);

        $this->actingAs($admin)->put(route('admin.ruangan.update', $this->vip), $form($this->vip, ['deposit' => 1000000, 'prepaid' => 1, 'rentable' => 1]))->assertSessionHasNoErrors();
        $this->assertTrue($this->vip->fresh()->prepaid);
        $this->assertSame(0, $this->vip->fresh()->deposit);

        $this->actingAs($admin)->put(route('admin.ruangan.update', $this->std), $form($this->std, ['deposit' => -5, 'rentable' => 1]))->assertSessionHasErrors('deposit');
    }

    public function test_admin_reservation_page_reminds_to_collect_payment_for_prepaid_rooms(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $r = app(ReservationService::class)->create(['room_type_id' => $this->master->id, 'package_id' => Package::where('name', 'Vibe')->value('id'), 'date' => self::DATE, 'start_hour' => 20,
            'hours' => 3, 'lc' => false, 'pax' => 4, 'name' => 'Tamu', 'phone' => '081234567890', 'note' => null], 'online', null, '127.0.0.1');

        $this->actingAs($admin)->get(route('admin.reservasi.show', $r))->assertOk()->assertSee('Pastikan pembayaran sudah diterima sebelum mengkonfirmasi');
    }
}
