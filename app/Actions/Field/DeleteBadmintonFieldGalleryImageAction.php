<?php

declare(strict_types=1);

namespace App\Actions\Field;

use App\Models\BadmintonFieldGalleryImage;
use Illuminate\Support\Facades\Storage;

class DeleteBadmintonFieldGalleryImageAction
{
    public function handle(BadmintonFieldGalleryImage $galleryImage): void
    {
        $path = $galleryImage->path;

        $galleryImage->delete();

        Storage::disk('public')->delete($path);
    }
}
