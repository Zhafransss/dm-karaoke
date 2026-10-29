<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'facilities', 'image', 'cap', 'show_cap', 'rentable', 'rate_day', 'rate_night', 'featured', 'sort', 'active'];

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
            'show_cap' => 'boolean',
            'rentable' => 'boolean',
            'featured' => 'boolean',
            'active' => 'boolean',
            'cap' => 'integer',
            'rate_day' => 'integer',
            'rate_night' => 'integer',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('sort')->orderBy('id');
    }

    public function activeRooms(): HasMany
    {
        return $this->rooms()->where('active', true);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class)->orderBy('sort')->orderBy('id');
    }

    public function activePackages(): HasMany
    {
        return $this->packages()->where('active', true);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true)->orderBy('sort')->orderBy('id');
    }

    /** Capacity only when it may be shown publicly (FR-PUB-08). */
    public function publicCap(): ?int
    {
        return $this->show_cap && $this->cap ? $this->cap : null;
    }

    public function maxPax(): int
    {
        return $this->cap ?: (int) config('dmk.max_pax_fallback');
    }

    public function rentKey(): string
    {
        return 'rent:'.$this->id;
    }

    /** Lowest non-family package price, for "mulai dari" labels. */
    public function minPackagePrice(): int
    {
        return (int) $this->activePackages->where('family', false)->min('price');
    }
}
