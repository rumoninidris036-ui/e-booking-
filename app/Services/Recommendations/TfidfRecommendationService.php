<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\FieldScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TfidfRecommendationService
{
    public function __construct(
        private readonly DocumentBuilderService $documentBuilderService,
        private readonly TFIDFService $tfidfService,
        private readonly CosineSimilarityService $cosineSimilarityService,
        private readonly UserProfileService $userProfileService,
        private readonly FieldScheduleService $fieldScheduleService,
    ) {}

    /**
     * @return Collection<int, array{field: BadmintonField, score: float, reasons: list<string>}>
     */
    public function recommend(FieldRecommendationCriteria $criteria, ?User $user = null): Collection
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

        if ($criteria->hasScheduleWindow()) {
            $fields = $fields->filter(function (BadmintonField $field) use ($criteria): bool {
                return $this->fieldScheduleService->isBookableSlot(
                    $field,
                    $criteria->startTime ?? '00:00',
                    $criteria->endTime ?? '00:00',
                );
            })->values();
        }

        if ($fields->isEmpty()) {
            return collect();
        }

        $fieldDocuments = $this->documentBuilderService->buildFieldDocuments($fields);

        if ($fieldDocuments->isEmpty()) {
            return collect();
        }

        $idf = $this->tfidfService->inverseDocumentFrequency($fieldDocuments->all());
        if ($idf === []) {
            return collect();
        }

        $profile = $this->userProfileService->buildProfile($user, $fieldDocuments, $idf);
        $queryTokens = $this->buildQueryTokens($criteria, $profile['tokens']);
        $userVector = $this->tfidfService->tfIdf($queryTokens, $idf);
        $userVector = $this->tfidfService->alignVector($userVector, $vocabulary = array_keys($idf));
        $queryReasons = $this->buildQueryReasons($criteria, $profile['reasons']);

        if ($userVector === []) {
            return collect();
        }

        $recommendations = $fieldDocuments->map(function (array $document) use ($idf, $profile, $vocabulary): array {
            $fieldVector = $this->tfidfService->tfIdf($document['tokens'], $idf);
            $fieldVector = $this->tfidfService->alignVector($fieldVector, $vocabulary);
            $similarity = $this->cosineSimilarityService->similarity($userVector, $fieldVector);

            return [
                'field' => $document['field'],
                'score' => round($similarity * 100, 4),
                'reasons' => $this->buildReasons($document, $profile, $queryReasons, $userVector, $fieldVector),
            ];
        })
            ->filter(fn (array $recommendation): bool => $recommendation['score'] > 0.0)
            ->sortByDesc('score')
            ->values()
            ->take($criteria->limit);

        if ($recommendations->isEmpty() && $fields->isNotEmpty()) {
            return collect();
        }

        return $recommendations;
    }

    /**
     * @param  array{
     *     field: BadmintonField,
     *     text: string,
     *     tokens: list<string>,
     *     term_counts: array<string, int>,
     *     features: array<string, list<string>>
     * }  $document
     * @param  array{
     *     user: ?User,
     *     tokens: list<string>,
     *     vector: array<string, float>,
     *     source: string,
     *     reasons: list<string>
     * }  $profile
     * @param  array<string, float>  $idf
     * @param  array<string, float>  $userVector
     * @param  array<string, float>  $fieldVector
     * @return list<string>
     */
    private function buildReasons(array $document, array $profile, array $queryReasons, array $userVector, array $fieldVector): array
    {
        $field = $document['field'];
        $sharedTerms = [];

        foreach ($fieldVector as $term => $fieldWeight) {
            $userWeight = (float) ($userVector[$term] ?? 0.0);

            if ($fieldWeight > 0 && $userWeight > 0) {
                $sharedTerms[$term] = $fieldWeight * $userWeight;
            }
        }

        arsort($sharedTerms);
        $topTerms = array_slice(array_keys($sharedTerms), 0, 3);

        $reasons = [];

        if ($topTerms !== []) {
            $reasons[] = 'Cocok dengan preferensi: '.implode(', ', $topTerms);
        }

        if ($profile['source'] === 'history') {
            $reasons[] = 'Disesuaikan dari histori booking dan rating kamu';
        } elseif ($profile['source'] === 'fallback') {
            $reasons[] = 'Diprioritaskan dari lapangan populer';
        }

        $facilityNames = $document['features']['facilities'] ?? [];
        if ($facilityNames !== []) {
            $overlap = array_values(array_intersect($profile['tokens'], $facilityNames));
            if ($overlap !== []) {
                $reasons[] = 'Fasilitas yang sesuai: '.implode(', ', array_slice($overlap, 0, 2));
            }
        }

        if ($field->address !== null && $field->address !== '') {
            $reasons[] = 'Lokasi: '.$field->address;
        }

        $reasons = array_merge($reasons, $queryReasons);

        return array_values(array_unique(array_filter($reasons)));
    }

    /**
     * @return list<string>
     */
    private function buildQueryTokens(FieldRecommendationCriteria $criteria, array $profileTokens): array
    {
        $tokens = $profileTokens;

        foreach ($criteria->facilitySlugs as $facilitySlug) {
            $tokens = array_merge($tokens, $this->documentBuilderService->tokenize($facilitySlug));
        }

        if ($criteria->budget !== null && $criteria->budget > 0) {
            $tokens = array_merge($tokens, $this->documentBuilderService->priceTokens($criteria->budget));
        }

        if ($criteria->hasLocation()) {
            $tokens[] = 'geo_lat_'.str_replace('.', '_', number_format((float) $criteria->latitude, 2, '.', ''));
            $tokens[] = 'geo_lng_'.str_replace('.', '_', number_format((float) $criteria->longitude, 2, '.', ''));
        }

        if ($criteria->hasScheduleWindow()) {
            $start = CarbonImmutable::createFromFormat('H:i', $criteria->startTime);
            $end = CarbonImmutable::createFromFormat('H:i', $criteria->endTime);

            if ($start !== false && $end !== false) {
                $tokens[] = 'slot_'.$start->diffInMinutes($end).'_min';
            }
        }

        return array_values(array_filter(array_unique($tokens)));
    }

    /**
     * @return list<string>
     */
    private function buildQueryReasons(FieldRecommendationCriteria $criteria, array $profileReasons): array
    {
        $reasons = $profileReasons;

        if ($criteria->hasFacilityPreference()) {
            $reasons[] = 'Fasilitas dipertimbangkan dari permintaan pencarian';
        }

        if ($criteria->budget !== null && $criteria->budget > 0) {
            $reasons[] = 'Budget dipertimbangkan';
        }

        if ($criteria->hasLocation()) {
            $reasons[] = 'Lokasi dipertimbangkan';
        }

        if ($criteria->hasScheduleWindow()) {
            $reasons[] = 'Slot waktu tersedia';
        }

        return array_values(array_unique(array_filter($reasons)));
    }
}
