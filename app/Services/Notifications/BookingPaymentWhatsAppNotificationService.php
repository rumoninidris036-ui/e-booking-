<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim konfirmasi pembayaran dan link invoice ke WhatsApp customer.
 * Pengiriman diklaim secara atomik agar webhook yang berulang tidak menghasilkan pesan ganda.
 */
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

        /*
         * Midtrans dapat mengirim ulang webhook sementara halaman pembayaran juga
         * melakukan sinkronisasi status. Klaim ini harus atomik supaya hanya satu
         * proses yang boleh meneruskan pengiriman WhatsApp untuk satu pembayaran.
         */
        $claimedAt = now();
        $claimed = Payment::query()
            ->whereKey($payment->id)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereNull('whatsapp_notified_at')
            ->update(['whatsapp_notified_at' => $claimedAt]);

        if ($claimed !== 1) {
            Log::info('booking.whatsapp_notification.skipped_already_claimed', [
                'payment_id' => $payment->id,
                'booking_id' => $payment->booking_id,
            ]);

            return $payment->fresh(['booking.field', 'booking.user']);
        }

        $payment = $payment->fresh(['booking.field', 'booking.user']);

        $booking = $payment->booking;
        $recipient = trim((string) $booking->customer_contact);

        if ($recipient === '') {
            Log::info('booking.whatsapp_notification.skipped_missing_contact', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
            ]);

            $this->releaseNotificationClaim($payment);

            return $payment;
        }

        if ($payment->invoice_pdf_path === null) {
            Log::warning('booking.whatsapp_notification.skipped_missing_invoice', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
            ]);

            $this->releaseNotificationClaim($payment);

            return $payment;
        }

        try {
            // Gabungkan teks info booking dengan link download PDF internal aplikasi
            $downloadUrl = $this->invoiceDownloadUrl($payment);
            $messageText = $this->successCaption($payment)
                . "\n\nUnduh Bukti Booking / Invoice Anda di sini:\n"
                . $downloadUrl;

            // Panggil pengiriman text message biasa agar super ringan dan instan
            $response = $this->whatsAppGateway->sendTextMessage(
                to: $recipient,
                message: $messageText
            );

            $payment->forceFill([
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

            $this->releaseNotificationClaim($payment);
        }

        return $payment->fresh(['booking.field', 'booking.user']);
    }

    private function releaseNotificationClaim(Payment $payment): void
    {
        $payment->forceFill([
            'whatsapp_notified_at' => null,
        ])->save();
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
