<?php

declare(strict_types=1);

namespace App\Contracts\Notifications;

interface WhatsAppNotificationGateway
{
    /**
     * @return array<string, mixed>
     */
    public function sendTextMessage(string $to, string $message, ?string $sessionId = null): array;

    /**
     * @return array<string, mixed>
     */
    public function sendDocumentMessage(
        string $to,
        string $documentUrl,
        string $caption,
        ?string $filename = null,
        ?string $sessionId = null,
    ): array;
}
