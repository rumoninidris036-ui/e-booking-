<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PublicFieldsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_fields_page_lists_active_courts(): void
    {
        BadmintonField::query()->create([
            'name' => 'Court Public Page',
            'slug' => 'court-public-page',
            'address' => 'Jl. Public Court',
            'latitude' => -2.5895123,
            'longitude' => 140.6687412,
            'price_per_hour' => 125000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'Court Draft Hidden',
            'slug' => 'court-draft-hidden',
            'price_per_hour' => 90000,
            'is_active' => false,
        ]);

        $this->get(route('public.fields.index'))
            ->assertOk()
            ->assertSee('Explore Courts')
            ->assertSee('Court Public Page')
            ->assertSee(route('public.fields.show', ['slug' => 'court-public-page']))
            ->assertSee(route('public.fields.booking', ['slug' => 'court-public-page']))
            ->assertDontSee('Court Draft Hidden');
    }

    public function test_public_fields_page_filters_by_location(): void
    {
        BadmintonField::query()->create([
            'name' => 'Central Arena',
            'slug' => 'central-arena',
            'address' => 'Jl. Merdeka Raya',
            'price_per_hour' => 120000,
            'is_active' => true,
        ]);

        BadmintonField::query()->create([
            'name' => 'North Hall',
            'slug' => 'north-hall',
            'address' => 'Jl. Utama Timur',
            'price_per_hour' => 110000,
            'is_active' => true,
        ]);

        $this->get(route('public.fields.index', ['location' => 'merdeka']))
            ->assertOk()
            ->assertSee('Central Arena')
            ->assertDontSee('North Hall');
    }

    public function test_public_fields_page_filters_out_busy_courts_for_selected_time_window(): void
    {
        $date = '2026-07-01';

        $availableField = BadmintonField::query()->create([
            'name' => 'Available Court',
            'slug' => 'available-court',
            'price_per_hour' => 100000,
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'is_active' => true,
        ]);

        $busyField = BadmintonField::query()->create([
            'name' => 'Busy Court',
            'slug' => 'busy-court',
            'price_per_hour' => 100000,
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'is_active' => true,
        ]);

        Booking::query()->create([
            'booking_code' => 'BOOK-BUSY-001',
            'badminton_field_id' => $busyField->id,
            'booking_date' => CarbonImmutable::createFromFormat('Y-m-d', $date),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 100000,
        ]);

        $this->get(route('public.fields.index', [
            'date' => $date,
            'time' => 'evening',
        ]))
            ->assertOk()
            ->assertSee('Available Court')
            ->assertDontSee('Busy Court');
    }

    public function test_public_fields_suggestions_return_available_city_names(): void
    {
        $date = '2026-07-01';

        $availableField = BadmintonField::query()->create([
            'name' => 'Ambon Prime Court',
            'slug' => 'ambon-prime-court',
            'address' => 'Jl. Pantai Mardika No. 12, Ambon, Maluku',
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $busyField = BadmintonField::query()->create([
            'name' => 'Papua Elite Hall',
            'slug' => 'papua-elite-hall',
            'address' => 'Jl. Cenderawasih No. 8, Jayapura, Papua',
            'open_time' => '06:00:00',
            'close_time' => '23:00:00',
            'price_per_hour' => 175000,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'booking_code' => 'BOOK-PAPUA-001',
            'badminton_field_id' => $busyField->id,
            'booking_date' => CarbonImmutable::createFromFormat('Y-m-d', $date),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 175000,
        ]);

        $this->getJson(route('public.fields.suggestions', [
            'q' => 'a',
            'date' => $date,
            'time' => 'evening',
        ]))
            ->assertOk()
            ->assertJsonFragment(['value' => 'Ambon'])
            ->assertJsonMissing(['value' => 'Papua']);
    }
}
