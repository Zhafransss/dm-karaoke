<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gallery CMS: photo (WebP) and video upload, categories, publish toggle (FR-ADM-14). */
class GalleryController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request)
    {
        $cats = config('dmk.gallery_categories');
        $cat = in_array($request->query('kategori'), $cats, true) ? $request->query('kategori') : null;
        $all = GalleryItem::query();

        return view('admin.gallery.index', [
            'items' => GalleryItem::when($cat, fn ($q) => $q->where('category', $cat))->orderBy('sort')->orderByDesc('id')->get(),
            'cats' => $cats, 'cat' => $cat,
            'total' => (clone $all)->count(), 'shown' => (clone $all)->where('published', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'media' => ['required', 'array', 'min:1', 'max:20'],
            'media.*' => MediaService::rules(),
            'caption' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::in(config('dmk.gallery_categories'))],
        ], ['media.required' => 'Pilih minimal satu foto atau video dulu.', ...MediaService::messages('media.*')]);

        $files = $request->file('media');
        // New uploads go first: push existing items back so `sort` (unsigned) never goes negative.
        GalleryItem::query()->increment('sort', count($files));
        foreach ($files as $i => $file) {
            $fromName = str_replace(['-', '_'], ' ', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $given = $data['caption'] ?? null;
            $caption = $given ? (count($files) > 1 ? "{$given} ".($i + 1) : $given) : $fromName;
            $stored = $this->media->store($file, 'gallery');
            GalleryItem::create([
                'caption' => mb_substr($caption, 0, 150), 'category' => $data['category'], 'type' => $stored['type'],
                'image' => $stored['type'] === 'image' ? $stored['path'] : null, 'video' => $stored['type'] === 'video' ? $stored['path'] : null,
                'sort' => $i, 'published' => $request->boolean('published'),
            ]);
        }

        return redirect()->route('admin.galeri.index')->with('ok', count($files).' file ditambahkan.');
    }

    public function edit(GalleryItem $item)
    {
        return view('admin.gallery.edit', ['it' => $item, 'cats' => config('dmk.gallery_categories')]);
    }

    public function update(Request $request, GalleryItem $item)
    {
        $data = $request->validate([
            'caption' => ['required', 'string', 'max:150'], 'category' => ['required', Rule::in(config('dmk.gallery_categories'))],
            // Optional replacement file; a photo can only be replaced by a photo, a video by a video.
            'media' => ['nullable', ...MediaService::rules(), fn ($a, $file, $fail) => MediaService::isVideo($file) !== $item->isVideo()
                ? $fail($item->isVideo() ? 'Video hanya bisa diganti dengan video.' : 'Foto hanya bisa diganti dengan foto.') : null],
        ], ['caption.required' => 'Judul wajib diisi.', ...MediaService::messages('media')]);

        $values = ['caption' => $data['caption'], 'category' => $data['category'], 'published' => $request->boolean('published')];
        $oldPath = null;
        if ($request->hasFile('media')) {
            $stored = $this->media->store($request->file('media'), 'gallery');
            $oldPath = $item->isVideo() ? $item->video : $item->image;
            $values[$item->isVideo() ? 'video' : 'image'] = $stored['path'];
        }
        $item->update($values);
        // Only after the row points at the new file, so a failure never leaves an item without media.
        if ($oldPath) {
            $this->media->delete($oldPath, $item->type);
        }

        return redirect()->route('admin.galeri.index')->with('ok', $oldPath ? ($item->isVideo() ? 'Video diganti.' : 'Foto diganti.') : 'Perubahan disimpan.');
    }

    public function toggle(GalleryItem $item)
    {
        $item->update(['published' => ! $item->published]);

        $what = $item->isVideo() ? 'Video' : 'Foto';

        return back()->with('ok', $item->published ? "{$what} ditampilkan di galeri." : "{$what} disembunyikan dari galeri.");
    }

    public function destroy(GalleryItem $item)
    {
        $this->media->delete($item->isVideo() ? $item->video : $item->image, $item->type);
        $isVideo = $item->isVideo();
        $item->delete();

        return back()->with('ok', $isVideo ? 'Video dihapus.' : 'Foto dihapus.');
    }
}
