<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->unsignedInteger('deposit')->default(0)->after('rate_night'); // Rp, 0 = not set
            $table->boolean('prepaid')->default(false)->after('deposit');          // must be paid in full up front, no deposit
        });

        // Master Room is paid in full up front. Every other type inherits today's single deposit.
        DB::table('room_types')->where('slug', 'master-room')->update(['prepaid' => true]);

        $row = DB::table('settings')->where('key', 'deposit')->first();
        $amount = (int) preg_replace('/\D/', '', (string) json_decode($row->value ?? '""', true));
        if ($amount > 0) {
            DB::table('room_types')->where('prepaid', false)->where('rentable', true)->update(['deposit' => $amount]);
        }

        // Stored WhatsApp templates: say where/when the guest pays per room type via {pembayaran}.
        foreach (['tpl_confirm' => ['(dibayar di tempat)' => '({pembayaran})'], 'tpl_guest' => ['Estimasi total: {total}' => 'Estimasi total: {total} ({pembayaran})']] as $key => $map) {
            $stored = DB::table('settings')->where('key', $key)->first();
            $text = $stored ? json_decode($stored->value, true) : null;
            if (is_string($text) && ! str_contains($text, '{pembayaran}') && ($new = strtr($text, $map)) !== $text) {
                DB::table('settings')->where('key', $key)->update(['value' => json_encode($new)]);
            }
        }
        Settings::flush();
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['deposit', 'prepaid']);
        });
        Settings::flush();
    }
};
