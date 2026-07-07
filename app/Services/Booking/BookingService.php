<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public const EXPIRED_PENDING_BOOKING_REASON = 'Auto-expired after 10 minutes without payment.';

    public function __construct(
        private readonly FieldScheduleService $fieldScheduleService,
    ) {}

    public function create(
        BadmintonField $field,
        ?User $user,
        string $bookingDate,
        string $startTime,
        ?string $endTime = null,
        array $customer = [],
    ): Booking {
        $scheduleDate = CarbonImmutable::createFromFormat('Y-m-d', $bookingDate);
        $slotStart = CarbonImmutable::createFromFormat('H:i', $startTime);
        $slotEnd = $endTime === null
            ? $slotStart->addMinutes($this->fieldScheduleService->slotDurationMinutesFor($field))
            : CarbonImmutable::createFromFormat('H:i', $endTime);

        if ($slotEnd->lte($slotStart)) {
            throw ValidationException::withMessages([
                'end_time' => ['End time must be greater than start time.'],
            ]);
        }

        if (! $this->fieldScheduleService->isBookableSlot($field, $slotStart->format('H:i'), $slotEnd->format('H:i'))) {
            throw ValidationException::withMessages([
                'start_time' => ['Selected time slot is outside the allowed schedule.'],
            ]);
        }

        if ($scheduleDate->isPast() && ! $scheduleDate->isToday()) {
            throw ValidationException::withMessages([
                'booking_date' => ['Booking date must be today or later.'],
            ]);
        }

        $requestedStartDateTime = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $scheduleDate->format('Y-m-d').' '.$slotStart->format('H:i'),
        );

        if ($requestedStartDateTime->isPast()) {
            throw ValidationException::withMessages([
                'start_time' => ['Booking time must be in the future.'],
            ]);
        }

        try {
            return DB::transaction(function () use ($field, $user, $customer, $scheduleDate, $slotStart, $slotEnd): Booking {
                $lockedField = BadmintonField::query()
                    ->whereKey($field->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->expireOverdueBookingsForFieldOnDate($lockedField->id, $scheduleDate->toDateString());

                $conflictExists = Booking::query()
                    ->where('badminton_field_id', $lockedField->id)
                    ->whereDate('booking_date', $scheduleDate->toDateString())
                    ->blocksSchedule()
                    ->where('start_time', '<', $slotEnd->format('H:i:s'))
                    ->where('end_time', '>', $slotStart->format('H:i:s'))
                    ->lockForUpdate()
                    ->exists();

                if ($conflictExists) {
                    throw ValidationException::withMessages([
                        'start_time' => ['Selected slot is already booked.'],
                    ]);
                }

                $bookingAttributes = [
                    'booking_code' => $this->generateBookingCode($scheduleDate->year),
                    'badminton_field_id' => $lockedField->id,
                    'user_id' => $user?->id,
                    'customer_name' => $customer['customer_name'] ?? $user?->name,
                    'customer_contact' => $customer['customer_contact'] ?? null,
                    'customer_email' => $customer['customer_email'] ?? $user?->email,
                    'guest_access_token' => Str::random(64),
                    'booking_date' => $scheduleDate->toDateString(),
                    'start_time' => $slotStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'status' => Booking::STATUS_PENDING,
                    'price_per_hour' => $lockedField->price_per_hour,
                ];

                if (Schema::hasColumn((new Booking())->getTable(), 'expires_at')) {
                    $bookingAttributes['expires_at'] = now()->addMinutes(Booking::PENDING_PAYMENT_TIMEOUT_MINUTES);
                }

                return Booking::query()->create($bookingAttributes)->load(['field:id,name,slug', 'user:id,name,email']);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueSlotViolation($exception)) {
                throw ValidationException::withMessages([
                    'start_time' => ['Selected slot is already booked.'],
                ]);
            }

            throw $exception;
        }
    }

    private function isUniqueSlotViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'bookings_unique_slot')
            || str_contains(strtolower($exception->getMessage()), 'unique constraint failed');
    }

    public function cancel(Booking $booking, User $user, ?string $reason = null): Booking
    {
        if ($booking->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'booking' => ['You are not allowed to cancel this booking.'],
            ]);
        }

        if (! in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_PAID], true)) {
            throw ValidationException::withMessages([
                'status' => ['Only pending or paid bookings can be cancelled.'],
            ]);
        }

        return $this->markBookingAsCancelled($booking, $reason);
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $this->assertValidTransition($booking, $status);

        if ($status === Booking::STATUS_CANCELLED) {
            return $this->markBookingAsCancelled($booking);
        }

        $attributes = [
            'status' => $status,
        ];

        if ($status === Booking::STATUS_PAID) {
            $attributes['paid_at'] = now();
        }

        if ($status === Booking::STATUS_FINISHED) {
            $attributes['finished_at'] = now();
        }

        $booking->forceFill($attributes)->save();

        return $booking->fresh(['field:id,name,slug', 'user:id,name,email']);
    }

    public function expirePendingBooking(Booking $booking): Booking
    {
        if ($booking->status !== Booking::STATUS_PENDING) {
            return $booking->fresh(['field:id,name,slug', 'user:id,name,email']);
        }

        return $this->markBookingAsExpired($booking);
    }

    public function expireOverduePendingBookings(): int
    {
        if (! Schema::hasColumn((new Booking())->getTable(), 'expires_at')) {
            return 0;
        }

        return DB::transaction(function (): int {
            $expiredBookings = Booking::query()
                ->where('status', Booking::STATUS_PENDING)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($expiredBookings as $booking) {
                $this->markBookingAsExpired($booking);
            }

            return $expiredBookings->count();
        });
    }

    private function expireOverdueBookingsForFieldOnDate(int $fieldId, string $bookingDate): void
    {
        if (! Schema::hasColumn((new Booking())->getTable(), 'expires_at')) {
            return;
        }

        $expiredBookings = Booking::query()
            ->where('badminton_field_id', $fieldId)
            ->whereDate('booking_date', $bookingDate)
            ->where('status', Booking::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->lockForUpdate()
            ->get();

        foreach ($expiredBookings as $booking) {
            $this->markBookingAsExpired($booking);
        }
    }

    private function generateBookingCode(int $year): string
    {
        $prefix = sprintf('BK-%d-', $year);

        $lastBooking = Booking::query()
            ->where('booking_code', 'like', $prefix.'%')
            ->orderByDesc('booking_code')
            ->lockForUpdate()
            ->first();

        if ($lastBooking?->booking_code === null) {
            return $prefix.'0001';
        }

        $lastSequence = (int) substr($lastBooking->booking_code, -4);

        return sprintf('%s%04d', $prefix, $lastSequence + 1);
    }

    private function assertValidTransition(Booking $booking, string $targetStatus): void
    {
        $allowedTransitions = [
            Booking::STATUS_PENDING => [Booking::STATUS_PAID, Booking::STATUS_CANCELLED],
            Booking::STATUS_PAID => [Booking::STATUS_FINISHED, Booking::STATUS_CANCELLED],
            Booking::STATUS_CANCELLED => [],
            Booking::STATUS_FINISHED => [],
        ];

        if (! in_array($targetStatus, $allowedTransitions[$booking->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => [sprintf('Booking status cannot change from %s to %s.', $booking->status, $targetStatus)],
            ]);
        }
    }

    private function markBookingAsCancelled(Booking $booking, ?string $reason = null): Booking
    {
        $booking->forceFill([
            'status' => Booking::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ])->save();

        $booking->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_FAILED,
                'failed_at' => now(),
            ]);

        return $booking->fresh(['field:id,name,slug', 'user:id,name,email']);
    }

    private function markBookingAsExpired(Booking $booking): Booking
    {
        $booking->forceFill([
            'status' => Booking::STATUS_EXPIRED,
            'cancellation_reason' => self::EXPIRED_PENDING_BOOKING_REASON,
        ])->save();

        $booking->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_FAILED,
                'failed_at' => now(),
            ]);

        return $booking->fresh(['field:id,name,slug', 'user:id,name,email']);
    }
}
