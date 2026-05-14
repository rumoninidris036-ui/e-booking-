<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'price_per_hour',
        'cover_image',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'cover_image_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
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

    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image === null) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image);
    }
}
