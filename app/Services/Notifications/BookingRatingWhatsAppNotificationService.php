<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Mengirim undangan rating hanya setelah owner menyatakan permainan selesai.
 */
class BookingRatingWhatsAppNotificationService
{
    public function __construct(
        private readonly WhatsAppNotificationGateway $whatsAppGateway,
    ) {}

    public function sendFinishedBookingRatingInvitation(Booking $booking): Booking
    {
        $booking->loadMissing(['field:id,name', 'user:id,name']);

        if ($booking->status !== Booking::STATUS_FINISHED || $booking->rating_whatsapp_notified_at !== null) {
            return $booking;
        }

        // Klaim atomik mencegah pengiriman ganda saat request owner diulang.
        $claimed = Booking::query()
            ->whereKey($booking->id)
            ->where('status', Booking::STATUS_FINISHED)
            ->whereNull('rating_whatsapp_notified_at')
            ->update(['rating_whatsapp_notified_at' => now()]);

        if ($claimed !== 1) {
            return $booking->fresh(['field:id,name', 'user:id,name']);
        }

        $booking = $booking->fresh(['field:id,name', 'user:id,name']);
        $recipient = trim((string) $booking->customer_contact);

        if ($recipient === '') {
            $this->releaseNotificationClaim($booking);

            return $booking;
        }

        try {
            $customerName = $booking->customer_name ?: $booking->user?->name ?: 'Customer';
            $ratingUrl = URL::signedRoute('public.rating.create', ['booking' => $booking->id]);
            $response = $this->whatsAppGateway->sendTextMessage(
                to: $recipient,
                message: implode("\n", [
                    "Halo {$customerName}, terima kasih sudah bermain di {$booking->field?->name}.",
                    '',
                    'Permainan kamu telah selesai. Bantu kami dengan memberikan rating dan ulasan:',
                    $ratingUrl,
                ]),
            );

            $booking->forceFill(['rating_whatsapp_notification_response' => $response])->save();

            Log::info('booking.rating_whatsapp_notification.sent', [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('booking.rating_whatsapp_notification.failed', [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'message' => $exception->getMessage(),
            ]);

            $this->releaseNotificationClaim($booking);
        }

        return $booking->fresh(['field:id,name', 'user:id,name']);
    }

    private function releaseNotificationClaim(Booking $booking): void
    {
        $booking->forceFill(['rating_whatsapp_notified_at' => null])->save();
    }
}
