<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Invoices\InvoiceService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function store(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorizePaymentAccess($request, $booking);

        if ($booking->status === Booking::STATUS_EXPIRED || $booking->isPendingPaymentExpired()) {
            if ($booking->status === Booking::STATUS_PENDING) {
                $booking = app(\App\Services\Booking\BookingService::class)->expirePendingBooking($booking);
            }

            throw ValidationException::withMessages([
                'booking' => ['This booking has expired because payment was not completed within 10 minutes. Please book the slot again.'],
            ]);
        }

        $payment = $this->paymentService->createOrGetSnapPayment(
            $booking->loadMissing(['field', 'user']),
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->to($this->paymentUrl($payment))
                ->with('status', 'Sesi pembayaran siap. Lanjutkan pembayaran secara aman lewat Midtrans.');
        }

        return response()->json([
            'message' => 'Snap payment created successfully.',
            'data' => PaymentResource::make($payment),
            'meta' => [
                'midtrans' => [
                    'merchant_id' => config('services.midtrans.merchant_id'),
                    'client_key' => config('services.midtrans.client_key'),
                ],
            ],
        ], 201);
    }

    public function show(Request $request, Payment $payment): JsonResponse|View
    {
        $this->authorizePaymentAccess($request, $payment->booking);

        $payment->load(['booking.field', 'booking.user']);
        $bookingExpired = false;

        if ($payment->booking->status === Booking::STATUS_PENDING && $payment->booking->isPendingPaymentExpired()) {
            $payment->booking = app(\App\Services\Booking\BookingService::class)->expirePendingBooking($payment->booking);
            $bookingExpired = true;
        } elseif ($payment->booking->status === Booking::STATUS_EXPIRED) {
            $bookingExpired = true;
        }

        if ($payment->provider === 'midtrans' && $payment->status !== Payment::STATUS_SUCCESS) {
            try {
                $payment = $this->paymentService->syncPaymentStatus($payment);
            } catch (\Throwable $exception) {
                Log::warning('payment.page.sync_failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $payment = $this->invoiceService->generateForPayment($payment);

            app(\App\Services\Notifications\BookingPaymentWhatsAppNotificationService::class)
                ->sendPaymentSuccessNotification($payment);
        }

        if (! $request->expectsJson()) {
            return view('payments.show', [
                'payment' => $payment,
                'booking' => $payment->booking,
                'field' => $payment->booking->field,
                'bookingExpiresAt' => $payment->booking->expires_at?->toIso8601String(),
                'bookingExpired' => $bookingExpired,
                'snapRedirectUrl' => $this->trustedSnapRedirectUrl($payment->snap_redirect_url),
                'paymentUrl' => $this->paymentUrl($payment),
                'paymentStoreUrl' => $this->paymentStoreUrl($payment->booking),
                'invoiceDownloadUrl' => $this->invoiceDownloadUrl($payment),
            ]);
        }

        return response()->json([
            'data' => PaymentResource::make($payment),
            'meta' => [
                'midtrans' => [
                    'merchant_id' => config('services.midtrans.merchant_id'),
                    'client_key' => config('services.midtrans.client_key'),
                ],
            ],
        ]);
    }

    public function handleReturn(Request $request, Payment $payment): View
    {
        $this->authorizePaymentAccess($request, $payment->booking);

        $payment->load(['booking.field', 'booking.user']);

        try {
            $payment = $this->paymentService->syncPaymentStatus($payment);

            if ($payment->status !== Payment::STATUS_SUCCESS && $this->hasMidtransReturnStatus($request)) {
                $payment = $this->paymentService->applyBrowserReturnStatus($payment, $request->all());
            }
        } catch (\Throwable $exception) {
            Log::warning('payment.return.sync_failed', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'message' => $exception->getMessage(),
            ]);

            try {
                $payment = $this->paymentService->applyBrowserReturnStatus($payment, $request->all());
            } catch (\Throwable $fallbackException) {
                Log::warning('payment.return.fallback_failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'message' => $fallbackException->getMessage(),
                ]);
            }
        }

        return view('payments.return', [
            'payment' => $payment->fresh(['booking.field', 'booking.user']),
            'paymentUrl' => $this->paymentUrl($payment),
        ]);
    }

    private function hasMidtransReturnStatus(Request $request): bool
    {
        return $request->filled('order_id')
            && ($request->filled('transaction_status') || $request->filled('callback_state'));
    }

    public function downloadInvoice(Request $request, Payment $payment): StreamedResponse
    {
        $this->authorizePaymentAccess($request, $payment->booking);

        $payment->load(['booking.field', 'booking.user']);
        $payment = $this->invoiceService->generateForPayment($payment);

        abort_if($payment->invoice_pdf_path === null, 404);
        abort_unless(Storage::disk('local')->exists($payment->invoice_pdf_path), 404);

        return Storage::disk('local')->download(
            $payment->invoice_pdf_path,
            sprintf('%s.pdf', $payment->invoice_number),
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function trustedSnapRedirectUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https') {
            return null;
        }

        $allowedHosts = [
            'app.midtrans.com',
            'app.sandbox.midtrans.com',
        ];

        return in_array($host, $allowedHosts, true) ? $url : null;
    }

    private function authorizePaymentAccess(Request $request, Booking $booking): void
    {
        $booking->loadMissing('field:id,owner_id');

        $token = (string) $request->query('access_token', $request->input('access_token', ''));

        if ($booking->user_id === null) {
            abort_unless($booking->guest_access_token !== null && hash_equals($booking->guest_access_token, $token), 403);

            return;
        }

        if ($booking->guest_access_token !== null && $token !== '' && hash_equals($booking->guest_access_token, $token)) {
            return;
        }

        if ($request->user()?->hasRole('owner') === true && $booking->field?->owner_id === $request->user()->id) {
            return;
        }

        abort_unless($booking->user_id === $request->user()?->id, 403);
    }

    private function paymentUrl(Payment $payment): string
    {
        return route('payments.show', array_filter([
            'payment' => $payment,
            'access_token' => $payment->booking->guest_access_token,
        ]));
    }

    private function paymentStoreUrl(Booking $booking): string
    {
        return route('payments.store', array_filter([
            'booking' => $booking,
            'access_token' => $booking->guest_access_token,
        ]));
    }

    private function invoiceDownloadUrl(Payment $payment): string
    {
        return route('payments.invoice.download', array_filter([
            'payment' => $payment,
            'access_token' => $payment->booking->guest_access_token,
        ]));
    }
}
