<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaLog extends Model
{
    protected $fillable = ['reservation_id', 'recipient', 'to', 'template', 'body', 'status', 'response', 'attempts', 'next_try_at', 'sent_at'];

    protected function casts(): array
    {
        return ['next_try_at' => 'datetime', 'sent_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
