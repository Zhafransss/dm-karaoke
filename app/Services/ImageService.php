<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Converts uploads to WebP in two sizes (NFR-01, NFR-05). Files are kept under
 * public/uploads so no storage symlink is needed on shared hosting.
 * Stored path "gallery/abc.webp" also has a small variant "gallery/abc-sm.webp".
 */
class ImageService
{
    private const LARGE = 1600;

    private const SMALL = 640;

    public function store(UploadedFile|string $file, string $dir): string
    {
        $manager = new ImageManager(new Driver);
        $source = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $name = Str::random(24);
        $base = public_path("uploads/{$dir}");
        File::ensureDirectoryExists($base);

        $manager->read($source)->scaleDown(self::LARGE, self::LARGE)->toWebp(80)->save("{$base}/{$name}.webp");
        $manager->read($source)->scaleDown(self::SMALL, self::SMALL)->toWebp(78)->save("{$base}/{$name}-sm.webp");

        return "{$dir}/{$name}.webp";
    }

    public function delete(?string $path): void
    {
        if (! $path || ! str_ends_with($path, '.webp')) {
            return;
        }
        File::delete([public_path("uploads/{$path}"), public_path('uploads/'.preg_replace('/\.webp$/', '-sm.webp', $path))]);
    }
}
