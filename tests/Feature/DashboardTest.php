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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_returns_booking_revenue_and_field_statistics(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $customer = User::factory()->create();

        $fieldOne = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court A',
            'slug' => 'court-a',
            'price_per_hour' => 80000,
            'is_active' => true,
        ]);

        $fieldTwo = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court B',
            'slug' => 'court-b',
            'price_per_hour' => 90000,
            'is_active' => false,
        ]);

        $bookingOne = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $fieldOne->id,
            'user_id' => $customer->id,
            'booking_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PAID,
            'price_per_hour' => 80000,
        ]);

        $bookingTwo = Booking::query()->create([
            'booking_code' => 'BK-2026-0002',
            'badminton_field_id' => $fieldTwo->id,
            'user_id' => $customer->id,
            'booking_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => Booking::STATUS_PENDING,
            'price_per_hour' => 90000,
        ]);

        Payment::query()->create([
            'booking_id' => $bookingOne->id,
            'provider' => 'midtrans',
            'order_id' => $bookingOne->booking_code,
            'amount' => 80000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        Payment::query()->create([
            'booking_id' => $bookingTwo->id,
            'provider' => 'midtrans',
            'order_id' => $bookingTwo->booking_code,
            'amount' => 90000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($owner)->getJson(route('owner.dashboard'));

        $response->assertOk()
            ->assertJsonPath('data.summary.total_bookings', 2)
            ->assertJsonPath('data.summary.paid_bookings', 1)
            ->assertJsonPath('data.summary.total_revenue', 80000)
            ->assertJsonCount(2, 'data.field_statistics')
            ->assertJsonPath('data.busy_schedules.0.total_bookings', 2);
    }

    public function test_admin_dashboard_returns_user_transaction_field_and_booking_monitoring(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('owner', 'web');
        Role::findOrCreate('customer', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Admin',
            'slug' => 'court-admin',
            'price_per_hour' => 85000,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-2026-0001',
            'badminton_field_id' => $field->id,
            'user_id' => $customer->id,
            'booking_date' => '2026-05-22',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Booking::STATUS_FINISHED,
            'price_per_hour' => 85000,
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'order_id' => $booking->booking_code,
            'amount' => 85000,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard'));

        $response->assertOk()
            ->assertJsonPath('data.summary.total_users', 3)
            ->assertJsonPath('data.summary.total_transactions', 1)
            ->assertJsonPath('data.summary.total_transaction_amount', 85000)
            ->assertJsonPath('data.field_monitoring.total_fields', 1)
            ->assertJsonPath('data.booking_monitoring.finished_bookings', 1)
            ->assertJsonCount(1, 'data.field_monitoring.recent_fields')
            ->assertJsonCount(1, 'data.booking_monitoring.recent_bookings');
    }
}
