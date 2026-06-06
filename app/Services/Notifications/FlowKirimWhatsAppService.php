<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class FlowKirimWhatsAppService implements WhatsAppNotificationGateway
{
    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function sendTextMessage(string $to, string $message, ?string $sessionId = null): array
    {
        $token = $this->token();
        $sessionId = $sessionId ?: $this->defaultSessionId();
        $message = trim($message);

        if ($sessionId === '') {
            throw new InvalidArgumentException('FlowKirim session ID belum dikonfigurasi.');
        }

        if ($message === '') {
            throw new InvalidArgumentException('Pesan WhatsApp tidak boleh kosong.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->post('/api/whatsapp/messages/text', [
                'sessionId' => $sessionId,
                'to' => $this->normalizeRecipient($to),
                'message' => $message,
            ])
            ->throw()
            ->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function sendDocumentMessage(
        string $to,
        string $documentUrl,
        string $caption,
        ?string $filename = null,
        ?string $sessionId = null,
    ): array {
        $token = $this->token();
        $sessionId = $sessionId ?: $this->defaultSessionId();
        $documentUrl = trim($documentUrl);
        $caption = trim($caption);

        if ($sessionId === '') {
            throw new InvalidArgumentException('FlowKirim session ID belum dikonfigurasi.');
        }

        if ($documentUrl === '') {
            throw new InvalidArgumentException('URL dokumen WhatsApp tidak boleh kosong.');
        }

        if ($caption === '') {
            throw new InvalidArgumentException('Caption dokumen WhatsApp tidak boleh kosong.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout())
            ->post('/api/whatsapp/messages/media', array_filter([
                'session_id' => $sessionId,
                'to' => $this->normalizePhoneNumber($to),
                'media_url' => $documentUrl,
                'type' => 'document',
                'caption' => $caption,
                'filename' => $filename,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''))
            ->throw()
            ->json() ?? [];
    }

    private function normalizeRecipient(string $recipient): string
    {
        $recipient = trim($recipient);

        if ($recipient === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak boleh kosong.');
        }

        if (str_contains($recipient, '@')) {
            return $recipient;
        }

        $number = preg_replace('/\D+/', '', $recipient) ?? '';

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        if ($number === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        return $number.'@s.whatsapp.net';
    }

    private function normalizePhoneNumber(string $recipient): string
    {
        $recipient = trim($recipient);

        if ($recipient === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak boleh kosong.');
        }

        $recipient = explode('@', $recipient, 2)[0];
        $number = preg_replace('/\D+/', '', $recipient) ?? '';

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        if ($number === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tujuan tidak valid.');
        }

        return $number;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.flowkirim.base_url', 'https://api.flowkirim.com'), '/');
    }

    private function token(): string
    {
        $token = (string) config('services.flowkirim.token');

        if ($token === '') {
            throw new InvalidArgumentException('FlowKirim token belum dikonfigurasi.');
        }

        return $token;
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
