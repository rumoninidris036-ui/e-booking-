<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creation_generates_pending_booking_code(): void
    {
        $user = User::factory()->create();
        $bookingDate = now()->addDay()->format('Y-m-d');
        $field = BadmintonField::query()->create([
            'name' => 'Arena Booking',
            'slug' => 'arena-booking',
            'price_per_hour' => 80000,
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
            ->assertJsonPath('data.booking_code', 'BK-2026-0001');
    }

    public function test_user_can_view_booking_history_and_detail(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Histori',
            'slug' => 'arena-histori',
            'price_per_hour' => 60000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 60000,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-2026-0002',
            'badminton_field_id' => $field->id,
            'user_id' => $otherUser->id,
            'booking_date' => '2026-05-21',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 60000,
        ]);

        $historyResponse = $this->actingAs($user)->getJson(route('bookings.index'));
        $historyResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', 'BK-2026-0001');

        $detailResponse = $this->actingAs($user)->getJson(route('bookings.show', $booking));
        $detailResponse->assertOk()
            ->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.booking_code', 'BK-2026-0001');
    }

    public function test_user_can_cancel_booking_with_reason(): void
    {
        $user = User::factory()->create();
        $field = BadmintonField::query()->create([
            'name' => 'Arena Cancel',
            'slug' => 'arena-cancel',
            'price_per_hour' => 50000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $user->id,
            'booking_date' => '2026-05-21',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 50000,
        ]);

        $response = $this->actingAs($user)->patchJson(route('bookings.cancel', $booking), [
            'reason' => 'Ada keperluan mendadak',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_CANCELLED)
            ->assertJsonPath('data.cancellation_reason', 'Ada keperluan mendadak');
    }

    public function test_owner_can_view_and_update_booking_status(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $customer = User::factory()->create();
        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Arena Owner Booking',
            'slug' => 'arena-owner-booking',
            'price_per_hour' => 70000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $customer->id,
            'booking_date' => '2026-05-21',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 70000,
        ]);

        $indexResponse = $this->actingAs($owner)->getJson(route('owner.bookings.index'));
        $indexResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', 'BK-2026-0001');

        $showResponse = $this->actingAs($owner)->getJson(route('owner.bookings.show', $booking));
        $showResponse->assertOk()
            ->assertJsonPath('data.id', $booking->id);

        $paidResponse = $this->actingAs($owner)->patchJson(route('owner.bookings.update-status', $booking), [
            'status' => Booking::STATUS_PAID,
        ]);
        $paidResponse->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_PAID);

        $finishedResponse = $this->actingAs($owner)->patchJson(route('owner.bookings.update-status', $booking), [
            'status' => Booking::STATUS_FINISHED,
        ]);
        $finishedResponse->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_FINISHED);
    }
}
