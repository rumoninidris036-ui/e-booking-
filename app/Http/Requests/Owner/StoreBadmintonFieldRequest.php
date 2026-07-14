<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Services\Booking\FieldScheduleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBadmintonFieldRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'open_time' => $this->input('open_time', FieldScheduleService::DEFAULT_OPEN_TIME),
            'close_time' => $this->input('close_time', FieldScheduleService::DEFAULT_CLOSE_TIME),
            'slot_duration_minutes' => $this->input('slot_duration_minutes', FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES),
        ]);
    }

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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'slot_duration_minutes' => ['required', 'integer', 'min:30', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
            'facility_ids' => ['sometimes', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $openTime = $this->string('open_time')->toString();
                $closeTime = $this->string('close_time')->toString();
                $slotDuration = (int) $this->integer('slot_duration_minutes');

                if (! FieldScheduleService::isValidScheduleWindow($openTime, $closeTime, $slotDuration)) {
                    $validator->errors()->add('slot_duration_minutes', 'Jam buka, jam tutup, dan durasi slot harus membentuk jadwal yang valid.');
                }
            },
        ];
    }
}
