<?php

namespace App\Models;

use App\Support\OpHours;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    protected $fillable = ['name', 'description', 'kind', 'value', 'x', 'y', 'date_start', 'date_end', 'time_start', 'time_end', 'active'];

    protected function casts(): array
    {
        return [
            'date_start' => 'date:Y-m-d',
            'date_end' => 'date:Y-m-d',
            'active' => 'boolean',
            'value' => 'integer',
            'x' => 'integer',
            'y' => 'integer',
            'time_start' => 'integer',
            'time_end' => 'integer',
        ];
    }

    public function targetRows(): HasMany
    {
        return $this->hasMany(PromoTarget::class);
    }

    /** Targets of an unsaved/edited promo (admin preview and conflict check). */
    public ?array $pendingTargets = null;

    /** @return string[] target keys such as "rent:2" or "pkg:15" */
    public function targets(): array
    {
        if ($this->pendingTargets !== null) {
            return $this->pendingTargets;
        }

        return $this->exists ? $this->targetRows->pluck('target_key')->all() : [];
    }

    public function syncTargets(array $keys): void
    {
        $this->targetRows()->delete();
        $this->targetRows()->createMany(array_map(fn ($k) => ['target_key' => $k], array_values(array_unique($keys))));
        $this->pendingTargets = null;
        $this->unsetRelation('targetRows');
    }

    public function ds(): string
    {
        return $this->date_start->toDateString();
    }

    public function de(): string
    {
        return $this->date_end->toDateString();
    }

    public function hasWindow(): bool
    {
        return $this->time_start !== null;
    }

    /** Does the hour window include operational hour $h? (SRS §4.6.5) */
    public function inWindow(int $h): bool
    {
        return ! $this->hasWindow() || ($h >= $this->time_start && $h < $this->time_end);
    }

    /** nonaktif | berakhir | terjadwal | berjalan */
    public function state(?string $ref = null): string
    {
        $ref ??= OpHours::today();
        if (! $this->active) {
            return 'nonaktif';
        }
        if ($this->de() < $ref) {
            return 'berakhir';
        }
        if ($this->ds() > $ref) {
            return 'terjadwal';
        }

        return 'berjalan';
    }

    /** Big figure + caption for promo cards. */
    public function figure(): array
    {
        return match ($this->kind) {
            'persen' => [$this->value.'%', 'Diskon'],
            'nominal' => [rpk($this->value), 'Potongan per transaksi'],
            default => [$this->x.' jam', 'Bayar '.$this->y.' jam'],
        };
    }

    public function windowLabel(): string
    {
        return $this->hasWindow() ? OpHours::label($this->time_start).'–'.OpHours::label($this->time_end) : '';
    }

    public function periodLabel(): string
    {
        return tgl($this->date_start, 'short').' – '.tgl($this->date_end, 'full').($this->hasWindow() ? ' · '.$this->windowLabel() : '');
    }

    /** Plain-language rule shown to guests. */
    public function ruleText(): string
    {
        $w = $this->windowLabel();
        $targets = $this->targets();
        $hasPkg = collect($targets)->contains(fn ($t) => str_starts_with($t, 'pkg:'));
        $hasRent = collect($targets)->contains(fn ($t) => str_starts_with($t, 'rent:'));

        if ($this->kind === 'bayar_x') {
            return "Sewa ruangan {$this->x} jam, cukup bayar {$this->y} jam. Satu kali per transaksi".($w ? ", untuk jam {$w}" : '').'.';
        }
        $base = $this->kind === 'persen' ? 'Diskon dihitung dari harga' : 'Potongan sekali per transaksi dari harga';
        $what = $hasRent && $hasPkg ? 'sewa ruangan atau paket' : ($hasPkg ? 'paket' : 'sewa ruangan');
        if (! $w) {
            return "{$base} {$what}.";
        }

        return $hasPkg && ! $hasRent
            ? "{$base} {$what}, bila paket mulai di jam {$w}."
            : "{$base} {$what}. Hanya jam {$w} yang kena promo, jam lain harga normal.";
    }
}
