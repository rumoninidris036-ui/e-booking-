<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_schedule_returns_generated_slots_with_booking_status(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'Arena Pagi',
            'slug' => 'arena-pagi',
            'price_per_hour' => 50000,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'badminton_field_id' => $field->id,
            'booking_date' => '2026-05-20',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 50000,
        ]);

        $response = $this->getJson(route('public.fields.schedule', [
            'slug' => $field->slug,
            'date' => '2026-05-20',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.date', '2026-05-20')
            ->assertJsonPath('data.slots.0.status', 'available')
            ->assertJsonPath('data.slots.1.start_time', '09:00')
            ->assertJsonPath('data.slots.1.status', 'booked');
    }

    public function test_authenticated_user_can_book_available_slot(): void
    {
        $user = User::factory()->create();
        $bookingDate = now()->addDay()->format('Y-m-d');
        $field = BadmintonField::query()->create([
            'name' => 'Arena Sore',
            'slug' => 'arena-sore',
            'price_per_hour' => 75000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('public.fields.bookings.store', [
            'slug' => $field->slug,
        ]), [
            'booking_date' => $bookingDate,
            'start_time' => '08:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Booking::STATUS_PENDING)
            ->assertJsonPath('data.start_time', '08:00:00')
            ->assertJsonPath('data.end_time', '09:00:00');

        $this->assertTrue(
            Booking::query()
                ->where('badminton_field_id', $field->id)
                ->where('user_id', $user->id)
                ->whereDate('booking_date', $bookingDate)
                ->where('start_time', '08:00:00')
                ->where('end_time', '09:00:00')
                ->where('status', Booking::STATUS_PENDING)
                ->exists(),
        );
    }

    public function test_custom_field_schedule_generates_shorter_slots_and_booking_duration(): void
    {
        $user = User::factory()->create();
        $bookingDate = now()->addDay()->format('Y-m-d');
        $field = BadmintonField::query()->create([
            'name' => 'Arena Custom Slot',
            'slug' => 'arena-custom-slot',
            'price_per_hour' => 75000,
            'open_time' => '08:00',
            'close_time' => '09:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $scheduleResponse = $this->getJson(route('public.fields.schedule', [
            'slug' => $field->slug,
            'date' => $bookingDate,
        ]));

        $scheduleResponse->assertOk()
            ->assertJsonCount(2, 'data.slots')
            ->assertJsonPath('data.slots.0.start_time', '08:00')
            ->assertJsonPath('data.slots.0.end_time', '08:30')
            ->assertJsonPath('data.slots.1.start_time', '08:30')
            ->assertJsonPath('data.slots.1.end_time', '09:00')
            ->assertJsonPath('meta.slot_duration_minutes', 30);

        $bookingResponse = $this->actingAs($user)->postJson(route('public.fields.bookings.store', [
            'slug' => $field->slug,
        ]), [
            'booking_date' => $bookingDate,
            'start_time' => '08:30',
        ]);

        $bookingResponse->assertCreated()
            ->assertJsonPath('data.start_time', '08:30:00')
            ->assertJsonPath('data.end_time', '09:00:00');
    }

    public function test_booking_endpoint_prevents_double_booking(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $bookingDate = now()->addDay()->format('Y-m-d');
        $field = BadmintonField::query()->create([
            'name' => 'Arena Malam',
            'slug' => 'arena-malam',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'badminton_field_id' => $field->id,
            'user_id' => $firstUser->id,
            'booking_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 90000,
        ]);

        $response = $this->actingAs($secondUser)->postJson(route('public.fields.bookings.store', [
            'slug' => $field->slug,
        ]), [
            'booking_date' => $bookingDate,
            'start_time' => '10:00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_expired_pending_bookings_do_not_block_the_slot_anymore(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $bookingDate = now()->addDay()->format('Y-m-d');
        $field = BadmintonField::query()->create([
            'name' => 'Arena Expired Slot',
            'slug' => 'arena-expired-slot',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        Booking::query()->create([
            'badminton_field_id' => $field->id,
            'user_id' => $firstUser->id,
            'booking_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
            'price_per_hour' => 90000,
        ]);

        $scheduleResponse = $this->getJson(route('public.fields.schedule', [
            'slug' => $field->slug,
            'date' => $bookingDate,
        ]));

        $scheduleResponse->assertOk()
            ->assertJsonPath('data.slots.2.status', 'available');

        $bookingResponse = $this->actingAs($secondUser)->postJson(route('public.fields.bookings.store', [
            'slug' => $field->slug,
        ]), [
            'booking_date' => $bookingDate,
            'start_time' => '10:00',
        ]);

        $bookingResponse->assertCreated()
            ->assertJsonPath('data.status', Booking::STATUS_PENDING);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_owner_can_view_schedule_for_owned_field(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Arena Owner',
            'slug' => 'arena-owner',
            'price_per_hour' => 65000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->getJson(route('owner.fields.schedule', [
            'badmintonField' => $field->id,
            'date' => '2026-05-20',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.field.id', $field->id)
            ->assertJsonPath('meta.slot_duration_minutes', 60);
    }
}
