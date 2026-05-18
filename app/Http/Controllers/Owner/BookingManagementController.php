<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingManagementController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->with(['field:id,name,slug,owner_id', 'user:id,name,email'])
            ->whereHas('field', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->filled('field_id'),
                fn ($query) => $query->where('badminton_field_id', (int) $request->integer('field_id')),
            )
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('booking_date', (string) $request->string('date')),
            )
            ->latest('booking_date')
            ->latest('start_time')
            ->paginate(10);

        return response()->json([
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('ownerView', $booking);

        return response()->json([
            'data' => $booking->load(['field:id,name,slug,price_per_hour,owner_id', 'user:id,name,email']),
        ]);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('ownerUpdateStatus', $booking);

        $validated = $request->validated();
        $updatedBooking = $this->bookingService->updateStatus($booking, $validated['status']);

        if ($validated['status'] === Booking::STATUS_CANCELLED && array_key_exists('cancellation_reason', $validated)) {
            $updatedBooking->forceFill([
                'cancellation_reason' => $validated['cancellation_reason'],
            ])->save();
            $updatedBooking = $updatedBooking->fresh(['field:id,name,slug', 'user:id,name,email']);
        }

        return response()->json([
            'message' => 'Booking status updated successfully.',
            'data' => $updatedBooking,
        ]);
    }
}
