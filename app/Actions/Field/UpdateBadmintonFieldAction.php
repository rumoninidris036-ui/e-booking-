<?php

declare(strict_types=1);

namespace App\Actions\Field;

use App\Models\BadmintonField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateBadmintonFieldAction
{
    /**
     * @param array{name: string, description?: string|null, address?: string|null, price_per_hour: numeric-string|int|float, is_active?: bool, facility_ids?: array<int, int>, remove_cover_image?: bool} $attributes
     */
    public function handle(BadmintonField $badmintonField, array $attributes, ?UploadedFile $coverImage = null): BadmintonField
    {
        return DB::transaction(function () use ($badmintonField, $attributes, $coverImage): BadmintonField {
            $oldCoverImage = $badmintonField->cover_image;
            $newCoverImage = $oldCoverImage;

            if (($attributes['remove_cover_image'] ?? false) === true) {
                $newCoverImage = null;
            }

            if ($coverImage !== null) {
                $newCoverImage = $coverImage->store('badminton-fields/covers', 'public');
            }

            $badmintonField->update([
                'name' => $attributes['name'],
                'slug' => $this->shouldRegenerateSlug($badmintonField, $attributes['name'])
                    ? $this->generateSlug($attributes['name'], $badmintonField->id)
                    : $badmintonField->slug,
                'description' => $attributes['description'] ?? null,
                'address' => $attributes['address'] ?? null,
                'price_per_hour' => $attributes['price_per_hour'],
                'cover_image' => $newCoverImage,
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            $badmintonField->facilities()->sync($attributes['facility_ids'] ?? []);

            if ($oldCoverImage !== null && $oldCoverImage !== $newCoverImage) {
                Storage::disk('public')->delete($oldCoverImage);
            }

            return $badmintonField->load(['facilities', 'owner']);
        });
    }

    private function shouldRegenerateSlug(BadmintonField $badmintonField, string $name): bool
    {
        return $badmintonField->name !== $name;
    }

    private function generateSlug(string $name, int $ignoreId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (BadmintonField::query()->where('slug', $slug)->whereKeyNot($ignoreId)->exists()) {
            $slug = sprintf('%s-%d', $baseSlug, $counter);
            $counter++;
        }

        return $slug;
    }
}
