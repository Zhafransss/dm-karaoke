<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\OpHours;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private AvailabilityService $availability,
        private PricingService $pricing,
        private WhatsAppService $wa,
    ) {}

    /**
     * Create a reservation atomically (FR-RES-09/10/11, NFR-03).
     *
     * @param  array{room_type_id:int, package_id:?int, date:string, start_hour:int, hours:int, lc:bool,
     *               pax:int, name:string, phone:?string, note:?string, room_id?:?int, status?:string}  $in
     */
    public function create(array $in, string $source = 'online', ?User $by = null, ?string $ip = null): Reservation
    {
        $asAdmin = $source === 'admin';

        $reservation = DB::transaction(function () use ($in, $asAdmin, $source, $by, $ip) {
            $type = RoomType::findOrFail($in['room_type_id']);
            // Lock every unit row of this type first, so two concurrent requests
            // for the same type are serialized before the conflict check.
            Room::where('room_type_id', $type->id)->lockForUpdate()->get();

            $pkg = ! empty($in['package_id']) ? Package::find($in['package_id']) : null;
            if (! empty($in['package_id']) && ! $pkg) {
                throw new BookingException('Paket tidak ditemukan.');
            }
            $date = $in['date'];
            $hour = (int) $in['start_hour'];
            $hours = $pkg ? $pkg->hours : (int) $in['hours'];

            if ($err = $this->availability->ruleError($type, $pkg, $date, $hour, $hours, $asAdmin)) {
                throw new BookingException($err);
            }
            if ((int) $in['pax'] < 1 || (int) $in['pax'] > $type->maxPax()) {
                throw new BookingException("Jumlah orang untuk {$type->name} maksimal {$type->maxPax()} orang.");
            }

            $this->availability->flush();
            $room = $this->availability->freeRoom($type->id, $date, $hour, $hours, $in['room_id'] ?? null);
            if (! $room) {
                throw new BookingException(
                    $asAdmin ? 'Unit yang dipilih sudah terisi di jam ini. Pilih unit atau jam lain.' : 'Maaf, jam ini baru saja dipesan orang lain. Pilih jam lain.',
                    conflict: true
                );
            }

            $quote = $this->pricing->calculate($type, $pkg, $date, $hour, $hours, (bool) ($in['lc'] ?? false));
            $status = $asAdmin ? ($in['status'] ?? 'dikonfirmasi') : 'menunggu';

            $r = Reservation::create([
                'code' => $this->newCode($date),
                'room_type_id' => $type->id,
                'room_id' => $room->id,
                'package_id' => $pkg?->id,
                'package_name' => $quote['package_name'],
                'lc' => $quote['lc'],
                'date' => $date,
                'start_hour' => $hour,
                'hours' => $quote['hours'],
                'pax' => (int) $in['pax'],
                'name' => trim($in['name']),
                'phone' => ! empty($in['phone']) ? Phone::normalize($in['phone']) : null,
                'note' => $in['note'] ?? null,
                'status' => $status,
                'base' => $quote['base'],
                'discount' => $quote['discount'],
                'total' => $quote['total'],
                'promo_id' => $quote['promo_id'],
                'promo_name' => $quote['promo_name'],
                'promo_note' => $quote['promo_note'] ?: null,
                'price_lines' => $quote['lines'],
                'source' => $source,
                'ip' => $ip,
                'created_by' => $by?->id,
            ]);
            ReservationLog::create([
                'reservation_id' => $r->id, 'user_id' => $by?->id, 'from_status' => null, 'to_status' => $status,
                'note' => $asAdmin ? 'Dibuat admin' : 'Reservasi online',
            ]);

            return $r;
        });

        $this->availability->flush();

        // Admin-made (walk-in/phone) reservations do not trigger notifications.
        if (! $asAdmin) {
            $this->wa->queueNewReservation($reservation->load('roomType', 'room'));
        }

        return $reservation;
    }

    /** Status change by admin only, following SRS §4.8; every change is logged. */
    public function changeStatus(Reservation $r, string $to, User $by): Reservation
    {
        if (! in_array($to, $r->nextStatuses(), true)) {
            throw new BookingException('Perubahan status ini tidak diizinkan.');
        }
        DB::transaction(function () use ($r, $to, $by) {
            $from = $r->status;
            $r->update(['status' => $to]);
            ReservationLog::create(['reservation_id' => $r->id, 'user_id' => $by->id, 'from_status' => $from, 'to_status' => $to]);
        });
        $this->availability->flush();
        $this->wa->queueStatusChange($r->load('roomType', 'room'));

        return $r;
    }

    /**
     * Move to another unit and/or another time (FR-ADM-09) with conflict validation.
     * The locked price is kept unless $reprice is true.
     */
    public function reschedule(Reservation $r, ?int $roomId, string $date, int $hour, bool $reprice, User $by): Reservation
    {
        if (! $r->isOccupying()) {
            throw new BookingException('Hanya reservasi menunggu atau dikonfirmasi yang bisa dipindah.');
        }

        return DB::transaction(function () use ($r, $roomId, $date, $hour, $reprice, $by) {
            $type = $r->roomType;
            Room::where('room_type_id', $type->id)->lockForUpdate()->get();
            $pkg = $r->package;
            $hours = $r->hours;

            if ($pkg && $pkg->start_hour !== null && $hour !== $pkg->start_hour) {
                throw new BookingException("{$pkg->name} hanya bisa dimulai pukul ".OpHours::label($pkg->start_hour).'.');
            }
            if ($hour < OpHours::open() || $hour + $hours > OpHours::close()) {
                throw new BookingException('Jadwal baru melewati jam buka atau jam tutup.');
            }

            $this->availability->flush();
            $room = $this->availability->freeRoom($type->id, $date, $hour, $hours, $roomId, $r->id);
            if (! $room) {
                throw new BookingException('Unit atau jam tujuan sudah terisi. Pilih yang lain.', conflict: true);
            }

            $before = "{$r->room->name}, ".tgl($r->date, 'mid').' '.$r->timeLabel();
            $changes = ['room_id' => $room->id, 'date' => $date, 'start_hour' => $hour];
            $note = '';
            if ($reprice && ($date !== $r->ymd() || $hour !== $r->start_hour)) {
                $q = $this->pricing->calculate($type, $pkg, $date, $hour, $hours, $r->lc);
                $changes += [
                    'base' => $q['base'], 'discount' => $q['discount'], 'total' => $q['total'], 'promo_id' => $q['promo_id'],
                    'promo_name' => $q['promo_name'], 'promo_note' => $q['promo_note'] ?: null, 'price_lines' => $q['lines'],
                ];
                $note = ' Harga dihitung ulang: '.rp($r->total).' → '.rp($q['total']).'.';
            }
            $r->update($changes);
            $r->refresh()->load('room');
            $this->availability->flush();
            ReservationLog::create([
                'reservation_id' => $r->id, 'user_id' => $by->id, 'from_status' => $r->status, 'to_status' => $r->status,
                'note' => "Dipindah dari {$before} ke {$r->room->name}, ".tgl($r->date, 'mid').' '.$r->timeLabel().'.'.$note,
            ]);

            return $r;
        });
    }

    /** Unguessable but readable code: DM-<ddmm of visit>-<4 chars>. */
    private function newCode(string $date): string
    {
        $prefix = 'DM-'.substr($date, 8, 2).substr($date, 5, 2).'-';
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (Reservation::where('code', $prefix.$suffix)->exists());

        return $prefix.$suffix;
    }
}
