<?php

namespace App\Services\WhatsApp;

/** Used until a gateway is chosen in Pengaturan: messages are only logged. */
class SimulatedGateway implements WhatsAppGateway
{
    public function send(string $to, string $message): array
    {
        return ['ok' => true, 'status' => 'simulated', 'response' => 'Gateway belum dipilih; pesan hanya dicatat.'];
    }
}
