<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $table = 'gallery';

    protected $fillable = ['caption', 'category', 'image', 'sort', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
