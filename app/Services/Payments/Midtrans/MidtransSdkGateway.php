<?php

declare(strict_types=1);

namespace App\Services\Payments\Midtrans;

use App\Contracts\Payments\MidtransGateway;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransSdkGateway implements MidtransGateway
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 500; // milliseconds

    public function __construct()
    {
        // Hapus spasi tidak sengaja yang mungkin terbawa dari .env
        Config::$serverKey = trim((string) config('services.midtrans.server_key'));

        // PERBAIKAN: Filter ketat untuk menangani string "false" / "true" dari Laravel Cloud
        $isProduction = config('services.midtrans.is_production', false);
        if (is_string($isProduction)) {
            $isProduction = trim($isProduction);
        }
        Config::$isProduction = filter_var($isProduction, FILTER_VALIDATE_BOOLEAN);

        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapTransaction(array $payload): array
    {
        return $this->normalizeResponse(Snap::createTransaction($payload));
    }

    public function getTransactionStatus(string $orderId): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return $this->normalizeResponse(Transaction::status($orderId));
            } catch (\Exception $e) {
                $lastException = $e;

                $isNotFound = str_contains($e->getMessage(), '404')
                    || str_contains($e->getMessage(), "Transaction doesn't exist");

                if (! $isNotFound) {
                    throw $e;
                }

                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * (2 ** ($attempt - 1)) * 1000);
                }
            }
        }

        throw $lastException;
    }

    private function normalizeResponse(array|object $response): array
    {
        return json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
