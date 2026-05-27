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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
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
            ? $slotStart->addMinutes(FieldScheduleService::SLOT_DURATION_MINUTES)
            : CarbonImmutable::createFromFormat('H:i', $endTime);

        if (! $this->fieldScheduleService->isBookableSlot($slotStart->format('H:i'), $slotEnd->format('H:i'))) {
            throw ValidationException::withMessages([
                'start_time' => ['Selected time slot is outside the allowed schedule.'],
            ]);
        }

        if ($scheduleDate->isPast() && ! $scheduleDate->isToday()) {
            throw ValidationException::withMessages([
                'booking_date' => ['Booking date must be today or later.'],
            ]);
        }

        try {
            return DB::transaction(function () use ($field, $user, $customer, $scheduleDate, $slotStart, $slotEnd): Booking {
                $conflictExists = Booking::query()
                    ->where('badminton_field_id', $field->id)
                    ->whereDate('booking_date', $scheduleDate->toDateString())
                    ->whereIn('status', Booking::ACTIVE_SLOT_STATUSES)
                    ->where('start_time', '<', $slotEnd->format('H:i:s'))
                    ->where('end_time', '>', $slotStart->format('H:i:s'))
                    ->lockForUpdate()
                    ->exists();

                if ($conflictExists) {
                    throw ValidationException::withMessages([
                        'start_time' => ['Selected slot is already booked.'],
                    ]);
                }

                return Booking::query()->create([
                    'booking_code' => $this->generateBookingCode($scheduleDate->year),
                    'badminton_field_id' => $field->id,
                    'user_id' => $user?->id,
                    'customer_name' => $customer['customer_name'] ?? $user?->name,
                    'customer_contact' => $customer['customer_contact'] ?? null,
                    'customer_email' => $customer['customer_email'] ?? $user?->email,
                    'guest_access_token' => $user === null ? Str::random(64) : null,
                    'booking_date' => $scheduleDate->toDateString(),
                    'start_time' => $slotStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'status' => Booking::STATUS_PENDING,
                    'price_per_hour' => $field->price_per_hour,
                ])->load(['field:id,name,slug', 'user:id,name,email']);
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

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $this->assertValidTransition($booking, $status);

        $attributes = [
            'status' => $status,
        ];

        if ($status === Booking::STATUS_PAID) {
            $attributes['paid_at'] = now();
        }

        if ($status === Booking::STATUS_FINISHED) {
            $attributes['finished_at'] = now();
        }

        if ($status === Booking::STATUS_CANCELLED) {
            $attributes['cancelled_at'] = now();
        }

        $booking->forceFill($attributes)->save();

        if ($status === Booking::STATUS_CANCELLED) {
            $booking->payments()
                ->where('status', Payment::STATUS_PENDING)
                ->update([
                    'status' => Payment::STATUS_FAILED,
                    'failed_at' => now(),
                ]);
        }

        return $booking->fresh(['field:id,name,slug', 'user:id,name,email']);
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
}
