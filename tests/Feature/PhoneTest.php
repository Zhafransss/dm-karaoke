<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Phone;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

/** Numbers pasted from contacts/WhatsApp carry invisible characters; list parsing and settings validation. */
class PhoneTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    // "+62 851-9002-4048" as copied from an iPhone contact: directional marks, no-break spaces, non-breaking hyphens.
    private const PASTED = "\u{202A}+62\u{00A0}851\u{2011}9002\u{2011}4048\u{202C}";

    public function test_pasted_numbers_with_invisible_characters_are_valid(): void
    {
        $this->assertTrue(Phone::valid(self::PASTED));
        $this->assertSame('+6285190024048', Phone::normalize(self::PASTED));
        $this->assertSame('6285190024048', Phone::toWa(self::PASTED));
        $this->assertTrue(Phone::valid("0822\u{00A0}2041\u{00A0}3723"));
        $this->assertTrue(Phone::valid('0822-2041-3723'));
        $this->assertFalse(Phone::valid('12345'));
        $this->assertFalse(Phone::valid('0822abc41372'));
    }

    public function test_list_accepts_commas_semicolons_slashes_newlines_and_spaces(): void
    {
        $this->assertSame(['0822', '0812'], Phone::list('0822, 0812'));
        $this->assertSame(['0822', '0812', '0813'], Phone::list("0822;0812\n0813"));
        $this->assertSame(['082220413723', '081234567890'], Phone::list('082220413723 081234567890'));
        $this->assertSame(['082220413723', '081234567890'], Phone::list('082220413723 / 081234567890'));
        // One number written with spaces stays one number
        $this->assertSame(['0822 2041 3723'], Phone::list('0822 2041 3723'));
    }

    private function settingsForm(string $admin): array
    {
        return ['open' => 14, 'close' => 4, 'day_end' => 19, 'max_days' => 30, 'wa_display' => '0822-2041-3723', 'wa_number' => '082220413723', 'wa_admin' => $admin];
    }

    public function test_settings_save_a_pasted_admin_number(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->settingsForm(self::PASTED))
            ->assertRedirect(route('admin.pengaturan'))->assertSessionHasNoErrors();
        $this->assertSame('+6285190024048', Settings::get('wa_admin'));

        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->settingsForm('082220413723 081234567890'))->assertSessionHasNoErrors();
        $this->assertSame('082220413723,081234567890', Settings::get('wa_admin'));
    }

    public function test_settings_save_several_pasted_numbers_and_notify_each(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $other = "\u{202A}+62\u{00A0}812\u{2011}3456\u{2011}7890\u{202C}";
        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->settingsForm(self::PASTED.', '.$other.',082220413723'))
            ->assertSessionHasNoErrors();
        $this->assertSame('+6285190024048,+6281234567890,082220413723', Settings::get('wa_admin'));
        $this->assertSame(['+6285190024048', '+6281234567890', '082220413723'], Phone::list(Settings::get('wa_admin')));

        // exactly 10 is fine, 11 is refused
        $ten = collect(range(1, 10))->map(fn ($i) => '08123456'.str_pad((string) $i, 4, '0', STR_PAD_LEFT))->join(', ');
        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->settingsForm($ten))->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->settingsForm($ten.', 081299990000'))->assertSessionHasErrors('wa_admin');
    }

    public function test_settings_error_names_the_invalid_number(): void
    {
        $admin = User::factory()->role('super_admin')->create();
        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $this->settingsForm('082220413723, 12345'))
            ->assertSessionHasErrors('wa_admin');
        $this->assertStringContainsString('Nomor tidak valid: 12345.', session('errors')->first('wa_admin'));
    }
}
