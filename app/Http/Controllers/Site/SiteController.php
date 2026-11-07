<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\Message;
use App\Models\Package;
use App\Models\Promo;
use App\Models\RoomType;
use App\Services\PromoService;
use App\Support\OpHours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function home(PromoService $promos)
    {
        $today = OpHours::today();

        return view('site.home', [
            'types' => RoomType::active()->with('activePackages')->get(),
            'promos' => $this->runningPromos()->take(2),
            'events' => Event::published()->whereDate('date', '>=', $today)->orderByDesc('featured')->orderBy('date')->take(3)->get(),
            'gallery' => GalleryItem::where('published', true)->orderBy('sort')->orderByDesc('id')->take(6)->get(),
            'promoSvc' => $promos,
            'today' => $today,
        ]);
    }

    public function prices(PromoService $promos)
    {
        $types = RoomType::active()->with('activePackages')->get();

        return view('site.prices', [
            'types' => $types,
            'family' => Package::with('roomType')->where('family', true)->where('active', true)
                ->whereIn('room_type_id', $types->pluck('id'))->orderBy('sort')->get(),
            'promoSvc' => $promos,
            'today' => OpHours::today(),
        ]);
    }

    public function room(string $slug, PromoService $promos)
    {
        $type = RoomType::active()->with('activePackages', 'media')->where('slug', $slug)->firstOrFail();
        $today = OpHours::today();

        return view('site.room', [
            't' => $type,
            'promo' => $promos->firstFor([$type->rentKey(), ...$type->activePackages->map->pkgKey()], $today),
            'promoSvc' => $promos,
            'today' => $today,
        ]);
    }

    public function promos()
    {
        $all = Promo::with('targetRows')->where('active', true)->get();
        $today = OpHours::today();

        return view('site.promos', [
            'running' => $all->filter(fn ($p) => $p->state($today) === 'berjalan')->sortBy(fn ($p) => $p->de())->values(),
            'soon' => $all->filter(fn ($p) => $p->state($today) === 'terjadwal')->sortBy(fn ($p) => $p->ds())->values(),
        ]);
    }

    public function events()
    {
        $today = OpHours::today();

        return view('site.events', [
            'upcoming' => Event::published()->whereDate('date', '>=', $today)->orderBy('date')->get(),
            'past' => Event::published()->whereDate('date', '<', $today)->orderByDesc('date')->take(6)->get(),
        ]);
    }

    public function event(string $slug)
    {
        $event = Event::published()->where('slug', $slug)->firstOrFail();

        return view('site.event', ['event' => $event, 'upcoming' => $event->date->toDateString() >= OpHours::today()]);
    }

    public function gallery(Request $request)
    {
        $cats = config('dmk.gallery_categories');
        $cat = in_array($request->query('kategori'), $cats, true) ? $request->query('kategori') : null;

        return view('site.gallery', [
            'items' => GalleryItem::where('published', true)->when($cat, fn ($q) => $q->where('category', $cat))
                ->orderBy('sort')->orderByDesc('id')->get(),
            'cats' => $cats,
            'cat' => $cat,
        ]);
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function contactStore(Request $request)
    {
        // Honeypot: bots fill every field.
        if ($request->filled('website')) {
            return back()->with('message_sent', true);
        }
        $key = 'message:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, config('dmk.rate_limit.messages_per_hour'))) {
            return back()->withInput()->withErrors(['message' => 'Terlalu banyak pesan dari jaringan ini. Coba lagi nanti.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:120'],
            'kind' => ['required', Rule::in(config('dmk.message_kinds'))],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'message.required' => 'Tulis pesan minimal 10 karakter.',
            'message.min' => 'Tulis pesan minimal 10 karakter.',
        ]);
        Message::create($data);
        RateLimiter::hit($key, 3600);

        return redirect()->route('kontak')->withFragment('pesan')->with('message_sent', $data['name']);
    }

    public function sitemap()
    {
        // lastmod = newest change among the content a page shows; pages without such content get none.
        $latest = fn (string ...$models) => collect($models)->map(fn ($m) => $m::max('updated_at'))->filter()->map(fn ($d) => Carbon::parse($d))->max()?->toDateString();
        $url = fn (string $loc, ?string $lastmod = null, array $images = []) => ['loc' => $loc, 'lastmod' => $lastmod, 'images' => $images];

        $galleryImages = GalleryItem::where('published', true)->whereNotNull('image')->orderBy('sort')->limit(200)->get()->map(fn ($g) => upload_url($g->image))->all();
        $urls = collect([
            $url(route('home'), $latest(RoomType::class, Promo::class, Event::class, GalleryItem::class)),
            $url(route('harga'), $latest(RoomType::class, Package::class)),
            $url(route('reservasi')),
            $url(route('promo'), $latest(Promo::class)),
            $url(route('event'), $latest(Event::class)),
            $url(route('galeri'), $latest(GalleryItem::class), $galleryImages),
            $url(route('kontak')),
        ]);
        RoomType::active()->with('media')->get()->each(fn ($t) => $urls->push($url(
            route('ruangan.show', $t->slug), $t->updated_at?->toDateString(), $t->galleryItems()->where('type', 'image')->pluck('src')->all()
        )));
        Event::published()->get()->each(fn ($e) => $urls->push($url(
            route('event.show', $e->slug), $e->updated_at?->toDateString(), $e->image ? [upload_url($e->image)] : []
        )));

        return response()->view('site.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /cek-status\nDisallow: /reservasi/ketersediaan\nDisallow: /reservasi/harga\n\nSitemap: ".route('sitemap')."\n";

        return response($body)->header('Content-Type', 'text/plain');
    }

    private function runningPromos()
    {
        $today = OpHours::today();

        return Promo::with('targetRows')->where('active', true)->get()
            ->filter(fn ($p) => $p->state($today) === 'berjalan')->values();
    }
}
