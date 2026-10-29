<?php

namespace App\Jobs;

use App\Models\WaLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

/** Runs after the response is sent (no queue worker on shared hosting). */
class SendWaMessages
{
    use Dispatchable;

    public function __construct(public array $ids) {}

    public function handle(WhatsAppService $wa): void
    {
        foreach (WaLog::whereIn('id', $this->ids)->where('status', 'pending')->get() as $log) {
            try {
                $wa->send($log);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
