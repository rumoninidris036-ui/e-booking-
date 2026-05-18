<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    public function ownerView(User $user, Booking $booking): bool
    {
        return $user->hasRole('owner') && $booking->field?->owner_id === $user->id;
    }

    public function ownerUpdateStatus(User $user, Booking $booking): bool
    {
        return $user->hasRole('owner') && $booking->field?->owner_id === $user->id;
    }
}
