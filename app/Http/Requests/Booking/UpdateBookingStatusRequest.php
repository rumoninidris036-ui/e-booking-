<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('owner') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                Booking::STATUS_PAID,
                Booking::STATUS_FINISHED,
                Booking::STATUS_CANCELLED,
            ])],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
