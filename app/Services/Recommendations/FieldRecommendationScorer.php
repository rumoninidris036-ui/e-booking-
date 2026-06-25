<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use App\Models\BadmintonField;
use App\Services\Booking\FieldScheduleService;

class FieldRecommendationScorer
{
    public function __construct(
        private readonly FieldScheduleService $fieldScheduleService,
    ) {}

    /**
     * @param  array{max_recent_bookings:int, min_price:float, max_price:float}  $stats
     * @return array{score: float, reasons: list<string>}
     */
    public function score(BadmintonField $field, FieldRecommendationCriteria $criteria, array $stats): array
    {
        $score = 10.0;
        $reasons = [];

        if ($criteria->hasScheduleWindow()) {
            $isBookable = $this->fieldScheduleService->isBookableSlot(
                field: $field,
                startTime: $criteria->startTime ?? '00:00',
                endTime: $criteria->endTime ?? '00:00',
            );

            if (! $isBookable) {
                return [
                    'score' => 0.0,
                    'reasons' => [],
                ];
            }

            $score += 35.0;
            $reasons[] = 'Slot yang kamu cari tersedia';
        }

        if ($criteria->hasFacilityPreference()) {
            $matchedFacilities = $field->facilities
                ->pluck('slug')
                ->intersect($criteria->facilitySlugs)
                ->values();
            $matchCount = $matchedFacilities->count();
            $requestedCount = count($criteria->facilitySlugs);

            if ($matchCount > 0) {
                $matchRatio = $requestedCount > 0 ? ($matchCount / $requestedCount) : 0;
                $score += 28.0 * $matchRatio;
                $reasons[] = 'Fasilitasnya sesuai preferensi';
            } else {
                $score -= 8.0;
            }
        }

        if ($criteria->budget !== null && $criteria->budget > 0) {
            if ((float) $field->price_per_hour <= $criteria->budget) {
                $priceRatio = (float) $field->price_per_hour / $criteria->budget;
                $score += 18.0 * (1 - min(1, $priceRatio));
                $reasons[] = 'Harga masuk budget';
            } else {
                $overBudgetRatio = ((float) $field->price_per_hour - $criteria->budget) / $criteria->budget;
                $score -= min(12.0, 12.0 * $overBudgetRatio);
            }
        } elseif ($stats['max_price'] > $stats['min_price']) {
            $priceRange = max(1.0, $stats['max_price'] - $stats['min_price']);
            $normalizedPrice = ((float) $field->price_per_hour - $stats['min_price']) / $priceRange;
            $score += 14.0 * (1 - max(0.0, min(1.0, $normalizedPrice)));
        }

        if ($stats['max_recent_bookings'] > 0) {
            $recentBookingScore = ((int) ($field->recent_bookings_count ?? 0) / $stats['max_recent_bookings']) * 12.0;
            if ($recentBookingScore > 0) {
                $score += $recentBookingScore;
                $reasons[] = 'Sering dipilih pemain lain';
            }
        }

        if ($criteria->hasLocation() && $field->latitude !== null && $field->longitude !== null) {
            $distanceKm = $this->distanceKm(
                latitudeA: $criteria->latitude ?? 0.0,
                longitudeA: $criteria->longitude ?? 0.0,
                latitudeB: (float) $field->latitude,
                longitudeB: (float) $field->longitude,
            );

            $distanceScore = max(0.0, 20.0 * (1 - min($distanceKm, 20.0) / 20.0));
            $score += $distanceScore;

            if ($distanceKm <= 5.0) {
                $reasons[] = 'Dekat dari lokasi kamu';
            }
        }

        return [
            'score' => max(0.0, round($score, 2)),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function distanceKm(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $earthRadius = 6371.0;

        $latFrom = deg2rad($latitudeA);
        $lonFrom = deg2rad($longitudeA);
        $latTo = deg2rad($latitudeB);
        $lonTo = deg2rad($longitudeB);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
