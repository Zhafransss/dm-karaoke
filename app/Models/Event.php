<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = ['title', 'slug', 'body', 'image', 'date', 'start_time', 'end_time', 'featured', 'published'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'featured' => 'boolean', 'published' => 'boolean'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    public function excerpt(int $len = 110): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', (string) $this->body), $len, '…', preserveWords: true);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = Str::limit($base, 160, '');
        $n = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
