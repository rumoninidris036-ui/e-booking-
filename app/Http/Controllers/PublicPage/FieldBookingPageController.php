<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use App\Services\Booking\FieldScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldBookingPageController extends Controller
{
    private const BOOKING_WINDOW_DAYS = 14;

    public function __construct(
        private readonly FieldScheduleService $fieldScheduleService,
    ) {}

    public function show(Request $request, string $slug): View
    {
        $field = BadmintonField::query()
            ->with(['facilities', 'owner:id,name'])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $windowStart = now()->addDay()->startOfDay()->toImmutable();
        $windowEnd = $windowStart->addDays(self::BOOKING_WINDOW_DAYS - 1);

        $selectedDate = $request->string('date')->toString();
        $date = $windowStart;

        if ($selectedDate !== '') {
            try {
                $parsedDate = CarbonImmutable::createFromFormat('Y-m-d', $selectedDate)->startOfDay();

                if ($parsedDate->betweenIncluded($windowStart, $windowEnd)) {
                    $date = $parsedDate;
                }
            } catch (\Throwable) {
                $date = $windowStart;
            }
        }

        $selectedSlot = $request->string('slot')->toString();
        $slots = $this->fieldScheduleService->generateSlots($field, $date->toDateString());

        $dateOptions = collect(range(0, self::BOOKING_WINDOW_DAYS - 1))
            ->map(fn (int $offset): CarbonImmutable => $windowStart->addDays($offset))
            ->all();

        return view('public.fields.booking', [
            'field' => $field,
            'slots' => $slots,
            'selectedDate' => $date,
            'selectedSlot' => $selectedSlot,
            'dateOptions' => $dateOptions,
            'mapMeta' => [
                'provider' => 'OpenStreetMap',
                'library' => 'Leaflet.js',
                'marker' => $field->map_marker,
            ],
        ]);
    }
}
