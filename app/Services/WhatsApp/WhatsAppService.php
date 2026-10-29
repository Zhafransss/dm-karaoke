<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWaMessages;
use App\Models\Reservation;
use App\Models\WaLog;
use App\Support\Phone;
use App\Support\Settings;

/**
 * Template rendering, logging and retries (FR-WA-01..04). Messages are written
 * to wa_logs first and sent after the HTTP response, so a gateway failure never
 * blocks or rolls back a reservation (FR-WA-02, NFR-07).
 */
class WhatsAppService
{
    public function __construct(private WhatsAppGateway $gateway) {}

    public static function render(?string $template, array $vars): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn ($m) => $vars[$m[1]] ?? $m[0], (string) $template);
    }

    public function queueNewReservation(Reservation $r): void
    {
        $vars = $r->templateVars();
        $ids = [];
        if ($admin = Settings::get('wa_admin')) {
            $ids[] = $this->log($r, 'admin', $admin, 'tpl_admin', $vars)->id;
        }
        if ($r->phone) {
            $ids[] = $this->log($r, 'guest', $r->phone, 'tpl_guest', $vars)->id;
        }
        $this->dispatch($ids);
    }

    /** Automatic message when admin confirms or cancels (FR-WA-04). */
    public function queueStatusChange(Reservation $r): void
    {
        if (! Settings::get('wa_auto_status') || ! $r->phone) {
            return;
        }
        $template = match ($r->status) {
            'dikonfirmasi' => 'tpl_confirm',
            'dibatalkan' => 'tpl_reject',
            default => null,
        };
        if ($template) {
            $this->dispatch([$this->log($r, 'guest', $r->phone, $template, $r->templateVars())->id]);
        }
    }

    public function queueTest(): WaLog
    {
        $log = WaLog::create([
            'recipient' => 'admin', 'to' => Phone::toWa(Settings::get('wa_admin')), 'template' => 'test',
            'body' => "Tes notifikasi dari panel admin DM Karaoke & Cafe.\nGateway: ".(Settings::get('wa_gateway') ?: 'simulasi'),
        ]);
        $this->send($log);

        return $log->fresh();
    }

    public function send(WaLog $log): void
    {
        $res = $this->gateway->send($log->to, $log->body);
        $attempts = $log->attempts + 1;
        $final = $res['ok'] || $attempts >= (int) config('dmk.wa_max_attempts');
        $log->update([
            'status' => $res['ok'] ? $res['status'] : 'failed',
            'response' => $res['response'],
            'attempts' => $attempts,
            'sent_at' => $res['ok'] ? now() : null,
            // failed messages are retried by the scheduler every 5 minutes (FR-WA-03)
            'next_try_at' => $final ? null : now()->addMinutes(5),
        ]);
    }

    /** Called by the scheduler: resend failed/pending messages that are due. */
    public function retryDue(): int
    {
        $due = WaLog::whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', (int) config('dmk.wa_max_attempts'))
            ->where(fn ($q) => $q->whereNull('next_try_at')->orWhere('next_try_at', '<=', now()))
            ->where('created_at', '>=', now()->subDay())
            ->limit(30)->get();
        $due->each(fn (WaLog $l) => $this->send($l));

        return $due->count();
    }

    private function log(Reservation $r, string $recipient, string $to, string $template, array $vars): WaLog
    {
        return WaLog::create([
            'reservation_id' => $r->id,
            'recipient' => $recipient,
            'to' => Phone::toWa($to),
            'template' => $template,
            'body' => self::render(Settings::get($template), $vars),
        ]);
    }

    private function dispatch(array $ids): void
    {
        if ($ids) {
            SendWaMessages::dispatchAfterResponse($ids);
        }
    }
}
