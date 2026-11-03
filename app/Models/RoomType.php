<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class RoomType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'facilities', 'image', 'cap', 'show_cap', 'rentable', 'rate_day', 'rate_night', 'deposit', 'prepaid', 'featured', 'sort', 'active'];

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
            'show_cap' => 'boolean',
            'rentable' => 'boolean',
            'prepaid' => 'boolean',
            'deposit' => 'integer',
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

    public function media(): HasMany
    {
        return $this->hasMany(RoomMedia::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Cover photo first, then extra photos and videos, for the public detail page.
     *
     * @return Collection<int, array{type: string, src: string, thumb: ?string}>
     */
    public function galleryItems(): Collection
    {
        $items = collect();
        if ($this->image) {
            $items->push(['type' => 'image', 'src' => upload_url($this->image), 'thumb' => upload_url($this->image, 'sm')]);
        }
        foreach ($this->media as $m) {
            $items->push($m->isVideo()
                ? ['type' => 'video', 'src' => upload_url($m->path), 'thumb' => null]
                : ['type' => 'image', 'src' => upload_url($m->path), 'thumb' => upload_url($m->path, 'sm')]);
        }

        return $items;
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

    /** "Rp 500.000", or '' when no deposit amount is set. */
    public function depositLabel(): string
    {
        return $this->deposit > 0 ? rp($this->deposit) : '';
    }

    /** Where and when the guest pays, as a short phrase for WhatsApp messages. */
    public function paymentShort(): string
    {
        return $this->prepaid ? 'wajib dilunasi di awal' : 'dibayar di tempat';
    }

    public function paymentNote(): string
    {
        return $this->prepaid ? 'Wajib lunas di awal, tanpa deposit.' : 'Dibayar di tempat.';
    }

    public function depositText(): string
    {
        if ($this->prepaid) {
            return 'Wajib lunas di awal, tanpa deposit.';
        }

        return $this->deposit > 0
            ? "Deposit {$this->depositLabel()} berupa uang, dibayar di tempat."
            : 'Deposit berupa uang, dibayar di tempat. Besarnya diinformasikan admin saat konfirmasi.';
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
