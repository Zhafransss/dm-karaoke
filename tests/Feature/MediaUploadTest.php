<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Concerns\SeedsVenue;
use Tests\TestCase;

/** Video upload in the gallery and extra media on the room detail page. */
class MediaUploadTest extends TestCase
{
    use RefreshDatabase, SeedsVenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedVenue();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('uploads/gallery'));
        File::deleteDirectory(public_path('uploads/rooms'));
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->role('super_admin')->create();
    }

    public function test_gallery_accepts_video_and_shows_it_publicly(): void
    {
        $this->actingAs($this->admin())->post('/admin/galeri', [
            'media' => [UploadedFile::fake()->create('tur-ruangan.mp4', 2000, 'video/mp4')],
            'category' => 'Ruangan', 'published' => 1,
        ])->assertRedirect(route('admin.galeri.index'));

        $item = GalleryItem::firstOrFail();
        $this->assertSame('video', $item->type);
        $this->assertNull($item->image);
        $this->assertFileExists(public_path('uploads/'.$item->video));

        $this->get('/galeri')->assertOk()->assertSee('<video', false)->assertSee(basename($item->video));
        $this->get('/')->assertOk();

        $this->actingAs($this->admin())->delete(route('admin.galeri.destroy', $item))->assertRedirect();
        $this->assertFileDoesNotExist(public_path('uploads/'.$item->video));
        $this->assertSame(0, GalleryItem::count());
    }

    public function test_gallery_rejects_oversized_video_and_wrong_format(): void
    {
        $admin = $this->admin();
        $tooBig = (config('dmk.max_video_mb') + 1) * 1024;
        $this->actingAs($admin)->post('/admin/galeri', ['media' => [UploadedFile::fake()->create('besar.mp4', $tooBig, 'video/mp4')], 'category' => 'Area'])
            ->assertSessionHasErrors('media.0');
        $this->actingAs($admin)->post('/admin/galeri', ['media' => [UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')], 'category' => 'Area'])
            ->assertSessionHasErrors('media.0');
        $this->assertSame(0, GalleryItem::count());
    }

    public function test_room_detail_page_lists_all_photos_and_videos(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.ruangan.media.store', $this->vip), [
            'media' => [
                UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.png', 800, 600),
                UploadedFile::fake()->create('tur.mp4', 1500, 'video/mp4'),
            ],
        ])->assertRedirect();

        $this->assertSame(3, $this->vip->media()->count());
        $this->assertSame(['image', 'image', 'video'], $this->vip->media()->pluck('type')->all());

        $this->get('/ruangan/'.$this->vip->slug)->assertOk()->assertSee('VIP Room')->assertSee('.mp4', false);
        $this->get('/ruangan/tidak-ada')->assertNotFound();
        $this->get('/sitemap.xml')->assertSee('/ruangan/'.$this->vip->slug, false);

        $video = $this->vip->media()->where('type', 'video')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.ruangan.media.destroy', [$this->vip, $video]))->assertRedirect();
        $this->assertFileDoesNotExist(public_path('uploads/'.$video->path));
        $this->assertSame(2, $this->vip->media()->count());
    }

    public function test_admin_pages_render_with_media(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.ruangan.media.store', $this->vip), ['media' => [UploadedFile::fake()->create('tur.mp4', 800, 'video/mp4')]]);
        $this->actingAs($admin)->post('/admin/galeri', ['media' => [UploadedFile::fake()->create('klip.mp4', 800, 'video/mp4')], 'category' => 'Area']);

        $this->actingAs($admin)->get(route('admin.ruangan.edit', $this->vip))->assertOk()->assertSee('Foto &amp; video tambahan', false)->assertSee('<video', false);
        $this->actingAs($admin)->get(route('admin.ruangan.create'))->assertOk()->assertSee('Simpan ruangan dulu');
        $this->actingAs($admin)->get(route('admin.galeri.index'))->assertOk()->assertSee('Video');
        $this->actingAs($admin)->get(route('admin.galeri.edit', GalleryItem::firstOrFail()))->assertOk()->assertSee('<video', false);
    }

    public function test_media_cannot_be_deleted_through_another_room(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.ruangan.media.store', $this->vip), ['media' => [UploadedFile::fake()->image('a.jpg')]]);
        $media = $this->vip->media()->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.ruangan.media.destroy', [$this->std, $media]))->assertNotFound();
        $this->assertSame(1, $this->vip->media()->count());
    }

    public function test_only_room_managers_can_upload_room_media(): void
    {
        $kasir = User::factory()->role('admin_reservasi')->create();
        $this->actingAs($kasir)->post(route('admin.ruangan.media.store', $this->vip), ['media' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
    }
}
