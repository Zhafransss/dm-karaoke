<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Photos (converted to WebP by ImageService) and videos (kept as uploaded, no
 * transcoding on shared hosting). Both live under public/uploads/{dir}.
 */
class MediaService
{
    public const VIDEO_EXT = ['mp4', 'webm', 'mov'];

    private const MAX_IMAGE_KB = 8192;

    public function __construct(private ImageService $images) {}

    /** Validation rules for one uploaded photo or video. */
    public static function rules(): array
    {
        return [
            'file', 'mimes:jpg,jpeg,png,webp,'.implode(',', self::VIDEO_EXT), 'max:'.((int) config('dmk.max_video_mb') * 1024),
            fn ($attr, $file, $fail) => ! self::isVideo($file) && $file->getSize() > self::MAX_IMAGE_KB * 1024
                ? $fail('Ukuran foto maksimal 8 MB.') : null,
        ];
    }

    public static function messages(string $field): array
    {
        $mb = (int) config('dmk.max_video_mb');

        return [
            "{$field}.mimes" => 'Format harus JPG, PNG, WebP, MP4, WebM, atau MOV.',
            "{$field}.max" => "Ukuran file maksimal {$mb} MB (foto maksimal 8 MB).",
            "{$field}.uploaded" => "File gagal diunggah. Ukuran video maksimal {$mb} MB dan harus lebih kecil dari batas upload server.",
            "{$field}.file" => 'File tidak valid.',
        ];
    }

    public static function isVideo(UploadedFile $file): bool
    {
        return str_starts_with((string) $file->getMimeType(), 'video/');
    }

    /** @return array{type: string, path: string} */
    public function store(UploadedFile $file, string $dir): array
    {
        if (! self::isVideo($file)) {
            return ['type' => 'image', 'path' => $this->images->store($file, $dir)];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $ext = in_array($ext, self::VIDEO_EXT, true) ? $ext : 'mp4';
        $name = Str::random(24).'.'.$ext;
        $base = public_path("uploads/{$dir}");
        File::ensureDirectoryExists($base);
        $file->move($base, $name);

        return ['type' => 'video', 'path' => "{$dir}/{$name}"];
    }

    public function delete(?string $path, string $type = 'image'): void
    {
        if ($type === 'video') {
            if ($path && preg_match('/\.(mp4|webm|mov)$/', $path)) {
                File::delete(public_path("uploads/{$path}"));
            }

            return;
        }
        $this->images->delete($path);
    }
}
