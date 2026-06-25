<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

use Illuminate\Support\Arr;

class FieldRecommendationCriteria
{
    /**
     * @param  list<string>  $facilitySlugs
     * @param  list<int>  $excludeFieldIds
     */
    public function __construct(
        public readonly int $limit = 3,
        public readonly ?string $date = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?float $budget = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly array $facilitySlugs = [],
        public readonly array $excludeFieldIds = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, int $defaultLimit = 3): self
    {
        $limit = (int) ($payload['limit'] ?? $defaultLimit);

        return new self(
            limit: max(1, min(12, $limit)),
            date: self::normalizeDate($payload['date'] ?? null),
            startTime: self::normalizeTime($payload['start_time'] ?? null),
            endTime: self::normalizeTime($payload['end_time'] ?? null),
            budget: self::normalizeFloat($payload['budget'] ?? null),
            latitude: self::normalizeFloat($payload['latitude'] ?? null),
            longitude: self::normalizeFloat($payload['longitude'] ?? null),
            facilitySlugs: self::normalizeStringList($payload['facility_slugs'] ?? []),
            excludeFieldIds: self::normalizeIntegerList($payload['exclude_field_ids'] ?? []),
        );
    }

    public function hasScheduleWindow(): bool
    {
        return $this->date !== null && $this->startTime !== null && $this->endTime !== null;
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function hasFacilityPreference(): bool
    {
        return $this->facilitySlugs !== [];
    }

    /**
     * @return list<string>
     */
    private static function normalizeStringList(mixed $value): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            Arr::wrap($value),
        ), static fn (string $item): bool => $item !== ''));
    }

    /**
     * @return list<int>
     */
    private static function normalizeIntegerList(mixed $value): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $item): int => (int) $item,
            Arr::wrap($value),
        ), static fn (int $item): bool => $item > 0));
    }

    private static function normalizeDate(mixed $value): ?string
    {
        $date = trim((string) $value);

        return $date !== '' ? substr($date, 0, 10) : null;
    }

    private static function normalizeTime(mixed $value): ?string
    {
        $time = trim((string) $value);

        return $time !== '' ? substr($time, 0, 5) : null;
    }

    private static function normalizeFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
