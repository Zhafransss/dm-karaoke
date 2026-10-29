<?php

namespace App\Models;

use App\Support\OpHours;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A manually closed time range on one unit (FR-ADM-08). */
class Block extends Model
{
    protected $fillable = ['room_id', 'date', 'start_hour', 'end_hour', 'reason', 'user_id'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'start_hour' => 'integer', 'end_hour' => 'integer'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeLabel(): string
    {
        return OpHours::label($this->start_hour).'–'.OpHours::label($this->end_hour);
    }
}
