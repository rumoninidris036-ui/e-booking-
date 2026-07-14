<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BadmintonFieldGalleryImage extends Model
{
    use HasFactory;

    /**
     * @mixin \Eloquent
     * @property-read string $url
     */
    /**
     * @var list<string>
     */
    protected $fillable = [
        'badminton_field_id',
        'path',
        'sort_order',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(BadmintonField::class, 'badminton_field_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
