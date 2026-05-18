<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

interface MidtransGateway
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSnapTransaction(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $orderId): array;
}
