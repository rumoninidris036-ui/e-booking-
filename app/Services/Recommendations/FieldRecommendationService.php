<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use App\Models\BadmintonField;
use App\Models\Booking;
use Illuminate\Support\Collection;

class FieldRecommendationService
{
    public function __construct(
        private readonly FieldRecommendationScorer $scorer,
    ) {}

    /**
     * @return Collection<int, array{field: BadmintonField, score: float, reasons: list<string>}>
     */
    public function recommend(FieldRecommendationCriteria $criteria): Collection
    {
        $fields = BadmintonField::query()
            ->with(['facilities', 'owner:id,name'])
            ->withAvg('ratings', 'score')
            ->withCount([
                'bookings as recent_bookings_count' => function ($query): void {
                    $query->where('booking_date', '>=', now()->subDays(30)->toDateString())
                        ->whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_FINISHED]);
                },
            ])
            ->where('is_active', true)
            ->when($criteria->excludeFieldIds !== [], function ($query) use ($criteria): void {
                $query->whereNotIn('id', $criteria->excludeFieldIds);
            })
            ->get();

        if ($fields->isEmpty()) {
            return collect();
        }

        $stats = [
            'max_recent_bookings' => (int) ($fields->max('recent_bookings_count') ?? 0),
            'min_price' => (float) ($fields->min('price_per_hour') ?? 0),
            'max_price' => (float) ($fields->max('price_per_hour') ?? 0),
        ];

        return $fields
            ->map(function (BadmintonField $field) use ($criteria, $stats): ?array {
                $scored = $this->scorer->score($field, $criteria, $stats);

                if ($scored['score'] <= 0.0) {
                    return null;
                }

                return [
                    'field' => $field,
                    'score' => $scored['score'],
                    'reasons' => $scored['reasons'],
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values()
            ->take($criteria->limit);
    }
}
