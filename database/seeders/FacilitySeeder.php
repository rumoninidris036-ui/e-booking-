<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            'Toilet',
            'Mushola',
            'Kantin',
            'Parkiran',
            'WiFi',
        ];

        foreach ($facilities as $facilityName) {
            Facility::query()->firstOrCreate(
                ['slug' => Str::slug($facilityName)],
                [
                    'name' => $facilityName,
                    'description' => sprintf('Fasilitas %s tersedia untuk pengunjung.', $facilityName),
                ],
            );
        }
    }
}
