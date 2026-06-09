<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Notifications\BookingPaymentWhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBookingPaymentWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tentukan jumlah percobaan jika gagal
     */
    public $tries = 3;

    /**
     * Tentukan waktu timeout untuk job ini (dalam detik)
     */
    public $timeout = 60;

    public function __construct(
        private readonly Payment $payment
    ) {}

    public function handle(BookingPaymentWhatsAppNotificationService $notificationService): void
    {
        Log::info('queue.whatsapp_notification.starting', [
            'payment_id' => $this->payment->id,
            'order_id' => $this->payment->order_id,
        ]);

        $notificationService->sendPaymentSuccessNotification($this->payment);
    }
}
