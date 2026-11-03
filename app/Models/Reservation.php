<?php

namespace App\Models;

use App\Support\OpHours;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'lc' => 'boolean',
            'price_lines' => 'array',
            'start_hour' => 'integer',
            'hours' => 'integer',
            'pax' => 'integer',
            'base' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReservationLog::class)->orderBy('id');
    }

    public function scopeOccupying(Builder $q): Builder
    {
        return $q->whereIn('status', config('dmk.active_statuses'));
    }

    public function ymd(): string
    {
        return $this->date->toDateString();
    }

    public function endHour(): int
    {
        return $this->start_hour + $this->hours;
    }

    public function timeLabel(): string
    {
        return OpHours::range($this->start_hour, $this->hours);
    }

    public function orderLabel(): string
    {
        return $this->package_name ?: 'Sewa ruangan saja'.($this->lc ? ' (dengan LC)' : '');
    }

    public function statusLabel(): string
    {
        return config("dmk.statuses.{$this->status}.label", $this->status);
    }

    public function isOccupying(): bool
    {
        return in_array($this->status, config('dmk.active_statuses'), true);
    }

    /** @return string[] statuses reachable from the current one (SRS §4.8) */
    public function nextStatuses(): array
    {
        return config("dmk.transitions.{$this->status}", []);
    }

    /** Values for WhatsApp templates (FR-WA-01). */
    public function templateVars(): array
    {
        return [
            'nama' => $this->name,
            'hp' => $this->phone ?: '-',
            'kode' => $this->code,
            'ruangan' => $this->roomType?->name ?? '-',
            'unit' => $this->room?->name ?? '-',
            'paket' => $this->orderLabel(),
            'lc' => $this->lc ? 'Ya' : 'Tidak',
            'tanggal' => tgl($this->date),
            'jam' => $this->timeLabel(),
            'durasi' => $this->hours.' jam',
            'total' => rp($this->total),
            'orang' => (string) $this->pax,
            'pembayaran' => $this->roomType?->paymentShort() ?? 'dibayar di tempat',
        ];
    }
}
