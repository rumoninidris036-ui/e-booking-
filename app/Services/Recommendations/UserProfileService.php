<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Collection;

class UserProfileService
{
    public function __construct(
        private readonly DocumentBuilderService $documentBuilderService,
    ) {}

    /**
     * @param  Collection<int, array{field: BadmintonField, tokens:list<string>, term_counts: array<string, int>, features: array<string, list<string>>, text: string}>  $fieldDocuments
     * @param  array<string, float>  $idf
     * @return array{
     *     user: ?User,
     *     tokens: list<string>,
     *     vector: array<string, float>,
     *     source: string,
     *     reasons: list<string>
     * }
     */
    public function buildProfile(?User $user, Collection $fieldDocuments, array $idf): array
    {
        $vocabulary = array_keys($idf);
        sort($vocabulary);

        if ($user !== null) {
            $history = $this->buildHistoryTokens($user);

            if ($history['tokens'] !== []) {
                return $this->vectorizeProfile($user, $history['tokens'], $idf, 'history', $history['reasons']);
            }
        }

        return $this->buildFallbackProfile($fieldDocuments, $idf);
    }

    /**
     * @return array{tokens:list<string>, reasons:list<string>}
     */
    private function buildHistoryTokens(User $user): array
    {
        $bookingTokens = [];
        $reasons = [];

        $bookings = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_FINISHED])
            ->with(['field.facilities'])
            ->latest('booking_date')
            ->latest('start_time')
            ->limit(25)
            ->get();

        foreach ($bookings as $booking) {
            $field = $booking->field;

            if ($field === null) {
                continue;
            }

            $document = $this->documentBuilderService->buildFieldDocument($field);
            $repeat = max(1, (int) $bookings->where('badminton_field_id', $field->id)->count());

            for ($i = 0; $i < $repeat; $i++) {
                $bookingTokens = array_merge($bookingTokens, $document['tokens']);
            }

            $reasons[] = 'Riwayat booking: '.$field->name;
        }

        $ratings = Rating::query()
            ->whereHas('booking', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->with(['booking.field.facilities'])
            ->latest('id')
            ->limit(25)
            ->get();

        foreach ($ratings as $rating) {
            $field = $rating->booking?->field;
            if ($field === null) {
                continue;
            }

            $document = $this->documentBuilderService->buildFieldDocument($field);
            $weight = max(1, (int) $rating->score);

            for ($i = 0; $i < $weight; $i++) {
                $bookingTokens = array_merge($bookingTokens, $document['tokens']);
            }

            $commentTokens = $this->documentBuilderService->tokenize($rating->comment);
            if ($commentTokens !== []) {
                for ($i = 0; $i < max(1, (int) round($rating->score / 2)); $i++) {
                    $bookingTokens = array_merge($bookingTokens, $commentTokens);
                }
            }

            $reasons[] = 'Rating untuk '.$field->name;
        }

        return [
            'tokens' => array_values(array_filter($bookingTokens)),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    /**
     * @param  Collection<int, array{field: BadmintonField, tokens:list<string>, term_counts: array<string, int>, features: array<string, list<string>>, text: string}>  $fieldDocuments
     * @param  array<string, float>  $idf
     * @return array{
     *     user: ?User,
     *     tokens: list<string>,
     *     vector: array<string, float>,
     *     source: string,
     *     reasons: list<string>
     * }
     */
    private function buildFallbackProfile(Collection $fieldDocuments, array $idf): array
    {
        $tokens = [];
        $reasons = [];

        foreach ($fieldDocuments as $document) {
            $field = $document['field'];
            $weight = 1.0;

            $weight += (float) ($field->recent_bookings_count ?? 0);
            $weight += ((float) ($field->ratings_avg_score ?? 0)) / 5.0;

            $repeat = max(1, (int) round($weight));
            for ($i = 0; $i < $repeat; $i++) {
                $tokens = array_merge($tokens, $document['tokens']);
            }

            $reasons[] = 'Lapangan populer: '.$field->name;
        }

        return $this->vectorizeProfile(null, $tokens, $idf, 'fallback', array_values(array_unique($reasons)));
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, float>  $idf
     * @param  list<string>  $reasons
     * @return array{
     *     user: ?User,
     *     tokens: list<string>,
     *     vector: array<string, float>,
     *     source: string,
     *     reasons: list<string>
     * }
     */
    private function vectorizeProfile(?User $user, array $tokens, array $idf, string $source, array $reasons): array
    {
        $vector = [];
        $tf = [];
        $totalTerms = count($tokens);

        if ($totalTerms > 0) {
            $counts = array_count_values($tokens);

            foreach ($idf as $term => $idfValue) {
                $termFrequency = (($counts[$term] ?? 0) / $totalTerms);
                $tf[$term] = $termFrequency;
                $vector[$term] = $termFrequency * $idfValue;
            }
        }

        ksort($vector);

        return [
            'user' => $user,
            'tokens' => array_values(array_filter($tokens)),
            'vector' => $vector,
            'source' => $source,
            'reasons' => $reasons,
        ];
    }
}
