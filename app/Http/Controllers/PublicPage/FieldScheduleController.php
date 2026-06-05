<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ShowFieldScheduleRequest;
use App\Models\BadmintonField;
use App\Services\Booking\FieldScheduleService;
use Illuminate\Http\JsonResponse;

class FieldScheduleController extends Controller
{
    public function __construct(
        private readonly FieldScheduleService $fieldScheduleService,
    ) {}

    public function show(ShowFieldScheduleRequest $request, string $slug): JsonResponse
    {
        $field = BadmintonField::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $date = $request->validated('date');
        $slots = $this->fieldScheduleService->generateSlots($field, $date);

        return response()->json([
            'data' => [
                'field' => $field->only(['id', 'name', 'slug', 'price_per_hour', 'open_time', 'close_time', 'slot_duration_minutes']),
                'date' => $date,
                'slots' => $slots,
            ],
            'meta' => [
                'open_time' => $this->fieldScheduleService->openTimeFor($field),
                'close_time' => $this->fieldScheduleService->closeTimeFor($field),
                'slot_duration_minutes' => $this->fieldScheduleService->slotDurationMinutesFor($field),
            ],
        ]);
    }
}
