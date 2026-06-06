<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\MidtransGateway;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Invoices\InvoiceService;
use App\Services\Notifications\BookingPaymentWhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly MidtransGateway $midtransGateway,
        private readonly InvoiceService $invoiceService,
        private readonly BookingPaymentWhatsAppNotificationService $whatsAppNotificationService,
    ) {}

    public function createOrGetSnapPayment(Booking $booking): Payment
    {
        return DB::transaction(function () use ($booking): Payment {
            $lockedBooking = Booking::query()
                ->with(['field', 'user'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ($lockedBooking->status !== Booking::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'booking' => ['Only pending bookings can be paid.'],
                ]);
            }

            $existingPayment = Payment::query()
                ->where('booking_id', $lockedBooking->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existingPayment !== null && in_array($existingPayment->status, [Payment::STATUS_PENDING, Payment::STATUS_SUCCESS], true)) {
                Log::info('payment.snap.reused', [
                    'booking_id' => $lockedBooking->id,
                    'payment_id' => $existingPayment->id,
                    'order_id' => $existingPayment->order_id,
                    'status' => $existingPayment->status,
                ]);

                return $existingPayment->load('booking.field', 'booking.user');
            }

            $payment = Payment::query()->create([
                'booking_id' => $lockedBooking->id,
                'provider' => 'midtrans',
                'order_id' => $this->generatePaymentOrderId($lockedBooking),
                'amount' => $lockedBooking->price_per_hour,
                'currency' => 'IDR',
                'status' => Payment::STATUS_PENDING,
            ]);

            $snapResponse = $this->midtransGateway->createSnapTransaction($this->makeSnapPayload($payment, $lockedBooking));

            $payment->forceFill([
                'amount' => $lockedBooking->price_per_hour,
                'status' => Payment::STATUS_PENDING,
                'snap_token' => $snapResponse['token'] ?? null,
                'snap_redirect_url' => $snapResponse['redirect_url'] ?? null,
                'snap_response' => $snapResponse,
                'failed_at' => null,
            ])->save();

            Log::info('payment.snap.created', [
                'booking_id' => $lockedBooking->id,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'status' => $payment->status,
            ]);

            return $payment->fresh(['booking.field', 'booking.user']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleMidtransNotification(array $payload): Payment
    {
        $this->assertNotificationSignature($payload);
        $orderId = (string) ($payload['order_id'] ?? '');
        $verifiedStatus = $this->midtransGateway->getTransactionStatus($orderId);

        $payment = DB::transaction(function () use ($orderId, $payload, $verifiedStatus): Payment {
            $payment = Payment::query()
                ->with(['booking.field', 'booking.user'])
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $this->synchronizePaymentFromVerifiedStatus(
                payment: $payment,
                verifiedStatus: $verifiedStatus,
                notificationPayload: $payload,
            );

            Log::info('payment.webhook.processed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'incoming_transaction_status' => $verifiedStatus['transaction_status'] ?? null,
                'resolved_status' => $payment->status,
                'booking_status' => $payment->booking->status,
            ]);

            return $payment;
        });

        return $this->sendWhatsAppNotificationForSuccessfulPayment($payment);
    }

    public function syncPaymentStatus(Payment $payment): Payment
    {
        if ($payment->order_id === '') {
            return $payment->loadMissing(['booking.field', 'booking.user']);
        }

        $verifiedStatus = $this->midtransGateway->getTransactionStatus($payment->order_id);

        $syncedPayment = DB::transaction(function () use ($payment, $verifiedStatus): Payment {
            $lockedPayment = Payment::query()
                ->with(['booking.field', 'booking.user'])
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $this->synchronizePaymentFromVerifiedStatus(
                payment: $lockedPayment,
                verifiedStatus: $verifiedStatus,
            );

            return $this->ensureInvoiceForSuccessfulPayment($payment);
        });

        return $this->sendWhatsAppNotificationForSuccessfulPayment($syncedPayment);
    }

    /**
     * Local/testing fallback when Midtrans redirects the browser back but webhook
     * cannot reach localhost. This path is intentionally disabled outside local
     * and testing environments.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyBrowserReturnStatus(Payment $payment, array $payload): Payment
    {
        $orderId = (string) ($payload['order_id'] ?? '');

        if ($orderId !== '' && $orderId !== $payment->order_id) {
            throw ValidationException::withMessages([
                'order_id' => ['Midtrans return order id mismatch.'],
            ]);
        }

        $syncedPayment = DB::transaction(function () use ($payment, $payload): Payment {
            $lockedPayment = Payment::query()
                ->with(['booking.field', 'booking.user'])
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! app()->environment(['local', 'testing'])) {
                $this->assertSignedBrowserReturnPayload($lockedPayment, $payload);
            }

            $resolvedTransactionStatus = $this->resolveBrowserReturnTransactionStatus($payload);
            $fallbackStatus = $this->mapMidtransStatusToPaymentStatus([
                'transaction_status' => $resolvedTransactionStatus,
                'fraud_status' => (string) ($payload['fraud_status'] ?? ''),
            ]);

            $resolvedStatus = $this->resolveNextPaymentStatus($lockedPayment, $fallbackStatus);

            $lockedPayment->forceFill([
                'status' => $resolvedStatus,
                'midtrans_transaction_status' => $resolvedTransactionStatus !== ''
                    ? $resolvedTransactionStatus
                    : $lockedPayment->midtrans_transaction_status,
                'midtrans_payment_type' => (string) ($payload['payment_type'] ?? $lockedPayment->midtrans_payment_type),
                'paid_at' => $resolvedStatus === Payment::STATUS_SUCCESS ? ($lockedPayment->paid_at ?? now()) : $lockedPayment->paid_at,
                'failed_at' => $resolvedStatus === Payment::STATUS_FAILED ? ($lockedPayment->failed_at ?? now()) : $lockedPayment->failed_at,
            ])->save();

            if ($resolvedStatus === Payment::STATUS_SUCCESS && $lockedPayment->booking->status === Booking::STATUS_PENDING) {
                $lockedPayment->booking->forceFill([
                    'status' => Booking::STATUS_PAID,
                    'paid_at' => $lockedPayment->booking->paid_at ?? now(),
                ])->save();
            }

            Log::warning('payment.browser_return_fallback_applied', [
                'payment_id' => $lockedPayment->id,
                'order_id' => $lockedPayment->order_id,
                'resolved_status' => $resolvedStatus,
                'environment' => app()->environment(),
            ]);

            $lockedPayment = $lockedPayment->fresh(['booking.field', 'booking.user']);

            return $this->ensureInvoiceForSuccessfulPayment($lockedPayment);
        });

        return $this->sendWhatsAppNotificationForSuccessfulPayment($syncedPayment);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSignedBrowserReturnPayload(Payment $payment, array $payload): void
    {
        if ((string) ($payload['signature_key'] ?? '') === '') {
            if (! (bool) config('services.midtrans.is_production', false) && (string) ($payload['callback_state'] ?? '') !== '') {
                Log::warning('payment.return.unsigned_sandbox_fallback_allowed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'callback_state' => (string) ($payload['callback_state'] ?? ''),
                ]);

                return;
            }

            throw ValidationException::withMessages([
                'signature_key' => ['Midtrans return signature is required outside local/testing.'],
            ]);
        }

        $this->assertNotificationSignature($payload);

        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $expectedAmount = number_format((float) $payment->amount, 2, '.', '');

        if ($grossAmount !== $expectedAmount) {
            throw ValidationException::withMessages([
                'gross_amount' => ['Midtrans return amount mismatch.'],
            ]);
        }
    }

    private function makeSnapPayload(Payment $payment, Booking $booking): array
    {
        $customerName = $booking->customer_name ?: $booking->user?->name ?: 'Customer';
        $customerEmail = $booking->customer_email ?: $booking->user?->email;

        $customerDetails = [
            'first_name' => $customerName,
        ];

        if ($customerEmail !== null && $customerEmail !== '') {
            $customerDetails['email'] = $customerEmail;
        }

        if ($booking->customer_contact !== null && $booking->customer_contact !== '') {
            $customerDetails['phone'] = $booking->customer_contact;
        }

        $returnRouteParameters = array_filter([
            'payment' => $payment,
            'access_token' => $booking->guest_access_token,
        ]);

        return [
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => (int) round((float) $payment->amount),
            ],
            'customer_details' => $customerDetails,
            'item_details' => [
                [
                    'id' => (string) $booking->field->id,
                    'price' => (int) round((float) $booking->price_per_hour),
                    'quantity' => 1,
                    'name' => sprintf(
                        '%s %s-%s',
                        $booking->field->name,
                        $booking->start_time,
                        $booking->end_time,
                    ),
                ],
            ],
            'callbacks' => [
                'finish' => route('payments.return', [
                    ...$returnRouteParameters,
                    'callback_state' => 'finish',
                ]),
                'pending' => route('payments.return', [
                    ...$returnRouteParameters,
                    'callback_state' => 'pending',
                ]),
                'error' => route('payments.return', [
                    ...$returnRouteParameters,
                    'callback_state' => 'error',
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertNotificationSignature(array $payload): void
    {
        $signature = (string) ($payload['signature_key'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('payment.webhook.invalid_signature', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
            ]);

            throw ValidationException::withMessages([
                'signature_key' => ['Invalid Midtrans signature.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $verifiedStatus
     */
    private function assertVerifiedStatusMatchesPayment(Payment $payment, array $verifiedStatus): void
    {
        $statusOrderId = (string) ($verifiedStatus['order_id'] ?? '');
        $grossAmount = (string) ($verifiedStatus['gross_amount'] ?? '');
        $expectedAmount = number_format((float) $payment->amount, 2, '.', '');

        if ($statusOrderId !== $payment->order_id) {
            Log::warning('payment.webhook.order_mismatch', [
                'payment_id' => $payment->id,
                'expected_order_id' => $payment->order_id,
                'received_order_id' => $statusOrderId,
            ]);

            throw ValidationException::withMessages([
                'order_id' => ['Midtrans order id mismatch.'],
            ]);
        }

        if ($grossAmount !== $expectedAmount) {
            Log::warning('payment.webhook.amount_mismatch', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'expected_amount' => $expectedAmount,
                'received_amount' => $grossAmount,
            ]);

            throw ValidationException::withMessages([
                'gross_amount' => ['Midtrans amount mismatch.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $verifiedStatus
     */
    private function mapMidtransStatusToPaymentStatus(array $verifiedStatus): string
    {
        $transactionStatus = (string) ($verifiedStatus['transaction_status'] ?? '');
        $fraudStatus = (string) ($verifiedStatus['fraud_status'] ?? '');

        if ($transactionStatus === 'settlement') {
            return Payment::STATUS_SUCCESS;
        }

        if ($transactionStatus === 'capture') {
            return $fraudStatus === '' || $fraudStatus === 'accept'
                ? Payment::STATUS_SUCCESS
                : Payment::STATUS_PENDING;
        }

        if ($transactionStatus === 'pending') {
            return Payment::STATUS_PENDING;
        }

        return Payment::STATUS_FAILED;
    }

    private function resolveNextPaymentStatus(Payment $payment, string $incomingStatus): string
    {
        $priority = [
            Payment::STATUS_PENDING => 1,
            Payment::STATUS_FAILED => 2,
            Payment::STATUS_SUCCESS => 3,
        ];

        $currentPriority = $priority[$payment->status] ?? 0;
        $incomingPriority = $priority[$incomingStatus] ?? 0;

        if ($incomingPriority < $currentPriority) {
            Log::info('payment.webhook.ignored_downgrade', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'current_status' => $payment->status,
                'incoming_status' => $incomingStatus,
            ]);

            return $payment->status;
        }

        return $incomingStatus;
    }

    private function generatePaymentOrderId(Booking $booking): string
    {
        $paymentCount = Payment::query()
            ->where('booking_id', $booking->id)
            ->count();

        return sprintf(
            '%s-PAY-%02d-%s',
            $booking->booking_code,
            $paymentCount + 1,
            Str::upper(Str::random(6)),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveBrowserReturnTransactionStatus(array $payload): string
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');

        if ($transactionStatus !== '') {
            return $transactionStatus;
        }

        return match ((string) ($payload['callback_state'] ?? '')) {
            'finish' => 'settlement',
            'pending' => 'pending',
            'error' => 'deny',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $verifiedStatus
     * @param  array<string, mixed>|null  $notificationPayload
     */
    private function synchronizePaymentFromVerifiedStatus(
        Payment $payment,
        array $verifiedStatus,
        ?array $notificationPayload = null,
    ): Payment {
        $this->assertVerifiedStatusMatchesPayment($payment, $verifiedStatus);

        $paymentStatus = $this->mapMidtransStatusToPaymentStatus($verifiedStatus);
        $resolvedStatus = $this->resolveNextPaymentStatus($payment, $paymentStatus);

        $attributes = [
            'status' => $resolvedStatus,
            'midtrans_transaction_id' => $verifiedStatus['transaction_id'] ?? $payment->midtrans_transaction_id,
            'midtrans_transaction_status' => $verifiedStatus['transaction_status'] ?? $payment->midtrans_transaction_status,
            'midtrans_payment_type' => $verifiedStatus['payment_type'] ?? $payment->midtrans_payment_type,
            'paid_at' => $resolvedStatus === Payment::STATUS_SUCCESS ? ($payment->paid_at ?? now()) : $payment->paid_at,
            'failed_at' => $resolvedStatus === Payment::STATUS_FAILED ? ($payment->failed_at ?? now()) : $payment->failed_at,
        ];

        if ($notificationPayload !== null) {
            $attributes['notification_payload'] = $notificationPayload;
        }

        $payment->forceFill($attributes)->save();

        if ($resolvedStatus === Payment::STATUS_SUCCESS && $payment->booking->status === Booking::STATUS_PENDING) {
            $payment->booking->forceFill([
                'status' => Booking::STATUS_PAID,
                'paid_at' => $payment->booking->paid_at ?? now(),
            ])->save();
        }

        $payment = $payment->fresh(['booking.field', 'booking.user']);

        return $this->ensureInvoiceForSuccessfulPayment($payment);
    }

    private function ensureInvoiceForSuccessfulPayment(Payment $payment): Payment
    {
        if ($payment->status !== Payment::STATUS_SUCCESS) {
            return $payment;
        }

        return $this->invoiceService->generateForPayment($payment);
    }

    private function sendWhatsAppNotificationForSuccessfulPayment(Payment $payment): Payment
    {
        if ($payment->status !== Payment::STATUS_SUCCESS) {
            return $payment;
        }

        return $this->whatsAppNotificationService->sendPaymentSuccessNotification($payment);
    }
}
