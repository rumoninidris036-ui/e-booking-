<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_recommended_courts(): void
    {
        $wifi = Facility::query()->create([
            'name' => 'WiFi',
            'slug' => 'wifi',
            'description' => 'Free internet',
        ]);

        $recommended = BadmintonField::query()->create([
            'name' => 'Home Court',
            'slug' => 'home-court',
            'address' => 'Jl. Rumah 1',
            'latitude' => -2.5897000,
            'longitude' => 140.6690000,
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);
        $recommended->facilities()->attach($wifi->id);

        Booking::query()->create([
            'booking_code' => 'BK-HOME-001',
            'badminton_field_id' => $recommended->id,
            'booking_date' => '2026-06-07',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 100000,
        ]);

        BadmintonField::query()->create([
            'name' => 'Hidden Court',
            'slug' => 'hidden-court',
            'price_per_hour' => 50000,
            'is_active' => false,
        ]);

        $this->get(url('/'))
            ->assertOk()
            ->assertSee('Lapangan Rekomendasi')
            ->assertSee('Home Court')
            ->assertDontSee('Hidden Court');
    }
}
