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
                'to' => $this->normalizePhoneNumber($to), // Pakai angka murni seperti di Postman
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
                'to' => $this->normalizePhoneNumber($to), // Pakai angka murni seperti di Postman
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

        // Buang @s.whatsapp.net jika ada (kembali ke cara yang sukses di Postman)
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

        return $number; // Mengembalikan format murni: 6285231125221
    }

    // =========================================================================
    // KITA HARDCODE SEMENTARA UNTUK MEMBYPASS CACHE .ENV LARAVEL CLOUD
    // =========================================================================

    private function baseUrl(): string
    {
        return 'https://scan.flowkirim.com';
    }

    private function token(): string
    {
        // Token kamu yang berhasil di Postman
        return '998298bd6716e716d86bc81674c265adf465a71dc5f71c60c2151e7ebccfa7df';
    }

    private function defaultSessionId(): string
    {
        // Session ID kamu yang terbukti jalan
        return 'tes-4c163ce5-cd99-4725-b1ed-13c5bbda3a99';
    }

    private function timeout(): int
    {
        return 30; // Timeout dinaikkan sedikit untuk media
    }
}
