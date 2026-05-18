<?php

declare(strict_types=1);

namespace App\Services\Payments\Midtrans;

use App\Contracts\Payments\MidtransGateway;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransSdkGateway implements MidtransGateway
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapTransaction(array $payload): array
    {
        return $this->normalizeResponse(Snap::createTransaction($payload));
    }

    public function getTransactionStatus(string $orderId): array
    {
        return $this->normalizeResponse(Transaction::status($orderId));
    }

    /**
     * @param  array<string, mixed>|object  $response
     * @return array<string, mixed>
     */
    private function normalizeResponse(array|object $response): array
    {
        return json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
