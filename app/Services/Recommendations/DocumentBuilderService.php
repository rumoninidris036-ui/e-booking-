<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use App\Models\BadmintonField;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DocumentBuilderService
{
    /**
     * @return array{
     *     field: BadmintonField,
     *     text: string,
     *     tokens: list<string>,
     *     term_counts: array<string, int>,
     *     features: array<string, list<string>>
     * }
     */
    public function buildFieldDocument(BadmintonField $field): array
    {
        $features = [
            'name' => $this->tokenize($field->name),
            'description' => $this->tokenize($field->description),
            'address' => $this->tokenize($field->address),
            'price' => $this->priceTokens((float) $field->price_per_hour),
            'schedule' => $this->scheduleTokens($field),
            'geo' => $this->geoTokens($field),
            'facilities' => $this->facilityTokens($field->relationLoaded('facilities') ? $field->facilities : collect()),
        ];

        $tokens = array_values(array_filter(array_merge(
            $features['name'],
            $features['description'],
            $features['address'],
            $features['price'],
            $features['schedule'],
            $features['geo'],
            $features['facilities'],
        )));

        return [
            'field' => $field,
            'text' => implode(' ', $tokens),
            'tokens' => $tokens,
            'term_counts' => array_count_values($tokens),
            'features' => $features,
        ];
    }

    /**
     * @param  Collection<int, BadmintonField>  $fields
     * @return Collection<int, array{
     *     field: BadmintonField,
     *     text: string,
     *     tokens: list<string>,
     *     term_counts: array<string, int>,
     *     features: array<string, list<string>>
     * }>
     */
    public function buildFieldDocuments(Collection $fields): Collection
    {
        return $fields->map(fn (BadmintonField $field): array => $this->buildFieldDocument($field));
    }

    /**
     * @return list<string>
     */
    public function tokenize(?string $text): array
    {
        $normalized = Str::of((string) $text)
            ->lower()
            ->trim()
            ->replaceMatches('/[^\p{L}\p{N}]+/u', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($tokens ?? [], static fn (string $token): bool => trim($token) !== ''));
    }

    /**
     * @return list<string>
     */
    public function priceTokens(float $pricePerHour): array
    {
        if ($pricePerHour <= 0) {
            return ['price_unknown'];
        }

        $bucket = match (true) {
            $pricePerHour < 75000 => 'price_budget',
            $pricePerHour < 125000 => 'price_mid',
            $pricePerHour < 200000 => 'price_premium',
            default => 'price_elite',
        };

        return [
            $bucket,
            sprintf('price_%s', number_format($pricePerHour, 0, '', '')),
        ];
    }

    /**
     * @return list<string>
     */
    private function scheduleTokens(BadmintonField $field): array
    {
        $tokens = [];

        if ($field->open_time !== null) {
            $tokens[] = 'open_'.$this->timeToken((string) $field->open_time);
        }

        if ($field->close_time !== null) {
            $tokens[] = 'close_'.$this->timeToken((string) $field->close_time);
        }

        if ($field->slot_duration_minutes !== null) {
            $tokens[] = 'slot_'.$field->slot_duration_minutes.'_min';
        }

        return $tokens;
    }

    /**
     * @param  Collection<int, mixed>  $facilities
     * @return list<string>
     */
    private function facilityTokens(Collection $facilities): array
    {
        if ($facilities->isEmpty()) {
            return [];
        }

        $tokens = [];

        foreach ($facilities as $facility) {
            $tokens = array_merge(
                $tokens,
                $this->tokenize((string) ($facility->name ?? '')),
                $this->tokenize((string) ($facility->description ?? '')),
                $this->tokenize((string) ($facility->slug ?? '')),
            );
        }

        return array_values(array_filter($tokens));
    }

    private function timeToken(string $time): string
    {
        return str_replace(':', '_', substr($time, 0, 5));
    }

    /**
     * @return list<string>
     */
    private function geoTokens(BadmintonField $field): array
    {
        if ($field->latitude === null || $field->longitude === null) {
            return [];
        }

        return [
            'geo_lat_'.str_replace('.', '_', number_format((float) $field->latitude, 2, '.', '')),
            'geo_lng_'.str_replace('.', '_', number_format((float) $field->longitude, 2, '.', '')),
        ];
    }
}
