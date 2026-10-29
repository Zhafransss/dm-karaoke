<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

class FonnteGateway implements WhatsAppGateway
{
    public function __construct(private string $token) {}

    public function send(string $to, string $message): array
    {
        try {
            $res = Http::asForm()->timeout(15)
                ->withHeaders(['Authorization' => $this->token])
                ->post('https://api.fonnte.com/send', ['target' => $to, 'message' => $message, 'countryCode' => '62']);
            $ok = $res->successful() && (bool) $res->json('status');

            return ['ok' => $ok, 'status' => $ok ? 'sent' : 'failed', 'response' => mb_substr($res->body(), 0, 1000)];
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => 'failed', 'response' => mb_substr($e->getMessage(), 0, 1000)];
        }
    }
}
