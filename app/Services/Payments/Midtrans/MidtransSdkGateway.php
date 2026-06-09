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
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                return $this->normalizeResponse(Transaction::status($orderId));
            } catch (\Exception $e) {
                $lastException = $e;

                $isNotFound = str_contains($e->getMessage(), '404')
                    || str_contains($e->getMessage(), "Transaction doesn't exist");

                // Jika error-nya bukan 404, langsung lempar error agar ditangani sistem
                if (! $isNotFound) {
                    throw $e;
                }

                // Jika masih 404, lakukan delay sebelum percobaan berikutnya (500ms -> 1000ms -> 2000ms)
                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::RETRY_DELAY_MS * (2 ** ($attempt - 1)) * 1000);
                }
            }
        }

        // Jika setelah percobaan maksimal masih 404, lemparkan error agar ditangkap oleh PaymentService
        throw $lastException;
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
