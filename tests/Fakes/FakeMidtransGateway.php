<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\Payments\MidtransGateway;

class FakeMidtransGateway implements MidtransGateway
{
    /**
     * @var array<string, mixed>
     */
    public array $snapResponse = [
        'token' => 'snap-token-test',
        'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token',
    ];

    /**
     * @var array<string, mixed>
     */
    public array $statusResponse = [
        'order_id' => 'BK-2026-0001',
        'status_code' => '200',
        'gross_amount' => '80000.00',
        'transaction_status' => 'settlement',
        'payment_type' => 'bank_transfer',
        'transaction_id' => 'trx-123',
        'fraud_status' => 'accept',
    ];

    public function createSnapTransaction(array $payload): array
    {
        return $this->snapResponse;
    }

    public function getTransactionStatus(string $orderId): array
    {
        $response = $this->statusResponse;
        $response['order_id'] = $orderId;

        return $response;
    }
}
