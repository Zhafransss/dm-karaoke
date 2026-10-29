<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    protected $fillable = ['room_type_id', 'brand', 'name', 'price', 'hours', 'items', 'family', 'start_hour', 'active', 'sort'];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'family' => 'boolean',
            'active' => 'boolean',
            'price' => 'integer',
            'hours' => 'integer',
            'start_hour' => 'integer',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function pkgKey(): string
    {
        return 'pkg:'.$this->id;
    }
}
