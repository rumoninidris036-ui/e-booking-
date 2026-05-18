<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'provider' => $this->provider,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'snap_token' => $this->snap_token,
            'snap_redirect_url' => $this->snap_redirect_url,
            'midtrans_transaction_status' => $this->midtrans_transaction_status,
            'midtrans_payment_type' => $this->midtrans_payment_type,
            'paid_at' => $this->paid_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'booking' => $this->whenLoaded('booking', fn (): array => [
                'id' => $this->booking->id,
                'booking_code' => $this->booking->booking_code,
                'status' => $this->booking->status,
                'booking_date' => $this->booking->booking_date,
                'start_time' => $this->booking->start_time,
                'end_time' => $this->booking->end_time,
                'field' => $this->booking->relationLoaded('field') ? [
                    'id' => $this->booking->field->id,
                    'name' => $this->booking->field->name,
                    'slug' => $this->booking->field->slug,
                ] : null,
            ]),
        ];
    }
}
