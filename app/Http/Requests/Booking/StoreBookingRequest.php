<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => [
                Rule::requiredIf($this->user() === null),
                'nullable',
                'string',
                'max:255',
            ],
            'customer_contact' => [
                Rule::requiredIf($this->user() === null),
                'nullable',
                'string',
                'max:50',
            ],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'booking_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
