<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use App\Models\User;
use Illuminate\Support\Collection;
use Throwable;

class RecommendationService
{
    public function __construct(
        private readonly TfidfRecommendationService $tfidfRecommendationService,
        private readonly RuleBasedRecommendationService $ruleBasedRecommendationService,
    ) {}

    /**
     * @return Collection<int, array{field: \App\Models\BadmintonField, score: float, reasons: list<string>}>
     */
    public function recommend(FieldRecommendationCriteria $criteria): Collection
    {
        $algorithm = strtolower((string) config('services.recommendations.algorithm', 'tfidf'));
        $user = auth()->user();

        if ($algorithm === 'legacy' || $algorithm === 'rule-based' || $algorithm === 'rule_based') {
            return $this->ruleBasedRecommendationService->recommend($criteria);
        }

        try {
            $recommendations = $this->tfidfRecommendationService->recommend($criteria, $user instanceof User ? $user : null);

            if ($recommendations->isNotEmpty()) {
                return $recommendations;
            }
        } catch (Throwable) {
            // Fall back to legacy scoring if the TF-IDF pipeline cannot run.
        }

        return $this->ruleBasedRecommendationService->recommend($criteria);
    }
}
