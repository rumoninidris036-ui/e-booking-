<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestRatingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_is_only_available_after_booking_is_finished(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'Arena Rating',
            'slug' => 'arena-rating',
            'price_per_hour' => 75000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0099',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Tamu Rating',
            'booking_date' => '2026-08-07',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 75000,
        ]);

        $url = URL::signedRoute('public.rating.create', ['booking' => $booking->id]);

        $this->get($url)->assertForbidden();

        $booking->forceFill(['status' => Booking::STATUS_FINISHED])->save();

        $this->get($url)->assertOk();
    }
}
