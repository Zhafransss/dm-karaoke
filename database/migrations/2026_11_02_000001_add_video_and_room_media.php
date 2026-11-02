<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gallery items are either an image (image column) or a video (video column).
        Schema::table('gallery', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
            $table->string('type', 10)->default('image')->after('category');
            $table->string('video')->nullable()->after('image');
        });

        // Extra photos and videos shown on the public room detail page.
        Schema::create('room_type_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('image');
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_media');
        Schema::table('gallery', function (Blueprint $table) {
            $table->dropColumn(['type', 'video']);
        });
    }
};
