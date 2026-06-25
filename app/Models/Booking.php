<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FINISHED = 'finished';

    public const PENDING_PAYMENT_TIMEOUT_MINUTES = 10;

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
        'customer_name',
        'customer_contact',
        'customer_email',
        'guest_access_token',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'expires_at',
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
            'expires_at' => 'datetime',
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

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function isPendingPaymentExpired(?CarbonInterface $now = null): bool
    {
        if ($this->status !== self::STATUS_PENDING || $this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lte($now ?? now());
    }

    /**
     * Keep slots blocked only while payment is still valid.
     */
    public function scopeBlocksSchedule(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();
        $hasExpiresAtColumn = Schema::hasColumn($this->getTable(), 'expires_at');

        return $query->where(function (Builder $query) use ($now, $hasExpiresAtColumn): void {
            $query->whereIn('status', [self::STATUS_PAID, self::STATUS_FINISHED])
                ->orWhere(function (Builder $query) use ($now, $hasExpiresAtColumn): void {
                    $query->where('status', self::STATUS_PENDING)
                        ->when($hasExpiresAtColumn, function (Builder $query) use ($now): void {
                            $query->where(function (Builder $query) use ($now): void {
                                $query->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', $now);
                            });
                        });
                });
        });
    }
}
