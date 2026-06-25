<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendations;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Facility;
use App\Services\Recommendations\FieldRecommendationCriteria;
use App\Services\Recommendations\FieldRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_the_best_matching_fields(): void
    {
        $wifi = Facility::query()->create([
            'name' => 'WiFi',
            'slug' => 'wifi',
            'description' => 'Free internet',
        ]);

        $shower = Facility::query()->create([
            'name' => 'Shower',
            'slug' => 'shower',
            'description' => 'Shower room',
        ]);

        $premium = BadmintonField::query()->create([
            'name' => 'Premium Court',
            'slug' => 'premium-court',
            'address' => 'Jl. Premium 1',
            'latitude' => -2.5900000,
            'longitude' => 140.6700000,
            'price_per_hour' => 110000,
            'is_active' => true,
        ]);
        $premium->facilities()->attach([$wifi->id, $shower->id]);

        Booking::query()->create([
            'booking_code' => 'BK-PRM-001',
            'badminton_field_id' => $premium->id,
            'booking_date' => '2026-06-05',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 110000,
        ]);
        Booking::query()->create([
            'booking_code' => 'BK-PRM-002',
            'badminton_field_id' => $premium->id,
            'booking_date' => '2026-06-06',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => Booking::STATUS_FINISHED,
            'price_per_hour' => 110000,
        ]);

        $budget = BadmintonField::query()->create([
            'name' => 'Budget Court',
            'slug' => 'budget-court',
            'address' => 'Jl. Budget 2',
            'latitude' => -2.6200000,
            'longitude' => 140.7100000,
            'price_per_hour' => 85000,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-BDG-001',
            'badminton_field_id' => $budget->id,
            'booking_date' => '2026-06-06',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 85000,
        ]);

        $inactive = BadmintonField::query()->create([
            'name' => 'Inactive Court',
            'slug' => 'inactive-court',
            'price_per_hour' => 50000,
            'is_active' => false,
        ]);

        $criteria = FieldRecommendationCriteria::fromArray([
            'limit' => 2,
            'latitude' => -2.5895000,
            'longitude' => 140.6695000,
            'budget' => 120000,
            'facility_slugs' => ['wifi'],
        ]);

        $results = app(FieldRecommendationService::class)->recommend($criteria);

        $this->assertCount(2, $results);
        $this->assertSame('premium-court', $results->first()['field']->slug);
        $this->assertContains('Fasilitasnya sesuai preferensi', $results->first()['reasons']);
        $this->assertContains('Dekat dari lokasi kamu', $results->first()['reasons']);
        $this->assertNotContains($inactive->slug, $results->pluck('field.slug')->all());
    }
}
