<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function store(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $payment = $this->paymentService->createOrGetSnapPayment(
            $booking->loadMissing(['field', 'user']),
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->route('payments.show', $payment)
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
        abort_unless($payment->booking->user_id === $request->user()->id, 403);

        $payment->load(['booking.field', 'booking.user']);

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

        if (! $request->expectsJson()) {
            return view('payments.show', [
                'payment' => $payment,
                'booking' => $payment->booking,
                'field' => $payment->booking->field,
                'snapRedirectUrl' => $this->trustedSnapRedirectUrl($payment->snap_redirect_url),
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
        abort_unless($payment->booking->user_id === $request->user()->id, 403);

        $payment->load(['booking.field', 'booking.user']);

        try {
            $payment = $this->paymentService->syncPaymentStatus($payment);
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
        ]);
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
}
