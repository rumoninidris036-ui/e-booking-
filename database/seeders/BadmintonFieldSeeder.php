<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BadmintonField;
use App\Models\Facility;
use App\Models\User;
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
