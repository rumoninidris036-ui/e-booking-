<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ownerId = $request->user()->id;

        $bookingBaseQuery = Booking::query()
            ->whereHas('field', fn ($query) => $query->where('owner_id', $ownerId));

        $successfulPaymentBaseQuery = Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereHas('booking.field', fn ($query) => $query->where('owner_id', $ownerId));

        $fieldStatistics = $this->fieldStatistics($ownerId);
        $busySchedules = $this->busySchedules($ownerId);

        return response()->json([
            'data' => [
                'summary' => [
                    'total_bookings' => (clone $bookingBaseQuery)->count(),
                    'pending_bookings' => (clone $bookingBaseQuery)->where('status', Booking::STATUS_PENDING)->count(),
                    'paid_bookings' => (clone $bookingBaseQuery)->where('status', Booking::STATUS_PAID)->count(),
                    'finished_bookings' => (clone $bookingBaseQuery)->where('status', Booking::STATUS_FINISHED)->count(),
                    'cancelled_bookings' => (clone $bookingBaseQuery)->where('status', Booking::STATUS_CANCELLED)->count(),
                    'total_revenue' => (float) ((clone $successfulPaymentBaseQuery)->sum('amount')),
                    'successful_transactions' => (clone $successfulPaymentBaseQuery)->count(),
                ],
                'busy_schedules' => $busySchedules,
                'field_statistics' => $fieldStatistics,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function busySchedules(int $ownerId): array
    {
        return Booking::query()
            ->join('badminton_fields', 'badminton_fields.id', '=', 'bookings.badminton_field_id')
            ->where('badminton_fields.owner_id', $ownerId)
            ->select([
                'bookings.booking_date',
                'bookings.start_time',
                'bookings.end_time',
                DB::raw('COUNT(*) as total_bookings'),
            ])
            ->groupBy('bookings.booking_date', 'bookings.start_time', 'bookings.end_time')
            ->orderByDesc('total_bookings')
            ->orderBy('bookings.booking_date')
            ->orderBy('bookings.start_time')
            ->limit(5)
            ->get()
            ->map(fn ($slot): array => [
                'booking_date' => $slot->booking_date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'total_bookings' => (int) $slot->total_bookings,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fieldStatistics(int $ownerId): array
    {
        return DB::table('badminton_fields')
            ->leftJoin('bookings', 'bookings.badminton_field_id', '=', 'badminton_fields.id')
            ->leftJoin('payments', function ($join): void {
                $join->on('payments.booking_id', '=', 'bookings.id')
                    ->where('payments.status', '=', Payment::STATUS_SUCCESS);
            })
            ->where('badminton_fields.owner_id', $ownerId)
            ->groupBy('badminton_fields.id', 'badminton_fields.name', 'badminton_fields.slug', 'badminton_fields.is_active')
            ->select([
                'badminton_fields.id',
                'badminton_fields.name',
                'badminton_fields.slug',
                'badminton_fields.is_active',
                DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                DB::raw('COALESCE(SUM(payments.amount), 0) as total_revenue'),
            ])
            ->orderByDesc('total_bookings')
            ->orderBy('badminton_fields.name')
            ->get()
            ->map(fn ($field): array => [
                'id' => $field->id,
                'name' => $field->name,
                'slug' => $field->slug,
                'is_active' => (bool) $field->is_active,
                'total_bookings' => (int) $field->total_bookings,
                'total_revenue' => (float) $field->total_revenue,
            ])
            ->all();
    }
}
