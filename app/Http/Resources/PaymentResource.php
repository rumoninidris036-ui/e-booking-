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
            'invoice_number' => $this->invoice_number,
            'invoice_generated_at' => $this->invoice_generated_at,
            'invoice_download_url' => $this->invoice_pdf_path !== null
                ? route('payments.invoice.download', array_filter([
                    'payment' => $this->resource,
                    'access_token' => $this->booking?->guest_access_token,
                ]))
                : null,
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
                'customer_name' => $this->booking->customer_name ?: $this->booking->user?->name,
                'customer_contact' => $this->booking->customer_contact,
                'customer_email' => $this->booking->customer_email ?: $this->booking->user?->email,
                'field' => $this->booking->relationLoaded('field') ? [
                    'id' => $this->booking->field->id,
                    'name' => $this->booking->field->name,
                    'slug' => $this->booking->field->slug,
                ] : null,
            ]),
        ];
    }
}
