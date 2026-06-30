<?php

declare(strict_types=1);

namespace App\Actions\Field;

use App\Models\BadmintonField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteBadmintonFieldAction
{
    public function handle(BadmintonField $badmintonField): void
    {
        DB::transaction(function () use ($badmintonField): void {
            $coverImage = $badmintonField->cover_image;
            $galleryImages = $badmintonField->galleryImages()->get(['id', 'path']);

            $badmintonField->facilities()->detach();
            $badmintonField->delete();

            if ($coverImage !== null) {
                Storage::disk('public')->delete($coverImage);
            }

            foreach ($galleryImages as $galleryImage) {
                Storage::disk('public')->delete($galleryImage->path);
            }
        });
    }
}
