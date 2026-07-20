<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BookingManagementController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $owner = $request->user();
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'status' => $request->string('status')->toString() ?: 'all',
            'field_id' => $request->filled('field_id') ? (int) $request->integer('field_id') : null,
            'date' => $request->string('date')->toString(),
        ];

        $bookings = Booking::query()
            ->with([
                'field:id,name,slug,owner_id',
                'user:id,name,email',
                'payments' => fn($query) => $query->latest('id'),
            ])
            ->whereHas('field', fn($query) => $query->where('owner_id', $owner->id))
            ->where(function ($query): void {
                $query->where('status', '!=', Booking::STATUS_PENDING)
                    ->orWhere(function ($query): void {
                        $query->where('status', Booking::STATUS_PENDING)
                            ->where(function ($query): void {
                                $query->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            });
                    });
            })
            ->when(
                $filters['status'] !== 'all',
                fn($query) => $query->where('status', $filters['status']),
            )
            ->when(
                $filters['field_id'] !== null,
                fn($query) => $query->where('badminton_field_id', $filters['field_id']),
            )
            ->when(
                $filters['date'] !== '',
                fn($query) => $query->whereDate('booking_date', $filters['date']),
            )
            ->when(
                $filters['search'] !== '',
                function ($query) use ($filters): void {
                    $query->where(function ($query) use ($filters): void {
                        $query
                            ->where('booking_code', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('customer_contact', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('customer_email', 'like', '%' . $filters['search'] . '%')
                            ->orWhereHas('user', fn($query) => $query->where('name', 'like', '%' . $filters['search'] . '%')->orWhere('email', 'like', '%' . $filters['search'] . '%'));
                    });
                },
            )
            ->latest('booking_date')
            ->latest('start_time')
            ->paginate(10)
            ->withQueryString();

        $summary = $this->summaryForOwner((int) $owner->id, $filters);
        $fields = BadmintonField::query()
            ->where('owner_id', $owner->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        if (! $request->expectsJson()) {
            return view('owner.bookings.index', [
                'bookings' => $bookings,
                'fields' => $fields,
                'filters' => $filters,
                'summary' => $summary,
                'owner' => $owner,
            ]);
        }

        return response()->json([
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'filters' => $filters,
                'summary' => $summary,
            ],
        ]);
    }

    public function show(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorize('ownerView', $booking);

        if (! $request->expectsJson()) {
            return redirect()->route('owner.bookings.index', ['focus' => $booking->id]);
        }

        return response()->json([
            'data' => $booking->load(['field:id,name,slug,price_per_hour,owner_id', 'user:id,name,email', 'payments']),
        ]);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse|RedirectResponse
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

        if (! $request->expectsJson()) {
            return redirect()
                ->route('owner.bookings.index', ['focus' => $updatedBooking->id])
                ->with('status', sprintf('Status booking %s diperbarui menjadi %s.', $updatedBooking->booking_code, $updatedBooking->status));
        }

        return response()->json([
            'message' => 'Booking status updated successfully.',
            'data' => $updatedBooking,
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function summaryForOwner(int $ownerId, array $filters): array
    {
        unset($filters['status']);

        $bookingQuery = Booking::query()
            ->whereHas('field', fn($query) => $query->where('owner_id', $ownerId));

        $bookingQuery = $this->applyBookingFilters($bookingQuery, $filters);

        $visibleBookingQuery = $this->applyVisibilityScope(clone $bookingQuery);
        $pendingBookingQuery = (clone $bookingQuery)->where('status', Booking::STATUS_PENDING);

        if (Schema::hasColumn((new Booking())->getTable(), 'expires_at')) {
            $pendingBookingQuery->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        $totalRevenue = Payment::query()
            ->where('payments.status', Payment::STATUS_SUCCESS)
            ->whereIn('payments.booking_id', (clone $visibleBookingQuery)->select('bookings.id'))
            ->sum('payments.amount');

        return [
            'total_bookings' => $visibleBookingQuery->count(),
            'pending_bookings' => $pendingBookingQuery->count(),
            'paid_bookings' => (clone $bookingQuery)->where('status', Booking::STATUS_PAID)->count(),
            'finished_bookings' => (clone $bookingQuery)->where('status', Booking::STATUS_FINISHED)->count(),
            'cancelled_bookings' => (clone $bookingQuery)->where('status', Booking::STATUS_CANCELLED)->count(),
            'total_revenue' => (float) $totalRevenue,
        ];
    }

    /**
     * @param  array<string, string|int|null>  $filters
     */
    private function applyBookingFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->when(
                ($filters['status'] ?? 'all') !== 'all',
                fn($query) => $query->where('status', $filters['status']),
            )
            ->when(
                ($filters['field_id'] ?? null) !== null,
                fn($query) => $query->where('badminton_field_id', $filters['field_id']),
            )
            ->when(
                ($filters['date'] ?? '') !== '',
                fn($query) => $query->whereDate('booking_date', $filters['date']),
            )
            ->when(
                ($filters['search'] ?? '') !== '',
                function ($query) use ($filters): void {
                    $query->where(function ($query) use ($filters): void {
                        $query
                            ->where('booking_code', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('customer_contact', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('customer_email', 'like', '%' . $filters['search'] . '%')
                            ->orWhereHas('user', fn($query) => $query->where('name', 'like', '%' . $filters['search'] . '%')->orWhere('email', 'like', '%' . $filters['search'] . '%'));
                    });
                },
            );
    }

    private function applyVisibilityScope(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($query): void {
            $query->where('bookings.status', '!=', Booking::STATUS_PENDING)
                ->orWhere(function ($query): void {
                    $query->where('bookings.status', Booking::STATUS_PENDING)
                        ->where(function ($query): void {
                            $query->whereNull('bookings.expires_at')
                                ->orWhere('bookings.expires_at', '>', now());
                        });
                });
        });
    }
}
