<?php

declare(strict_types=1);

namespace App\Actions\Field;

use App\Models\BadmintonField;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBadmintonFieldAction
{
    /**
     * @param  array{name: string, description?: string|null, address?: string|null, latitude?: numeric-string|int|float|null, longitude?: numeric-string|int|float|null, price_per_hour: numeric-string|int|float, open_time: string, close_time: string, slot_duration_minutes: int, is_active?: bool, facility_ids?: array<int, int>}  $attributes
     */
    public function handle(User $owner, array $attributes, ?UploadedFile $coverImage = null): BadmintonField
    {
        return DB::transaction(function () use ($owner, $attributes, $coverImage): BadmintonField {
            $field = BadmintonField::query()->create([
                'owner_id' => $owner->id,
                'name' => $attributes['name'],
                'slug' => $this->generateSlug($attributes['name']),
                'description' => $attributes['description'] ?? null,
                'address' => $attributes['address'] ?? null,
                'latitude' => $attributes['latitude'] ?? null,
                'longitude' => $attributes['longitude'] ?? null,
                'price_per_hour' => $attributes['price_per_hour'],
                'open_time' => $attributes['open_time'],
                'close_time' => $attributes['close_time'],
                'slot_duration_minutes' => $attributes['slot_duration_minutes'],
                'cover_image' => $coverImage?->store('badminton-fields/covers', 'public'),
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            $field->facilities()->sync($attributes['facility_ids'] ?? []);

            return $field->load(['facilities', 'owner']);
        });
    }

    private function generateSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (BadmintonField::query()->where('slug', $slug)->exists()) {
            $slug = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $slug;
    }
}
