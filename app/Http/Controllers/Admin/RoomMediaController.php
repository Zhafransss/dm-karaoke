<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomMedia;
use App\Models\RoomType;
use App\Services\MediaService;
use Illuminate\Http\Request;

/** Extra photos and videos for the public room detail page. */
class RoomMediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function store(Request $request, RoomType $roomType)
    {
        $request->validate([
            'media' => ['required', 'array', 'min:1', 'max:20'],
            'media.*' => MediaService::rules(),
        ], ['media.required' => 'Pilih minimal satu foto atau video dulu.', ...MediaService::messages('media.*')]);

        $sort = (int) $roomType->media()->max('sort');
        $files = $request->file('media');
        foreach ($files as $file) {
            $stored = $this->media->store($file, 'rooms');
            $roomType->media()->create([...$stored, 'sort' => ++$sort]);
        }

        return redirect()->to(route('admin.ruangan.edit', $roomType).'#media')->with('ok', count($files).' file ditambahkan.');
    }

    public function destroy(RoomType $roomType, RoomMedia $media)
    {
        abort_unless($media->room_type_id === $roomType->id, 404);
        $this->media->delete($media->path, $media->type);
        $media->delete();

        return redirect()->to(route('admin.ruangan.edit', $roomType).'#media')->with('ok', $media->isVideo() ? 'Video dihapus.' : 'Foto dihapus.');
    }
}
