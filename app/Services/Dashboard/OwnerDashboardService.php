<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function dataFor(User $owner, Request $request): array
    {
        $filters = $this->resolveFilters($request);
        $ownerId = (int) $owner->id;

        $bookingBaseQuery = $this->bookingBaseQuery($ownerId, $filters);
        $successfulPaymentBaseQuery = $this->paymentBaseQuery($ownerId, $filters)
            ->where('payments.status', Payment::STATUS_SUCCESS);

        $summary = [
            'total_bookings' => (clone $bookingBaseQuery)->count(),
            'pending_bookings' => (clone $bookingBaseQuery)->where('bookings.status', Booking::STATUS_PENDING)->count(),
            'paid_bookings' => (clone $bookingBaseQuery)->where('bookings.status', Booking::STATUS_PAID)->count(),
            'finished_bookings' => (clone $bookingBaseQuery)->where('bookings.status', Booking::STATUS_FINISHED)->count(),
            'cancelled_bookings' => (clone $bookingBaseQuery)->where('bookings.status', Booking::STATUS_CANCELLED)->count(),
            'active_fields' => BadmintonField::query()->where('owner_id', $ownerId)->where('is_active', true)->count(),
            'total_fields' => BadmintonField::query()->where('owner_id', $ownerId)->count(),
            'total_revenue' => (float) (clone $successfulPaymentBaseQuery)->sum('payments.amount'),
            'successful_transactions' => (clone $successfulPaymentBaseQuery)->count(),
        ];

        $summary['average_revenue_per_booking'] = $summary['successful_transactions'] > 0
            ? round($summary['total_revenue'] / $summary['successful_transactions'], 2)
            : 0.0;

        return [
            'filters' => $filters,
            'summary' => $summary,
            'trends' => $this->trends($ownerId, $filters),
            'peak_hours' => $this->peakHours($ownerId, $filters),
            'busy_schedules' => $this->busySchedules($ownerId, $filters),
            'field_statistics' => $this->fieldStatistics($ownerId, $filters),
            'recent_bookings' => $this->recentBookings($ownerId, $filters),
            'recent_transactions' => $this->recentTransactions($ownerId, $filters),
            'notifications' => $this->notifications($ownerId),
            'map_fields' => $this->mapFields($ownerId, $filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        $period = $request->string('period')->toString() ?: '7_days';
        $today = CarbonImmutable::today();

        [$dateFrom, $dateTo] = match ($period) {
            'today' => [$today, $today],
            'month' => [$today->startOfMonth(), $today],
            'custom' => [
                CarbonImmutable::parse($request->string('date_from')->toString() ?: $today->subDays(6)->toDateString()),
                CarbonImmutable::parse($request->string('date_to')->toString() ?: $today->toDateString()),
            ],
            default => [$today->subDays(6), $today],
        };

        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'field_id' => $request->filled('field_id') ? (int) $request->integer('field_id') : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function bookingBaseQuery(int $ownerId, array $filters): Builder
    {
        return Booking::query()
            ->join('badminton_fields', 'badminton_fields.id', '=', 'bookings.badminton_field_id')
            ->where('badminton_fields.owner_id', $ownerId)
            ->when($filters['field_id'] !== null, fn ($query) => $query->where('bookings.badminton_field_id', $filters['field_id']))
            ->whereBetween('bookings.booking_date', [$filters['date_from'], $filters['date_to']]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paymentBaseQuery(int $ownerId, array $filters): Builder
    {
        return Payment::query()
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('badminton_fields', 'badminton_fields.id', '=', 'bookings.badminton_field_id')
            ->where('badminton_fields.owner_id', $ownerId)
            ->when($filters['field_id'] !== null, fn ($query) => $query->where('bookings.badminton_field_id', $filters['field_id']))
            ->whereBetween('bookings.booking_date', [$filters['date_from'], $filters['date_to']]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function trends(int $ownerId, array $filters): array
    {
        $period = CarbonPeriod::create($filters['date_from'], $filters['date_to']);

        return collect($period)->map(function ($date) use ($ownerId, $filters): array {
            $dateString = CarbonImmutable::parse($date)->toDateString();

            $bookingQuery = $this->bookingBaseQuery($ownerId, [
                ...$filters,
                'date_from' => $dateString,
                'date_to' => $dateString,
            ]);

            $revenueQuery = $this->paymentBaseQuery($ownerId, [
                ...$filters,
                'date_from' => $dateString,
                'date_to' => $dateString,
            ])->where('payments.status', Payment::STATUS_SUCCESS);

            return [
                'date' => $dateString,
                'label' => CarbonImmutable::parse($dateString)->format('D'),
                'total_bookings' => (clone $bookingQuery)->count(),
                'total_revenue' => (float) $revenueQuery->sum('payments.amount'),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function peakHours(int $ownerId, array $filters): array
    {
        return $this->bookingBaseQuery($ownerId, $filters)
            ->select([
                'bookings.start_time',
                'bookings.end_time',
                DB::raw('COUNT(*) as total_bookings'),
            ])
            ->groupBy('bookings.start_time', 'bookings.end_time')
            ->orderByDesc('total_bookings')
            ->orderBy('bookings.start_time')
            ->limit(5)
            ->get()
            ->map(fn ($slot): array => [
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
                'total_bookings' => (int) $slot->total_bookings,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function busySchedules(int $ownerId, array $filters): array
    {
        return $this->bookingBaseQuery($ownerId, $filters)
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
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
                'total_bookings' => (int) $slot->total_bookings,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function fieldStatistics(int $ownerId, array $filters): array
    {
        return DB::table('badminton_fields')
            ->leftJoin('bookings', function ($join) use ($filters): void {
                $join->on('bookings.badminton_field_id', '=', 'badminton_fields.id')
                    ->whereBetween('bookings.booking_date', [$filters['date_from'], $filters['date_to']]);
            })
            ->leftJoin('payments', function ($join): void {
                $join->on('payments.booking_id', '=', 'bookings.id')
                    ->where('payments.status', '=', Payment::STATUS_SUCCESS);
            })
            ->where('badminton_fields.owner_id', $ownerId)
            ->when($filters['field_id'] !== null, fn ($query) => $query->where('badminton_fields.id', $filters['field_id']))
            ->groupBy('badminton_fields.id', 'badminton_fields.name', 'badminton_fields.slug', 'badminton_fields.is_active', 'badminton_fields.latitude', 'badminton_fields.longitude')
            ->select([
                'badminton_fields.id',
                'badminton_fields.name',
                'badminton_fields.slug',
                'badminton_fields.is_active',
                'badminton_fields.latitude',
                'badminton_fields.longitude',
                DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                DB::raw("COUNT(DISTINCT CASE WHEN bookings.status = '".Booking::STATUS_PENDING."' THEN bookings.id END) as pending_bookings"),
                DB::raw("COUNT(DISTINCT CASE WHEN bookings.status = '".Booking::STATUS_PAID."' THEN bookings.id END) as paid_bookings"),
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
                'latitude' => $field->latitude !== null ? (float) $field->latitude : null,
                'longitude' => $field->longitude !== null ? (float) $field->longitude : null,
                'total_bookings' => (int) $field->total_bookings,
                'pending_bookings' => (int) $field->pending_bookings,
                'paid_bookings' => (int) $field->paid_bookings,
                'total_revenue' => (float) $field->total_revenue,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function recentBookings(int $ownerId, array $filters): array
    {
        return Booking::query()
            ->with(['field:id,name,slug,owner_id', 'user:id,name,email', 'payments' => fn ($query) => $query->latest('id')])
            ->whereHas('field', fn ($query) => $query->where('owner_id', $ownerId))
            ->when($filters['field_id'] !== null, fn ($query) => $query->where('badminton_field_id', $filters['field_id']))
            ->whereBetween('booking_date', [$filters['date_from'], $filters['date_to']])
            ->latest('booking_date')
            ->latest('start_time')
            ->limit(8)
            ->get()
            ->map(function (Booking $booking): array {
                $payment = $booking->payments->first();

                return [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'customer_name' => $booking->customer_name ?: $booking->user?->name ?: 'Customer',
                    'field_name' => $booking->field?->name,
                    'booking_date' => $booking->booking_date?->format('Y-m-d'),
                    'start_time' => substr((string) $booking->start_time, 0, 5),
                    'end_time' => substr((string) $booking->end_time, 0, 5),
                    'status' => $booking->status,
                    'payment_status' => $payment?->status,
                    'amount' => $payment !== null ? (float) $payment->amount : (float) $booking->price_per_hour,
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(int $ownerId, array $filters): array
    {
        return Payment::query()
            ->with(['booking.field:id,name,slug,owner_id', 'booking.user:id,name,email'])
            ->whereHas('booking.field', fn ($query) => $query->where('owner_id', $ownerId))
            ->when($filters['field_id'] !== null, fn ($query) => $query->whereHas('booking', fn ($bookingQuery) => $bookingQuery->where('badminton_field_id', $filters['field_id'])))
            ->whereHas('booking', fn ($query) => $query->whereBetween('booking_date', [$filters['date_from'], $filters['date_to']]))
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'booking_code' => $payment->booking?->booking_code,
                'customer_name' => $payment->booking?->customer_name ?: $payment->booking?->user?->name ?: 'Customer',
                'field_name' => $payment->booking?->field?->name,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i'),
                'invoice_number' => $payment->invoice_number,
                'invoice_download_url' => $payment->invoice_pdf_path !== null ? route('payments.invoice.download', $payment) : null,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notifications(int $ownerId): array
    {
        return Booking::query()
            ->with(['field:id,name,owner_id'])
            ->whereHas('field', fn ($query) => $query->where('owner_id', $ownerId))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Booking $booking): array => [
                'title' => match ($booking->status) {
                    Booking::STATUS_PAID => 'Payment sukses',
                    Booking::STATUS_CANCELLED => 'Booking dibatalkan',
                    Booking::STATUS_FINISHED => 'Booking selesai',
                    default => 'Booking baru',
                },
                'message' => sprintf('%s - %s', $booking->booking_code, $booking->field?->name ?? 'Lapangan'),
                'status' => $booking->status,
                'time' => $booking->updated_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function mapFields(int $ownerId, array $filters): array
    {
        return collect($this->fieldStatistics($ownerId, $filters))
            ->filter(fn (array $field): bool => $field['latitude'] !== null && $field['longitude'] !== null)
            ->map(fn (array $field): array => [
                'id' => $field['id'],
                'name' => $field['name'],
                'latitude' => $field['latitude'],
                'longitude' => $field['longitude'],
                'status' => $field['is_active'] ? 'active' : 'nonactive',
                'total_bookings' => $field['total_bookings'],
                'total_revenue' => $field['total_revenue'],
            ])
            ->values()
            ->all();
    }
}
