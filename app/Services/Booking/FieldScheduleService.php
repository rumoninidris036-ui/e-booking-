<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Models\BadmintonField;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FieldScheduleService
{
    public const DEFAULT_OPEN_TIME = '08:00';

    public const DEFAULT_CLOSE_TIME = '22:00';

    public const SLOT_DURATION_MINUTES = 60;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateSlots(BadmintonField $field, string $date): array
    {
        $scheduleDate = CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
        $openAt = $scheduleDate->setTimeFromTimeString(self::DEFAULT_OPEN_TIME);
        $closeAt = $scheduleDate->setTimeFromTimeString(self::DEFAULT_CLOSE_TIME);

        $bookings = $field->bookings()
            ->whereDate('booking_date', $scheduleDate->toDateString())
            ->whereIn('status', Booking::ACTIVE_SLOT_STATUSES)
            ->orderBy('start_time')
            ->get();

        $slots = [];

        for ($cursor = $openAt; $cursor->lt($closeAt); $cursor = $cursor->addMinutes(self::SLOT_DURATION_MINUTES)) {
            $slotEnd = $cursor->addMinutes(self::SLOT_DURATION_MINUTES);

            if ($slotEnd->gt($closeAt)) {
                break;
            }

            $matchingBooking = $this->findBookingForSlot($bookings, $cursor->format('H:i:s'), $slotEnd->format('H:i:s'));

            $slots[] = [
                'date' => $scheduleDate->toDateString(),
                'start_time' => $cursor->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'status' => $matchingBooking === null ? 'available' : 'booked',
                'booking_id' => $matchingBooking?->id,
            ];
        }

        return $slots;
    }

    public function isBookableSlot(string $startTime, string $endTime): bool
    {
        $openAt = $this->timeToMinutes(self::DEFAULT_OPEN_TIME);
        $closeAt = $this->timeToMinutes(self::DEFAULT_CLOSE_TIME);
        $slotStart = $this->timeToMinutes($startTime);
        $slotEnd = $this->timeToMinutes($endTime);

        if ($slotEnd <= $slotStart) {
            return false;
        }

        if ($slotStart < $openAt || $slotEnd > $closeAt) {
            return false;
        }

        return ($slotEnd - $slotStart) === self::SLOT_DURATION_MINUTES;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function findBookingForSlot(Collection $bookings, string $slotStart, string $slotEnd): ?Booking
    {
        return $bookings->first(
            fn (Booking $booking): bool => $booking->start_time < $slotEnd && $booking->end_time > $slotStart,
        );
    }
}
