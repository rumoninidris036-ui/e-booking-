<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->with(['field:id,name,slug,owner_id', 'user:id,name,email'])
            ->where('user_id', $request->user()->id)
            ->when(
                $request->string('status')->isNotEmpty(),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
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

    public function store(StoreBookingRequest $request, string $slug): JsonResponse|RedirectResponse
    {
        $field = BadmintonField::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validated();

        $booking = $this->bookingService->create(
            field: $field,
            user: $request->user(),
            bookingDate: $validated['booking_date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'] ?? null,
            customer: [
                'customer_name' => $validated['customer_name'] ?? $request->user()?->name,
                'customer_contact' => $validated['customer_contact'] ?? null,
                'customer_email' => $validated['customer_email'] ?? $request->user()?->email,
            ],
        );

        if (! $request->expectsJson()) {
            $payment = $this->paymentService->createOrGetSnapPayment(
                $booking->loadMissing(['field', 'user']),
            );

            return redirect()
                ->to(route('payments.show', array_filter([
                    'payment' => $payment,
                    'access_token' => $booking->guest_access_token,
                ])))
                ->with('status', 'Booking berhasil dibuat. Lanjutkan ke pembayaran untuk mengamankan slot.');
        }

        return response()->json([
            'message' => 'Booking created successfully.',
            'data' => $booking,
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return response()->json([
            'data' => $booking->load(['field:id,name,slug,price_per_hour,owner_id', 'user:id,name,email']),
        ]);
    }

    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookingService->cancel(
            booking: $booking,
            user: $request->user(),
            reason: $request->validated('reason'),
        );

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'data' => $booking,
        ]);
    }
}
