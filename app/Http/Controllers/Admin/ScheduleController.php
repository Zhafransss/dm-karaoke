<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Support\OpHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Daily unit × hour timeline and manual blocks (FR-ADM-04, FR-ADM-08). */
class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('tanggal')) ? $request->query('tanggal') : OpHours::today();
        $types = RoomType::with(['rooms' => fn ($q) => $q->where('active', true)])->orderBy('sort')->get()->filter(fn ($t) => $t->rooms->isNotEmpty());

        return view('admin.schedule', [
            'date' => $date,
            'today' => OpHours::today(),
            'types' => $types,
            'reservations' => Reservation::whereDate('date', $date)->where('status', '!=', 'dibatalkan')->get()->groupBy('room_id'),
            'blocks' => Block::with('user')->whereDate('date', $date)->get()->groupBy('room_id'),
            'activeCount' => Reservation::whereDate('date', $date)->occupying()->count(),
            'hours' => OpHours::hours(),
        ]);
    }

    public function storeBlock(Request $request, AvailabilityService $availability)
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'], 'date' => ['required', 'date_format:Y-m-d'],
            'start_hour' => ['required', 'integer'], 'hours' => ['required', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $end = $data['start_hour'] + $data['hours'];
        if ($data['start_hour'] < OpHours::open() || $end > OpHours::close()) {
            return back()->withInput()->withErrors(['block' => 'Melewati jam tutup ('.hl(OpHours::close()).').']);
        }

        return DB::transaction(function () use ($data, $end, $availability, $request) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);
            Room::where('room_type_id', $room->room_type_id)->lockForUpdate()->get();
            $map = $availability->busyMap($room->room_type_id, $data['date']);
            if (! $availability->roomFree($map, $room->id, $data['start_hour'], $data['hours'])) {
                $clash = Reservation::occupying()->whereDate('date', $data['date'])->where('room_id', $room->id)
                    ->where('start_hour', '<', $end)->whereRaw('start_hour + hours > ?', [$data['start_hour']])->first();

                return back()->withInput()->withErrors(['block' => $clash
                    ? "Ada reservasi {$clash->code} ({$clash->name}) di jam ini. Batalkan atau pindahkan reservasinya dulu."
                    : 'Sebagian jam ini sudah ditutup sebelumnya.']);
            }
            Block::create([...$data, 'end_hour' => $end, 'user_id' => $request->user()->id]);

            return redirect()->route('admin.jadwal', ['tanggal' => $data['date']])->with('ok', "Jam {$room->name} ditutup.");
        });
    }

    public function destroyBlock(Block $block)
    {
        $date = $block->date->toDateString();
        $block->delete();

        return redirect()->route('admin.jadwal', ['tanggal' => $date])->with('ok', 'Jam dibuka kembali.');
    }
}
