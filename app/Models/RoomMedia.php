<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomMedia extends Model
{
    protected $table = 'room_type_media';

    protected $fillable = ['room_type_id', 'type', 'path', 'sort'];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
}
