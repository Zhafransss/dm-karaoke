<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoTarget extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['promo_id', 'target_key'];

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }
}
