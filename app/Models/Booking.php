<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FINISHED = 'finished';

    public const ACTIVE_SLOT_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_FINISHED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'booking_code',
        'badminton_field_id',
        'user_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'paid_at',
        'finished_at',
        'price_per_hour',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'finished_at' => 'datetime',
            'price_per_hour' => 'decimal:2',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
            self::STATUS_FINISHED,
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(BadmintonField::class, 'badminton_field_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
