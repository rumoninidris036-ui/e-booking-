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

class OwnerBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_booking_list_page(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Booking Owner',
            'slug' => 'court-booking-owner',
            'price_per_hour' => 100000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-OWNER-BOOKING',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Customer Booking',
            'customer_contact' => '08123456789',
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 100000,
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => $booking->booking_code,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($owner)
            ->get(route('owner.bookings.index'))
            ->assertOk()
            ->assertSee('Daftar Booking')
            ->assertSee('BK-OWNER-BOOKING')
            ->assertSee('Customer Booking')
            ->assertSee('Court Booking Owner')
            ->assertSee('Mark Paid');
    }

    public function test_owner_booking_index_returns_summary_and_filters_json(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Filter Booking',
            'slug' => 'court-filter-booking',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        $paidBooking = Booking::query()->create([
            'booking_code' => 'BK-OWNER-PAID',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Paid Customer',
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 90000,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-OWNER-PENDING',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Pending Customer',
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 90000,
        ]);

        Payment::query()->create([
            'booking_id' => $paidBooking->id,
            'provider' => 'midtrans',
            'order_id' => $paidBooking->booking_code,
            'amount' => 90000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $this->actingAs($owner)
            ->getJson(route('owner.bookings.index', ['status' => Booking::STATUS_PAID, 'field_id' => $field->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', 'BK-OWNER-PAID')
            ->assertJsonPath('meta.summary.total_bookings', 2)
            ->assertJsonPath('meta.summary.pending_bookings', 1)
            ->assertJsonPath('meta.summary.paid_bookings', 1)
            ->assertJsonPath('meta.summary.total_revenue', 90000);
    }

    public function test_owner_booking_summary_revenue_follows_filters(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Revenue Filter',
            'slug' => 'court-revenue-filter',
            'price_per_hour' => 90000,
            'is_active' => true,
        ]);

        $dateA = now()->addDay()->format('Y-m-d');
        $dateB = now()->addDays(2)->format('Y-m-d');

        $bookingA = Booking::query()->create([
            'booking_code' => 'BK-OWNER-REV-A',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Revenue A',
            'booking_date' => $dateA,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 90000,
        ]);

        $bookingB = Booking::query()->create([
            'booking_code' => 'BK-OWNER-REV-B',
            'badminton_field_id' => $field->id,
            'customer_name' => 'Revenue B',
            'booking_date' => $dateB,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 90000,
        ]);

        Payment::query()->create([
            'booking_id' => $bookingA->id,
            'provider' => 'midtrans',
            'order_id' => $bookingA->booking_code,
            'amount' => 90000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        Payment::query()->create([
            'booking_id' => $bookingB->id,
            'provider' => 'midtrans',
            'order_id' => $bookingB->booking_code,
            'amount' => 180000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $this->actingAs($owner)
            ->getJson(route('owner.bookings.index', ['date' => $dateA]))
            ->assertOk()
            ->assertJsonPath('meta.summary.total_bookings', 1)
            ->assertJsonPath('meta.summary.paid_bookings', 1)
            ->assertJsonPath('meta.summary.total_revenue', 90000);
    }

    public function test_owner_can_update_booking_status_from_web_page(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Status Booking',
            'slug' => 'court-status-booking',
            'price_per_hour' => 95000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-OWNER-STATUS',
            'badminton_field_id' => $field->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 95000,
        ]);

        $this->actingAs($owner)
            ->patch(route('owner.bookings.update-status', $booking), [
                'status' => Booking::STATUS_PAID,
            ])
            ->assertRedirect(route('owner.bookings.index', ['focus' => $booking->id]));

        $booking->refresh();
        $this->assertSame(Booking::STATUS_PAID, $booking->status);
    }

    public function test_owner_cannot_force_expired_status_through_request(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Status Validation',
            'slug' => 'court-status-validation',
            'price_per_hour' => 95000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-OWNER-VALIDATION',
            'badminton_field_id' => $field->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 95000,
        ]);

        $this->actingAs($owner)
            ->patchJson(route('owner.bookings.update-status', $booking), [
                'status' => Booking::STATUS_EXPIRED,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_PENDING, $booking->status);
    }
}
