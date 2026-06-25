<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFieldRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_recommendations_endpoint_returns_ranked_fields(): void
    {
        $wifi = Facility::query()->create([
            'name' => 'WiFi',
            'slug' => 'wifi',
            'description' => 'Free internet',
        ]);

        $recommended = BadmintonField::query()->create([
            'name' => 'Recommended Court',
            'slug' => 'recommended-court',
            'address' => 'Jl. Utama 1',
            'latitude' => -2.5897000,
            'longitude' => 140.6690000,
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);
        $recommended->facilities()->attach($wifi->id);

        Booking::query()->create([
            'booking_code' => 'BK-REC-001',
            'badminton_field_id' => $recommended->id,
            'booking_date' => '2026-06-07',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 100000,
        ]);

        $other = BadmintonField::query()->create([
            'name' => 'Other Court',
            'slug' => 'other-court',
            'address' => 'Jl. Jauh 9',
            'latitude' => -2.6900000,
            'longitude' => 140.8200000,
            'price_per_hour' => 130000,
            'is_active' => true,
        ]);

        $this->getJson(route('public.fields.recommendations', [
            'limit' => 2,
            'latitude' => -2.5895000,
            'longitude' => 140.6695000,
            'budget' => 120000,
            'facility_slugs' => ['wifi'],
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.field.slug', 'recommended-court')
            ->assertJsonPath('data.0.field.booking_url', route('public.fields.booking', ['slug' => 'recommended-court']))
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('badminton_fields', [
            'slug' => $other->slug,
        ]);
    }
}
