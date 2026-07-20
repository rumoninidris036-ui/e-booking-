<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BadmintonField extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'price_per_hour',
        'open_time',
        'close_time',
        'slot_duration_minutes',
        'cover_image',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'cover_image_url',
        'gallery_image_urls',
        'map_marker',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'slot_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(BadmintonFieldGalleryImage::class, 'badminton_field_id')->orderBy('sort_order')->orderBy('id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'badminton_field_id');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image === null) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image);
    }

    /**
     * @return list<string>
     */
    public function getGalleryImageUrlsAttribute(): array
    {
        return $this->relationLoaded('galleryImages')
            ? $this->galleryImages->map(fn(BadmintonFieldGalleryImage $image): string => $image->url)->all()
            : $this->galleryImages()->get()->map(fn(BadmintonFieldGalleryImage $image): string => $image->url)->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMapMarkerAttribute(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'price_per_hour' => (float) $this->price_per_hour,
            'cover_image_url' => $this->cover_image_url,
        ];
    }
}
