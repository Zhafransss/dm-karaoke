<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $table = 'gallery';

    protected $fillable = ['caption', 'category', 'type', 'image', 'video', 'sort', 'published'];

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /** Full-size URL of the photo or video. */
    public function mediaUrl(): ?string
    {
        return upload_url($this->isVideo() ? $this->video : $this->image);
    }

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
