<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use Illuminate\Support\Collection;

class FieldRecommendationService
{
    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {}

    /**
     * @return Collection<int, array{field: \App\Models\BadmintonField, score: float, reasons: list<string>}>
     */
    public function recommend(FieldRecommendationCriteria $criteria): Collection
    {
        return $this->recommendationService->recommend($criteria);
    }
}
