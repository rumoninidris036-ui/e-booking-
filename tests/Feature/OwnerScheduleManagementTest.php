<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_schedule_page_for_selected_field(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Schedule Owner',
            'slug' => 'court-schedule-owner',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-OWNER-SCHEDULE',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Schedule Customer',
            'customer_contact' => '08123456789',
            'booking_date' => '2026-06-08',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 100000,
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => $booking->booking_code,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $this->actingAs($owner)
            ->get(route('owner.schedules.index', [
                'field_id' => $field->id,
                'date' => '2026-06-08',
            ]))
            ->assertOk()
            ->assertSee('Jadwal Lapangan')
            ->assertSee('Court Schedule Owner')
            ->assertSee('BK-OWNER-SCHEDULE')
            ->assertSee('Schedule Customer')
            ->assertSee('Available');
    }

    public function test_owner_field_schedule_json_endpoint_still_returns_slots(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Schedule Json',
            'slug' => 'court-schedule-json',
            'price_per_hour' => 100000,
            'open_time' => '08:00',
            'close_time' => '09:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-OWNER-SCHEDULE-JSON',
            'badminton_field_id' => $field->id,
            'booking_date' => '2026-06-08',
            'start_time' => '08:00:00',
            'end_time' => '08:30:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 100000,
        ]);

        $this->actingAs($owner)
            ->getJson(route('owner.fields.schedule', [
                'badmintonField' => $field,
                'date' => '2026-06-08',
            ]))
            ->assertOk()
            ->assertJsonPath('data.field.name', 'Court Schedule Json')
            ->assertJsonPath('data.slots.0.status', 'booked')
            ->assertJsonPath('data.slots.1.status', 'available')
            ->assertJsonPath('data.slots.0.start_time', '08:00')
            ->assertJsonPath('data.slots.0.end_time', '08:30')
            ->assertJsonPath('data.slots.1.start_time', '08:30')
            ->assertJsonPath('data.slots.1.end_time', '09:00')
            ->assertJsonPath('meta.open_time', '08:00')
            ->assertJsonPath('meta.close_time', '09:00')
            ->assertJsonPath('meta.slot_duration_minutes', 30);
    }
}
