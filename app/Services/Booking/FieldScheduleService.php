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

    public const DEFAULT_SLOT_DURATION_MINUTES = 60;

    public const SLOT_DURATION_MINUTES = self::DEFAULT_SLOT_DURATION_MINUTES;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generateSlots(BadmintonField $field, string $date): array
    {
        $scheduleDate = CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
        $openAt = $scheduleDate->setTimeFromTimeString($this->openTimeFor($field));
        $closeAt = $scheduleDate->setTimeFromTimeString($this->closeTimeFor($field));
        $slotDurationMinutes = $this->slotDurationMinutesFor($field);

        $bookings = $field->bookings()
            ->whereDate('booking_date', $scheduleDate->toDateString())
            ->blocksSchedule()
            ->orderBy('start_time')
            ->get();

        $slots = [];

        for ($cursor = $openAt; $cursor->lt($closeAt); $cursor = $cursor->addMinutes($slotDurationMinutes)) {
            $slotEnd = $cursor->addMinutes($slotDurationMinutes);

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

    public function isBookableSlot(BadmintonField $field, string $startTime, string $endTime): bool
    {
        $openAt = $this->timeToMinutes($this->openTimeFor($field));
        $closeAt = $this->timeToMinutes($this->closeTimeFor($field));
        $slotStart = $this->timeToMinutes($startTime);
        $slotEnd = $this->timeToMinutes($endTime);
        $slotDurationMinutes = $this->slotDurationMinutesFor($field);

        if ($slotEnd <= $slotStart) {
            return false;
        }

        if ($slotStart < $openAt || $slotEnd > $closeAt) {
            return false;
        }

        return ($slotEnd - $slotStart) === $slotDurationMinutes;
    }

    public function openTimeFor(BadmintonField $field): string
    {
        return substr((string) ($field->open_time ?? self::DEFAULT_OPEN_TIME), 0, 5);
    }

    public function closeTimeFor(BadmintonField $field): string
    {
        return substr((string) ($field->close_time ?? self::DEFAULT_CLOSE_TIME), 0, 5);
    }

    public function slotDurationMinutesFor(BadmintonField $field): int
    {
        return (int) ($field->slot_duration_minutes ?: self::DEFAULT_SLOT_DURATION_MINUTES);
    }

    public static function isValidScheduleWindow(string $openTime, string $closeTime, int $slotDurationMinutes): bool
    {
        if ($slotDurationMinutes < 30 || $slotDurationMinutes > 240) {
            return false;
        }

        $openAt = self::timeToMinutesValue($openTime);
        $closeAt = self::timeToMinutesValue($closeTime);

        if ($openAt === null || $closeAt === null || $closeAt <= $openAt) {
            return false;
        }

        return ($closeAt - $openAt) >= $slotDurationMinutes;
    }

    private function timeToMinutes(string $time): int
    {
        return self::timeToMinutesValue($time) ?? 0;
    }

    private static function timeToMinutesValue(string $time): ?int
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

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
