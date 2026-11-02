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

    /** Width / height of the photo (videos use their 4:3 thumbnail), for balancing the public grid. */
    public function ratio(): float
    {
        $info = $this->image && ! $this->isVideo() ? @getimagesize(public_path('uploads/'.$this->image)) : false;

        return $info && $info[1] ? round($info[0] / $info[1], 3) : 1.333;
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
