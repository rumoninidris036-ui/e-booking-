<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerFieldManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_my_fields_page(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Owner Page',
            'slug' => 'court-owner-page',
            'price_per_hour' => 100000,
            'is_active' => true,
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $this->actingAs($owner)
            ->get(route('owner.fields.index'))
            ->assertOk()
            ->assertSee('Lapangan Saya')
            ->assertSee('Court Owner Page')
            ->assertSee('create-map');
    }

    public function test_owner_field_index_returns_summary_and_filtered_fields(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $activeField = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Arena Aktif',
            'slug' => 'arena-aktif',
            'price_per_hour' => 100000,
            'is_active' => true,
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Arena Draft',
            'slug' => 'arena-draft',
            'price_per_hour' => 80000,
            'is_active' => false,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-OWNER-FIELD',
            'badminton_field_id' => $activeField->id,
            'booking_date' => now()->format('Y-m-d'),
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
            ->getJson(route('owner.fields.index', ['status' => 'active', 'sort' => 'revenue']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Arena Aktif')
            ->assertJsonPath('meta.summary.total_fields', 2)
            ->assertJsonPath('meta.summary.active_fields', 1)
            ->assertJsonPath('meta.summary.mapped_fields', 1)
            ->assertJsonPath('meta.summary.total_bookings', 1)
            ->assertJsonPath('meta.summary.total_revenue', 100000);
    }

    public function test_owner_can_create_field_with_osm_coordinates(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $facility = Facility::query()->create([
            'name' => 'Parkiran',
            'slug' => 'parkiran',
        ]);

        $response = $this->actingAs($owner)->post(route('owner.fields.store'), [
            'name' => 'Court Pin Baru',
            'description' => 'Lapangan dengan pin OSM.',
            'address' => 'Jl. OSM Test',
            'latitude' => '-3.7012345',
            'longitude' => '128.1901234',
            'price_per_hour' => '125000',
            'open_time' => '08:00',
            'close_time' => '23:00',
            'slot_duration_minutes' => '30',
            'is_active' => '1',
            'facility_ids' => [$facility->id],
        ]);

        $field = BadmintonField::query()->where('slug', 'court-pin-baru')->firstOrFail();

        $response->assertRedirect(route('owner.fields.index'));
        $this->assertSame($owner->id, $field->owner_id);
        $this->assertSame('-3.7012345', (string) $field->latitude);
        $this->assertSame('128.1901234', (string) $field->longitude);
        $this->assertSame('08:00', substr((string) $field->open_time, 0, 5));
        $this->assertSame('23:00', substr((string) $field->close_time, 0, 5));
        $this->assertSame(30, $field->slot_duration_minutes);
        $this->assertTrue($field->facilities()->whereKey($facility->id)->exists());
    }

    public function test_owner_can_create_field_when_close_time_has_unused_remainder(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $response = $this->actingAs($owner)->post(route('owner.fields.store'), [
            'name' => 'Court Remainder Slot',
            'address' => 'Siale',
            'latitude' => '-3.6960821',
            'longitude' => '128.1839531',
            'price_per_hour' => '20000',
            'open_time' => '08:30',
            'close_time' => '23:45',
            'slot_duration_minutes' => '30',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('owner.fields.index'));

        $field = BadmintonField::query()->where('slug', 'court-remainder-slot')->firstOrFail();
        $this->assertSame('08:30', substr((string) $field->open_time, 0, 5));
        $this->assertSame('23:45', substr((string) $field->close_time, 0, 5));
        $this->assertSame(30, $field->slot_duration_minutes);
    }

    public function test_custom_schedule_ignores_close_time_remainder_when_generating_slots(): void
    {
        $field = BadmintonField::query()->create([
            'name' => 'Court Remainder Schedule',
            'slug' => 'court-remainder-schedule',
            'price_per_hour' => 20000,
            'open_time' => '08:30',
            'close_time' => '09:45',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('public.fields.schedule', [
            'slug' => $field->slug,
            'date' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data.slots')
            ->assertJsonPath('data.slots.0.start_time', '08:30')
            ->assertJsonPath('data.slots.0.end_time', '09:00')
            ->assertJsonPath('data.slots.1.start_time', '09:00')
            ->assertJsonPath('data.slots.1.end_time', '09:30')
            ->assertJsonPath('meta.close_time', '09:45');
    }

    public function test_owner_can_update_field_coordinates_from_map_pin(): void
    {
        Role::findOrCreate('owner', 'web');

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $field = BadmintonField::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Court Update Pin',
            'slug' => 'court-update-pin',
            'price_per_hour' => 100000,
            'is_active' => true,
            'latitude' => -3.6954,
            'longitude' => 128.1814,
        ]);

        $response = $this->actingAs($owner)->put(route('owner.fields.update', $field), [
            'name' => 'Court Update Pin',
            'description' => 'Koordinat diperbarui.',
            'address' => 'Alamat baru',
            'latitude' => '-3.7123456',
            'longitude' => '128.2012345',
            'price_per_hour' => '150000',
            'open_time' => '09:00',
            'close_time' => '22:00',
            'slot_duration_minutes' => '60',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('owner.fields.index', ['focus' => $field->id]));

        $field->refresh();
        $this->assertSame('-3.7123456', (string) $field->latitude);
        $this->assertSame('128.2012345', (string) $field->longitude);
        $this->assertSame('09:00', substr((string) $field->open_time, 0, 5));
        $this->assertSame('22:00', substr((string) $field->close_time, 0, 5));
        $this->assertSame(60, $field->slot_duration_minutes);
        $this->assertSame('Alamat baru', $field->address);
    }
}
