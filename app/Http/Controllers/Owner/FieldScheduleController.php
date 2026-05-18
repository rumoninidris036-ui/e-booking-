<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

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

    public function show(ShowFieldScheduleRequest $request, BadmintonField $badmintonField): JsonResponse
    {
        $this->authorize('view', $badmintonField);

        $date = $request->validated('date');
        $slots = $this->fieldScheduleService->generateSlots($badmintonField, $date);

        return response()->json([
            'data' => [
                'field' => $badmintonField->only(['id', 'name', 'slug', 'price_per_hour']),
                'date' => $date,
                'slots' => $slots,
            ],
            'meta' => [
                'open_time' => FieldScheduleService::DEFAULT_OPEN_TIME,
                'close_time' => FieldScheduleService::DEFAULT_CLOSE_TIME,
                'slot_duration_minutes' => FieldScheduleService::SLOT_DURATION_MINUTES,
            ],
        ]);
    }
}
