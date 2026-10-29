<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Core tables from SRS §5. Money is stored as integer rupiah.
 * Hours use "operational hours" (14..27), see App\Support\OpHours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->json('facilities')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('cap')->nullable();
            $table->boolean('show_cap')->default(true);
            $table->boolean('rentable')->default(true);
            $table->unsignedInteger('rate_day')->default(0);
            $table->unsignedInteger('rate_night')->default(0);
            $table->boolean('featured')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('brand', 80);
            $table->string('name', 100);
            $table->unsignedInteger('price');
            $table->unsignedTinyInteger('hours')->default(3);
            $table->json('items')->nullable();
            $table->boolean('family')->default(false);
            $table->unsignedTinyInteger('start_hour')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('kind', 10); // persen | nominal | bayar_x
            $table->unsignedInteger('value')->default(0);
            $table->unsignedTinyInteger('x')->nullable();
            $table->unsignedTinyInteger('y')->nullable();
            $table->date('date_start');
            $table->date('date_end');
            $table->unsignedTinyInteger('time_start')->nullable();
            $table->unsignedTinyInteger('time_end')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('promo_targets', function (Blueprint $table) {
            $table->foreignId('promo_id')->constrained()->cascadeOnDelete();
            $table->string('target_key', 40); // rent:<room_type_id> | pkg:<package_id>
            $table->primary(['promo_id', 'target_key']);
            $table->index('target_key');
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('package_name', 100)->nullable();
            $table->boolean('lc')->default(false);
            $table->date('date'); // operational date
            $table->unsignedTinyInteger('start_hour');
            $table->unsignedTinyInteger('hours');
            $table->unsignedSmallInteger('pax');
            $table->string('name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 15)->default('menunggu');
            $table->unsignedInteger('base');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');
            $table->unsignedBigInteger('promo_id')->nullable(); // no FK: promo may be deleted, name is snapshotted
            $table->string('promo_name', 100)->nullable();
            $table->string('promo_note', 100)->nullable();
            $table->json('price_lines');
            $table->string('source', 10)->default('online'); // online | admin
            $table->string('ip', 45)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'room_id', 'status']);
            $table->index(['phone', 'code']);
            $table->index('status');
        });

        Schema::create('reservation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 15)->nullable();
            $table->string('to_status', 15);
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('start_hour');
            $table->unsignedTinyInteger('end_hour');
            $table->string('reason', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'date']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 170)->unique();
            $table->text('body')->nullable();
            $table->string('image')->nullable();
            $table->date('date');
            $table->string('start_time', 5)->default('19.00');
            $table->string('end_time', 5)->default('23.00');
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index(['published', 'date']);
        });

        Schema::create('gallery', function (Blueprint $table) {
            $table->id();
            $table->string('caption', 150);
            $table->string('category', 30);
            $table->string('image');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('published')->default(true);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('contact', 120)->nullable(); // WhatsApp or email
            $table->string('kind', 20)->default('Saran');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wa_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 10); // admin | guest
            $table->string('to', 20);
            $table->string('template', 20);
            $table->text('body');
            $table->string('status', 10)->default('pending'); // pending | sent | failed | simulated
            $table->text('response')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('next_try_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_try_at']);
        });
    }

    public function down(): void
    {
        foreach (['wa_logs', 'messages', 'gallery', 'events', 'blocks', 'reservation_logs', 'reservations', 'promo_targets', 'promos', 'packages', 'rooms', 'room_types', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
