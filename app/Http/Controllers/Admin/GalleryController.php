<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gallery CMS: upload (WebP), categories, publish toggle (FR-ADM-14). */
class GalleryController extends Controller
{
    public function __construct(private ImageService $images) {}

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
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::in(config('dmk.gallery_categories'))],
        ], ['photos.required' => 'Pilih minimal satu foto dulu.', 'photos.*.image' => 'Ada file yang bukan gambar.', 'photos.*.max' => 'Ukuran foto maksimal 8 MB.']);

        $files = $request->file('photos');
        $sort = (int) GalleryItem::min('sort');
        foreach ($files as $i => $file) {
            $fromName = str_replace(['-', '_'], ' ', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $caption = $data['caption'] ? (count($files) > 1 ? "{$data['caption']} ".($i + 1) : $data['caption']) : $fromName;
            GalleryItem::create([
                'caption' => mb_substr($caption, 0, 150), 'category' => $data['category'], 'image' => $this->images->store($file, 'gallery'),
                'sort' => $sort - count($files) + $i, 'published' => $request->boolean('published'),
            ]);
        }

        return redirect()->route('admin.galeri.index')->with('ok', count($files).' foto ditambahkan.');
    }

    public function edit(GalleryItem $item)
    {
        return view('admin.gallery.edit', ['it' => $item, 'cats' => config('dmk.gallery_categories')]);
    }

    public function update(Request $request, GalleryItem $item)
    {
        $data = $request->validate([
            'caption' => ['required', 'string', 'max:150'], 'category' => ['required', Rule::in(config('dmk.gallery_categories'))],
        ], ['caption.required' => 'Judul wajib diisi.']);
        $item->update([...$data, 'published' => $request->boolean('published')]);

        return redirect()->route('admin.galeri.index')->with('ok', 'Perubahan disimpan.');
    }

    public function toggle(GalleryItem $item)
    {
        $item->update(['published' => ! $item->published]);

        return back()->with('ok', $item->published ? 'Foto ditampilkan di galeri.' : 'Foto disembunyikan dari galeri.');
    }

    public function destroy(GalleryItem $item)
    {
        $this->images->delete($item->image);
        $item->delete();

        return back()->with('ok', 'Foto dihapus.');
    }
}
