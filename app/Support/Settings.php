<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Runtime settings editable by Super Admin (FR-ADM-17), cached in the file cache.
 * The gateway token is stored encrypted (NFR-05).
 */
class Settings
{
    private const CACHE_KEY = 'dmk.settings';

    private const ENCRYPTED = ['wa_token'];

    private static ?array $memo = null;

    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return DB::table('settings')->pluck('value', 'key')
                    ->map(fn ($v) => json_decode($v, true))
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });

        foreach (self::ENCRYPTED as $key) {
            if (! empty($stored[$key])) {
                try {
                    $stored[$key] = Crypt::decryptString($stored[$key]);
                } catch (Throwable) {
                    $stored[$key] = '';
                }
            }
        }

        return self::$memo = array_merge(config('dmk.settings_defaults'), $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(array $values): void
    {
        $now = now();
        foreach ($values as $key => $value) {
            if (in_array($key, self::ENCRYPTED, true) && $value !== '' && $value !== null) {
                $value = Crypt::encryptString($value);
            }
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'updated_at' => $now, 'created_at' => $now]
            );
        }
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$memo = null;
    }
}
