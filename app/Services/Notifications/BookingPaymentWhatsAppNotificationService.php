<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class BookingPaymentWhatsAppNotificationService
{
    public function __construct(
        private readonly WhatsAppNotificationGateway $whatsAppGateway,
    ) {}

    public function sendPaymentSuccessNotification(Payment $payment): Payment
    {
        $payment->loadMissing(['booking.field', 'booking.user']);

        if ($payment->status !== Payment::STATUS_SUCCESS || $payment->whatsapp_notified_at !== null) {
            return $payment;
        }

        $booking = $payment->booking;
        $recipient = trim((string) $booking->customer_contact);

        if ($recipient === '') {
            Log::info('booking.whatsapp_notification.skipped_missing_contact', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
            ]);

            return $payment;
        }

        if ($payment->invoice_pdf_path === null) {
            Log::warning('booking.whatsapp_notification.skipped_missing_invoice', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
            ]);

            return $payment;
        }

        try {
            // KITA GABUNGKAN CAPTION DENGAN LINK DOWNLOAD PDF
            $downloadUrl = $this->invoiceDownloadUrl($payment);
            $messageText = $this->successCaption($payment) . "\n\nUnduh Bukti Booking / Invoice Anda di sini:\n" . $downloadUrl;

            // KITA UBAH JADI KIRIM TEXT MESSAGE (BUKAN DOCUMENT)
            $response = $this->whatsAppGateway->sendTextMessage(
                to: $recipient,
                message: $messageText
            );

            $payment->forceFill([
                'whatsapp_notified_at' => now(),
                'whatsapp_notification_response' => $response,
            ])->save();

            Log::info('booking.whatsapp_notification.sent', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('booking.whatsapp_notification.failed', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'message' => $exception->getMessage(),
            ]);
        }

        return $payment->fresh(['booking.field', 'booking.user']);
    }

    private function invoiceDownloadUrl(Payment $payment): string
    {
        return route('payments.invoice.download', array_filter([
            'payment' => $payment,
            'access_token' => $payment->booking->guest_access_token,
        ]));
    }

    private function successCaption(Payment $payment): string
    {
        $booking = $payment->booking;
        $customerName = $booking->customer_name ?: $booking->user?->name ?: 'Customer';
        $bookingDate = $booking->booking_date?->translatedFormat('d M Y') ?? (string) $booking->booking_date;
        $startTime = substr((string) $booking->start_time, 0, 5);
        $endTime = substr((string) $booking->end_time, 0, 5);

        return implode("\n", [
            "Halo {$customerName}, pembayaran booking kamu sudah berhasil.",
            '',
            "Kode booking: {$booking->booking_code}",
            "Lapangan: {$booking->field->name}",
            "Jadwal: {$bookingDate}, {$startTime}-{$endTime}",
            'Status: Lunas',
            '',
            'Sampai jumpa di lapangan!',
        ]);
    }
}
