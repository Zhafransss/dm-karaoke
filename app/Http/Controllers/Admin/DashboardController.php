<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Promo;
use App\Models\Reservation;
use App\Models\WaLog;
use App\Support\OpHours;
use Illuminate\Http\Request;

/** FR-ADM-03. Widgets are shown according to the user's permissions. */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $today = OpHours::today();
        $canRes = $user->hasPerm('reservasi');

        return view('admin.dashboard', [
            'today' => $today,
            'canRes' => $canRes,
            'todays' => $canRes ? Reservation::with('room', 'roomType')->whereDate('date', $today)->where('status', '!=', 'dibatalkan')->orderBy('start_hour')->get() : collect(),
            'waiting' => $canRes ? Reservation::with('roomType')->where('status', 'menunggu')->orderBy('date')->orderBy('start_hour')->get() : collect(),
            'running' => Promo::where('active', true)->whereDate('date_start', '<=', $today)->whereDate('date_end', '>=', $today)->count(),
            'unread' => Message::whereNull('read_at')->count(),
            'waLogs' => $user->hasPerm('pengaturan') ? WaLog::latest('id')->take(4)->get() : collect(),
        ]);
    }
}
