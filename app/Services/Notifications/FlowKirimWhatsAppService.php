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

        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post('/api/whatsapp/messages/text', [
                'session_id' => $sessionId, // Pakai session_id
                'to' => $this->normalizeRecipient($to), // Pakai format lengkap @s.whatsapp.net
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

        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post('/api/whatsapp/messages/media', array_filter([
                'session_id' => $sessionId, // Pakai session_id
                'to' => $this->normalizeRecipient($to), // Pakai format lengkap @s.whatsapp.net
                'media_url' => trim($documentUrl),
                'type' => 'document',
                'caption' => trim($caption),
                'filename' => $filename,
            ], static fn(mixed $value): bool => $value !== null && $value !== ''))
            ->throw()
            ->json() ?? [];
    }

    private function normalizeRecipient(string $recipient): string
    {
        $recipient = trim($recipient);
        // Jika sudah ada @s.whatsapp.net, biarkan
        if (str_contains($recipient, '@s.whatsapp.net')) {
            return $recipient;
        }

        // Hapus karakter non-digit
        $number = preg_replace('/\D+/', '', explode('@', $recipient, 2)[0]) ?? '';

        // Pastikan format 62
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        return $number . '@s.whatsapp.net';
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
        return (int) config('services.flowkirim.timeout', 15);
    }
}
