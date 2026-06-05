<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ShowFieldScheduleRequest;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Services\Booking\FieldScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldScheduleController extends Controller
{
    public function __construct(
        private readonly FieldScheduleService $fieldScheduleService,
    ) {}

    public function index(Request $request): View
    {
        $owner = $request->user();
        $fields = BadmintonField::query()
            ->where('owner_id', $owner->id)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'price_per_hour', 'open_time', 'close_time', 'slot_duration_minutes']);

        $selectedField = $request->filled('field_id')
            ? $fields->firstWhere('id', (int) $request->integer('field_id'))
            : $fields->first();

        $date = $request->string('date')->toString() ?: CarbonImmutable::today()->toDateString();
        $slots = $selectedField === null
            ? []
            : $this->fieldScheduleService->generateSlots($selectedField, $date);

        $bookingIds = collect($slots)
            ->pluck('booking_id')
            ->filter()
            ->values();

        $bookings = Booking::query()
            ->with(['field:id,name,slug,owner_id', 'user:id,name,email', 'payments' => fn ($query) => $query->latest('id')])
            ->whereIn('id', $bookingIds)
            ->get()
            ->keyBy('id');

        $summary = [
            'total_slots' => count($slots),
            'available_slots' => collect($slots)->where('status', 'available')->count(),
            'booked_slots' => collect($slots)->where('status', 'booked')->count(),
            'pending_bookings' => $bookings->where('status', Booking::STATUS_PENDING)->count(),
            'paid_bookings' => $bookings->where('status', Booking::STATUS_PAID)->count(),
        ];

        return view('owner.schedules.index', [
            'owner' => $owner,
            'fields' => $fields,
            'selectedField' => $selectedField,
            'date' => $date,
            'slots' => $slots,
            'bookings' => $bookings,
            'summary' => $summary,
        ]);
    }

    public function show(ShowFieldScheduleRequest $request, BadmintonField $badmintonField): JsonResponse
    {
        $this->authorize('view', $badmintonField);

        $date = $request->validated('date');
        $slots = $this->fieldScheduleService->generateSlots($badmintonField, $date);

        return response()->json([
            'data' => [
                'field' => $badmintonField->only(['id', 'name', 'slug', 'price_per_hour', 'open_time', 'close_time', 'slot_duration_minutes']),
                'date' => $date,
                'slots' => $slots,
            ],
            'meta' => [
                'open_time' => $this->fieldScheduleService->openTimeFor($badmintonField),
                'close_time' => $this->fieldScheduleService->closeTimeFor($badmintonField),
                'slot_duration_minutes' => $this->fieldScheduleService->slotDurationMinutesFor($badmintonField),
            ],
        ]);
    }
}
