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

    public function test_homepage_search_uses_tfidf_query(): void
    {
        $tribun = Facility::query()->create([
            'name' => 'Tribun',
            'slug' => 'tribun',
            'description' => 'Area tribun',
        ]);

        $searchHit = BadmintonField::query()->create([
            'name' => 'Tribun Court',
            'slug' => 'tribun-court',
            'address' => 'Jl. Utama 1',
            'latitude' => -2.5897000,
            'longitude' => 140.6690000,
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);
        $searchHit->facilities()->attach($tribun->id);

        BadmintonField::query()->create([
            'name' => 'Plain Court',
            'slug' => 'plain-court',
            'address' => 'Jl. Jauh 9',
            'latitude' => -2.6900000,
            'longitude' => 140.8200000,
            'price_per_hour' => 130000,
            'is_active' => true,
        ]);

        $this->get('/?q=ada+tribun')
            ->assertOk()
            ->assertSee('Tribun Court')
            ->assertDontSee('Plain Court');
    }

    public function test_homepage_search_uses_combined_keyword_and_location_query(): void
    {
        $wifi = Facility::query()->create([
            'name' => 'WiFi',
            'slug' => 'wifi',
            'description' => 'Free internet',
        ]);

        $sentaniWifi = BadmintonField::query()->create([
            'name' => 'Sentani WiFi Court',
            'slug' => 'sentani-wifi-court',
            'address' => 'Jl. Raya Sentani No. 1, Sentani, Jayapura',
            'open_time' => '06:00:00',
            'close_time' => '12:00:00',
            'price_per_hour' => 120000,
            'is_active' => true,
        ]);
        $sentaniWifi->facilities()->attach($wifi->id);

        $jayapuraNoWifi = BadmintonField::query()->create([
            'name' => 'Jayapura Court',
            'slug' => 'jayapura-court',
            'address' => 'Jl. Kota Baru No. 9, Jayapura',
            'price_per_hour' => 120000,
            'is_active' => true,
        ]);

        $this->get('/?q=sentani+wifi&time=morning')
            ->assertOk()
            ->assertSee('Sentani WiFi Court')
            ->assertDontSee('Jayapura Court');
    }

    public function test_homepage_search_filters_by_morning_time_window(): void
    {
        BadmintonField::query()->create([
            'name' => 'Morning Exact Court',
            'slug' => 'morning-court',
            'address' => 'Jl. Pagi 1',
            'open_time' => '06:00:00',
            'close_time' => '12:00:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'Morning Partial Court',
            'slug' => 'morning-partial-court',
            'address' => 'Jl. Pagi 2',
            'open_time' => '08:00:00',
            'close_time' => '23:00:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'Morning Wrong Close Court',
            'slug' => 'night-court',
            'address' => 'Jl. Malam 1',
            'open_time' => '17:00:00',
            'close_time' => '23:00:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        $this->get('/?q=court&time=morning')
            ->assertOk()
            ->assertSee('Morning Exact Court')
            ->assertSee('Morning Partial Court')
            ->assertSeeInOrder(['Morning Exact Court', 'Morning Partial Court'])
            ->assertDontSee('Morning Wrong Close Court');
    }

    public function test_homepage_search_filters_by_afternoon_time_window(): void
    {
        BadmintonField::query()->create([
            'name' => 'Day Court',
            'slug' => 'day-court',
            'address' => 'Jl. Siang 1',
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'Early Court',
            'slug' => 'early-court',
            'address' => 'Jl. Pagi 2',
            'open_time' => '06:00:00',
            'close_time' => '11:30:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        $this->get('/?q=court&time=afternoon')
            ->assertOk()
            ->assertSee('Day Court')
            ->assertDontSee('Early Court');
    }

    public function test_homepage_search_filters_by_evening_time_window(): void
    {
        BadmintonField::query()->create([
            'name' => 'Evening Court',
            'slug' => 'evening-court',
            'address' => 'Jl. Sore 1',
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'Late Afternoon Court',
            'slug' => 'late-afternoon-court',
            'address' => 'Jl. Sore 2',
            'open_time' => '06:00:00',
            'close_time' => '16:30:00',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        $this->get('/?q=court&time=evening')
            ->assertOk()
            ->assertSee('Evening Court')
            ->assertDontSee('Late Afternoon Court');
    }
}
