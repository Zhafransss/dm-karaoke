<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * `php artisan migrate:fresh --seed` gives the production base data;
     * demo content (promo, event, galeri, reservasi contoh) is added outside production.
     */
    public function run(): void
    {
        foreach (['rooms', 'events', 'gallery'] as $dir) {
            File::deleteDirectory(public_path("uploads/{$dir}"));
        }

        $this->call(CoreSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
