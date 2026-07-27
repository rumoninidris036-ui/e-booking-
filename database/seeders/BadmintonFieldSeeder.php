<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BadmintonField;
use App\Models\Facility;
use App\Models\User;
use App\Services\Booking\FieldScheduleService;
use Illuminate\Database\Seeder;

class BadmintonFieldSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@ebooking.test')->first();

        if ($owner === null) {
            return;
        }

        $fields = [
            [
                'name' => 'Olympic Arena',
                'slug' => 'olympic-arena',
                'description' => 'Lapangan badminton premium dengan pencahayaan terang, karpet empuk, dan atmosfer kompetitif untuk sesi sparring maupun turnamen komunitas.',
                'address' => 'Jl. Ahmad Yani No. 1224, Abepura, Jayapura',
                'latitude' => -2.5895123,
                'longitude' => 140.6687412,
                'price_per_hour' => 250000,
                'is_active' => true,
                'facility_slugs' => ['toilet', 'mushola', 'kantin', 'parkiran', 'wifi'],
            ],
            [
                'name' => 'Grand Central Court',
                'slug' => 'grand-central-court',
                'description' => 'Venue indoor dengan nuansa modern dan ventilasi baik, cocok untuk latihan rutin, sparring malam, dan sesi bareng klub.',
                'address' => 'Jl. Raya Sentani No. 88, Sentani, Jayapura',
                'latitude' => -2.5769874,
                'longitude' => 140.5123401,
                'price_per_hour' => 300000,
                'is_active' => true,
                'facility_slugs' => ['toilet', 'kantin', 'parkiran', 'wifi'],
            ],
            [
                'name' => 'Velocity X Hall',
                'slug' => 'velocity-x-hall',
                'description' => 'Lapangan dengan gaya futuristik untuk pemain yang suka sesi intens, lengkap dengan area tunggu dan fasilitas dasar yang nyaman.',
                'address' => 'Jl. Dok II Atas No. 15, Jayapura Utara, Jayapura',
                'latitude' => -2.5332458,
                'longitude' => 140.7189234,
                'price_per_hour' => 450000,
                'is_active' => true,
                'facility_slugs' => ['toilet', 'mushola', 'parkiran', 'wifi'],
            ],
            [
                'name' => 'Shuttle Pro Court',
                'slug' => 'shuttle-pro-court',
                'description' => 'Cocok untuk pencarian court dengan shower, loker, dan area tribun. Keyword seperti shower atau loker harus mudah ketemu.',
                'address' => 'Jl. Angkasa No. 20, Abepura, Jayapura',
                'latitude' => -2.5931122,
                'longitude' => 140.6802211,
                'price_per_hour' => 180000,
                'is_active' => true,
                'facility_slugs' => ['toilet', 'shower', 'loker', 'tribun'],
            ],
            [
                'name' => 'Community Smash Court',
                'slug' => 'community-smash-court',
                'description' => 'Venue ramah komunitas dengan kantin, parkiran luas, dan wifi untuk pemain yang cari court santai.',
                'address' => 'Jl. Sentosa No. 7, Heram, Jayapura',
                'latitude' => -2.6124410,
                'longitude' => 140.6943300,
                'price_per_hour' => 95000,
                'is_active' => true,
                'facility_slugs' => ['kantin', 'parkiran', 'wifi'],
            ],
            [
                'name' => 'Pagi 1 Court',
                'slug' => 'pagi-1-court',
                'description' => 'Lapangan untuk pencarian slot pagi, buka lebih awal, cocok buat latihan sebelum kerja.',
                'address' => 'Jl. Pagi No. 1, Abepura, Jayapura',
                'latitude' => -2.6001100,
                'longitude' => 140.6801100,
                'price_per_hour' => 110000,
                'is_active' => true,
                'open_time' => '06:00:00',
                'close_time' => '11:00:00',
                'slot_duration_minutes' => 60,
                'facility_slugs' => ['toilet', 'parkiran'],
            ],
            [
                'name' => 'Siang 1 Court',
                'slug' => 'siang-1-court',
                'description' => 'Lapangan buat slot siang, operasional penuh dan cocok untuk cari court di jam kerja santai.',
                'address' => 'Jl. Siang No. 1, Sentani, Jayapura',
                'latitude' => -2.5852200,
                'longitude' => 140.5352200,
                'price_per_hour' => 125000,
                'is_active' => true,
                'open_time' => '12:00:00',
                'close_time' => '17:00:00',
                'slot_duration_minutes' => 60,
                'facility_slugs' => ['toilet', 'kantin'],
            ],
            [
                'name' => 'Malam 1 Court',
                'slug' => 'malam-1-court',
                'description' => 'Lapangan untuk slot malam, terang dan nyaman untuk main selepas aktivitas utama.',
                'address' => 'Jl. Malam No. 1, Jayapura Utara, Jayapura',
                'latitude' => -2.5303300,
                'longitude' => 140.7203300,
                'price_per_hour' => 140000,
                'is_active' => true,
                'open_time' => '17:00:00',
                'close_time' => '23:00:00',
                'slot_duration_minutes' => 90,
                'facility_slugs' => ['toilet', 'mushola', 'wifi'],
            ],
        ];

        foreach ($fields as $payload) {
            $field = BadmintonField::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'owner_id' => $owner->id,
                    'name' => $payload['name'],
                    'description' => $payload['description'],
                    'address' => $payload['address'],
                    'latitude' => $payload['latitude'],
                    'longitude' => $payload['longitude'],
                    'price_per_hour' => $payload['price_per_hour'],
                    'open_time' => $payload['open_time'] ?? FieldScheduleService::DEFAULT_OPEN_TIME,
                    'close_time' => $payload['close_time'] ?? FieldScheduleService::DEFAULT_CLOSE_TIME,
                    'slot_duration_minutes' => $payload['slot_duration_minutes'] ?? FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES,
                    'is_active' => $payload['is_active'],
                ],
            );

            $facilityIds = Facility::query()
                ->whereIn('slug', $payload['facility_slugs'])
                ->pluck('id');

            $field->facilities()->sync($facilityIds);
        }
    }
}
