<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\MidtransWebhookRequest;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;

class MidtransWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(MidtransWebhookRequest $request): JsonResponse
    {
        $payment = $this->paymentService->handleMidtransNotification(
            $request->validated(),
        );

        return response()->json([
            'message' => 'Midtrans notification processed successfully.',
            'data' => [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'payment_status' => $payment->status,
                'booking_status' => $payment->booking->status,
            ],
        ]);
    }
}
