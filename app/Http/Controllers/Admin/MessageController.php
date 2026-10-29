<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

/** Guest message inbox (FR-ADM-15). */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['baru', 'dibaca'], true) ? $request->query('tab') : 'semua';
        $open = $request->integer('buka') ?: null;
        if ($open && ($m = Message::find($open)) && ! $m->isRead()) {
            $m->update(['read_at' => now()]);
        }

        return view('admin.messages.index', [
            'list' => Message::when($tab === 'baru', fn ($q) => $q->whereNull('read_at'))->when($tab === 'dibaca', fn ($q) => $q->whereNotNull('read_at'))
                ->latest()->paginate(30)->withQueryString(),
            'tab' => $tab, 'open' => $open,
            'counts' => ['semua' => Message::count(), 'baru' => Message::whereNull('read_at')->count(), 'dibaca' => Message::whereNotNull('read_at')->count()],
        ]);
    }

    public function toggleRead(Message $message)
    {
        $message->update(['read_at' => $message->isRead() ? null : now()]);

        return back();
    }

    public function readAll()
    {
        Message::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('ok', 'Semua pesan ditandai dibaca.');
    }

    public function destroy(Message $message)
    {
        $message->delete();

        return redirect()->route('admin.pesan.index')->with('ok', 'Pesan dihapus.');
    }
}
