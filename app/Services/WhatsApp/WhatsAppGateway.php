<?php

namespace App\Services\WhatsApp;

/** Provider-agnostic contract so the gateway can be swapped (SRS §6.4). */
interface WhatsAppGateway
{
    /** @return array{ok: bool, status: string, response: string} status is "sent" or "simulated" when ok */
    public function send(string $to, string $message): array;
}
