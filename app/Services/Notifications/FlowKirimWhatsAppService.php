<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class FlowKirimWhatsAppService implements WhatsAppNotificationGateway
{
    public function sendTextMessage(string $to, string $message, ?string $sessionId = null): array
    {
        $token = $this->token();
        $sessionId = $sessionId ?: $this->defaultSessionId();

        if ($sessionId === '') {
            throw new InvalidArgumentException('FlowKirim session ID belum dikonfigurasi.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->post('/api/whatsapp/messages/text', [
                'session_id' => $sessionId,
                'to' => $this->normalizePhoneNumber($to), // Pakai angka murni
                'message' => trim($message),
            ])
            ->throw()
            ->json() ?? [];
    }

    public function sendDocumentMessage(
        string $to,
        string $documentUrl,
        string $caption,
        ?string $filename = null,
        ?string $sessionId = null,
    ): array {
        $token = $this->token();
        $sessionId = $sessionId ?: $this->defaultSessionId();

        if ($sessionId === '') {
            throw new InvalidArgumentException('FlowKirim session ID belum dikonfigurasi.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->post('/api/whatsapp/messages/media', array_filter([
                'session_id' => $sessionId,
                'to' => $this->normalizePhoneNumber($to), // Pakai angka murni
                'media_url' => trim($documentUrl),
                'type' => 'document',
                'caption' => trim($caption),
                'filename' => $filename,
            ], static fn(mixed $value): bool => $value !== null && $value !== ''))
            ->throw()
            ->json() ?? [];
    }

    private function normalizePhoneNumber(string $recipient): string
    {
        $recipient = trim($recipient);

        // Bersihkan dari @s.whatsapp.net (karena server FlowKirim menolak format itu)
        $recipient = explode('@', $recipient, 2)[0];

        // Ambil angkanya saja
        $number = preg_replace('/\D+/', '', $recipient) ?? '';

        // Pastikan depannya 62, bukan 0
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        if ($number === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        return $number; // Hasil akhir: 6285231125221
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.flowkirim.base_url', 'https://scan.flowkirim.com'), '/');
    }

    private function token(): string
    {
        return (string) config('services.flowkirim.token');
    }

    private function defaultSessionId(): string
    {
        return (string) config('services.flowkirim.session_id');
    }

    private function timeout(): int
    {
        return 30; // Timeout untuk PDF dilegakan jadi 30 detik
    }
}
