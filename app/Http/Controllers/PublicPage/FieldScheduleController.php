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
                'field' => $field->only(['id', 'name', 'slug', 'price_per_hour']),
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
