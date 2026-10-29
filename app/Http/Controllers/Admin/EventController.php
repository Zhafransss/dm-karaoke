<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ImageService;
use App\Support\OpHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Event CMS with draft/published status (FR-ADM-14). */
class EventController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index()
    {
        $today = OpHours::today();
        $all = Event::all();
        $list = $all->sortBy(fn ($e) => $e->date->toDateString() >= $today ? '0'.$e->date->toDateString() : '1'.(99999999 - (int) $e->date->format('Ymd')))->values();

        return view('admin.events.index', ['list' => $list, 'today' => $today, 'stats' => [
            'upcoming' => $all->filter(fn ($e) => $e->published && $e->date->toDateString() >= $today)->count(),
            'draft' => $all->where('published', false)->count(),
            'past' => $all->filter(fn ($e) => $e->date->toDateString() < $today)->count(),
        ]]);
    }

    public function create()
    {
        return view('admin.events.form', ['e' => new Event(['date' => CarbonImmutable::parse(OpHours::today())->addDays(7)->toDateString(), 'start_time' => '19.00', 'end_time' => '23.00', 'published' => true])]);
    }

    public function edit(Event $event)
    {
        return view('admin.events.form', ['e' => $event]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new Event);
    }

    public function update(Request $request, Event $event)
    {
        return $this->save($request, $event);
    }

    public function toggle(Event $event)
    {
        $event->update(['published' => ! $event->published]);

        return back()->with('ok', $event->published ? 'Event diterbitkan.' : 'Event dijadikan draf.');
    }

    public function destroy(Event $event)
    {
        $this->images->delete($event->image);
        $event->delete();

        return redirect()->route('admin.event.index')->with('ok', 'Event dihapus.');
    }

    private function save(Request $request, Event $e)
    {
        $time = ['required', 'regex:/^\d{2}\.\d{2}$/'];
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'], 'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => $time, 'end_time' => $time, 'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], ['title.required' => 'Judul wajib diisi.', 'date.required' => 'Tanggal wajib diisi.']);

        if (! $e->exists || $e->title !== $data['title']) {
            $e->slug = Event::uniqueSlug($data['title'], $e->id);
        }
        $e->fill([
            'title' => $data['title'], 'date' => $data['date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
            'body' => $data['body'] ?? null, 'published' => $request->input('status') === 'terbit', 'featured' => $request->boolean('featured'),
        ]);
        if ($request->hasFile('image')) {
            $old = $e->image;
            $e->image = $this->images->store($request->file('image'), 'events');
            $this->images->delete($old);
        }
        $e->save();

        return redirect()->route('admin.event.index')->with('ok', $e->wasRecentlyCreated ? 'Event disimpan.' : 'Event diperbarui.');
    }
}
