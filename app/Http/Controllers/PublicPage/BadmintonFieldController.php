<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Services\Recommendations\FieldRecommendationCriteria;
use App\Services\Recommendations\FieldRecommendationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BadmintonFieldController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $filters = $this->filtersFromRequest($request);

        $fields = BadmintonField::query()
            ->with(['facilities', 'owner:id,name', 'galleryImages'])
            ->where('is_active', true)
            ->when($filters['location'] !== '', function (Builder $query) use ($filters): void {
                $search = $this->likePattern($filters['location']);

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhereHas('owner', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', $search);
                        });
                });
            })
            ->when(
                $filters['date'] !== null && $filters['timeWindow'] !== null,
                function (Builder $query) use ($filters): void {
                    $query->whereDoesntHave('bookings', function (Builder $query) use ($filters): void {
                        $query->whereDate('booking_date', $filters['date'])
                            ->blocksSchedule()
                            ->where('start_time', '<', $filters['timeWindow']['end'])
                            ->where('end_time', '>', $filters['timeWindow']['start']);
                    });
                },
            )
            ->when(
                $filters['timeWindow'] !== null,
                function (Builder $query) use ($filters): void {
                    $query->where('open_time', '<=', $filters['timeWindow']['start'])
                        ->where('close_time', '>=', $filters['timeWindow']['end']);
                },
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        if (! $request->expectsJson()) {
            return view('public.fields.index', [
                'fields' => $fields,
                'filters' => $filters,
                'mapMeta' => [
                    'provider' => 'OpenStreetMap',
                    'library' => 'Leaflet.js',
                    'markers' => collect($fields->items())
                        ->map(fn (BadmintonField $field): ?array => $field->map_marker)
                        ->filter()
                        ->values(),
                ],
            ]);
        }

        return response()->json([
            'data' => $fields->items(),
            'meta' => [
                'current_page' => $fields->currentPage(),
                'last_page' => $fields->lastPage(),
                'per_page' => $fields->perPage(),
                'total' => $fields->total(),
                'map' => [
                    'provider' => 'OpenStreetMap',
                    'library' => 'Leaflet.js',
                    'markers' => collect($fields->items())
                        ->map(fn (BadmintonField $field): ?array => $field->map_marker)
                        ->filter()
                        ->values(),
                ],
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse|View
    {
        $field = BadmintonField::query()
            ->with(['facilities', 'owner:id,name', 'galleryImages'])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $request->expectsJson()) {
            $field->loadAvg('ratings', 'score');
            $field->load([
                'galleryImages' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'ratings' => fn ($query) => $query
                    ->latest()
                    ->with(['booking:id,booking_code,customer_name,user_id', 'booking.user:id,name']),
            ]);

            return view('public.fields.show', [
                'field' => $field,
                'mapMeta' => [
                    'provider' => 'OpenStreetMap',
                    'library' => 'Leaflet.js',
                    'marker' => $field->map_marker,
                ],
            ]);
        }

        return response()->json([
            'data' => $field,
            'meta' => [
                'map' => [
                    'provider' => 'OpenStreetMap',
                    'library' => 'Leaflet.js',
                    'marker' => $field->map_marker,
                ],
            ],
        ]);
    }

    public function markers(): JsonResponse
    {
        $markers = BadmintonField::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (BadmintonField $field): ?array => $field->map_marker)
            ->filter()
            ->values();

        return response()->json([
            'data' => $markers,
            'meta' => [
                'provider' => 'OpenStreetMap',
                'library' => 'Leaflet.js',
            ],
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $filters = $this->filtersFromRequest($request);
        $query = trim($request->string('q')->toString());
        $queryLower = Str::lower($query);

        $fields = BadmintonField::query()
            ->where('is_active', true)
            ->when(
                $filters['date'] !== null && $filters['timeWindow'] !== null,
                function (Builder $query) use ($filters): void {
                    $query->whereDoesntHave('bookings', function (Builder $query) use ($filters): void {
                        $query->whereDate('booking_date', $filters['date'])
                            ->blocksSchedule()
                            ->where('start_time', '<', $filters['timeWindow']['end'])
                            ->where('end_time', '>', $filters['timeWindow']['start']);
                    });
                },
            )
            ->when(
                $filters['timeWindow'] !== null,
                function (Builder $query) use ($filters): void {
                    $query->where('open_time', '<=', $filters['timeWindow']['start'])
                        ->where('close_time', '>=', $filters['timeWindow']['end']);
                },
            )
            ->get(['address']);

        $cities = $fields
            ->map(fn (BadmintonField $field): ?string => $this->cityFromAddress($field->address))
            ->filter()
            ->unique(fn (string $city): string => Str::lower($city))
            ->filter(function (string $city) use ($queryLower): bool {
                return $queryLower === '' || Str::contains(Str::lower($city), $queryLower);
            })
            ->sortBy(fn (string $city): string => Str::lower($city))
            ->take(8)
            ->values()
            ->map(fn (string $city): array => [
                'value' => $city,
                'label' => $city,
            ]);

        return response()->json([
            'data' => $cities,
            'meta' => [
                'query' => $query,
                'count' => $cities->count(),
            ],
        ]);
    }

    public function recommendations(
        Request $request,
        FieldRecommendationService $recommendationService,
    ): JsonResponse {
        $criteria = FieldRecommendationCriteria::fromArray([
            'limit' => $request->integer('limit', 5),
            'date' => $request->string('date')->toString(),
            'start_time' => $request->string('start_time')->toString(),
            'end_time' => $request->string('end_time')->toString(),
            'budget' => $request->input('budget'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'facility_slugs' => $request->input('facility_slugs', []),
            'exclude_field_ids' => $request->input('exclude_field_ids', []),
        ], 5);

        $recommendations = $recommendationService->recommend($criteria);

        return response()->json([
            'data' => $recommendations->map(static function (array $recommendation): array {
                /** @var BadmintonField $field */
                $field = $recommendation['field'];

                return [
                    'score' => $recommendation['score'],
                    'reasons' => $recommendation['reasons'],
                    'field' => [
                        'id' => $field->id,
                        'name' => $field->name,
                        'slug' => $field->slug,
                        'address' => $field->address,
                        'price_per_hour' => (float) $field->price_per_hour,
                        'cover_image_url' => $field->cover_image_url,
                        'facilities_count' => $field->facilities->count(),
                        'booking_url' => route('public.fields.booking', ['slug' => $field->slug]),
                        'show_url' => route('public.fields.show', ['slug' => $field->slug]),
                    ],
                ];
            })->values(),
            'meta' => [
                'limit' => $criteria->limit,
                'filters' => [
                    'date' => $criteria->date,
                    'start_time' => $criteria->startTime,
                    'end_time' => $criteria->endTime,
                    'budget' => $criteria->budget,
                    'latitude' => $criteria->latitude,
                    'longitude' => $criteria->longitude,
                    'facility_slugs' => $criteria->facilitySlugs,
                    'exclude_field_ids' => $criteria->excludeFieldIds,
                ],
            ],
        ]);
    }

    /**
     * @return array{location: string, date: ?string, time: string, timeWindow: ?array{label: string, start: string, end: string}}
     */
    private function filtersFromRequest(Request $request): array
    {
        $location = trim($request->string('location')->toString());
        $date = trim($request->string('date')->toString());
        $time = trim($request->string('time')->toString() ?: 'any');

        $normalizedDate = null;

        if ($date !== '') {
            try {
                $parsedDate = CarbonImmutable::createFromFormat('Y-m-d', $date);
                $normalizedDate = $parsedDate instanceof CarbonImmutable ? $parsedDate->toDateString() : null;
            } catch (\Throwable) {
                $normalizedDate = null;
            }
        }

        return [
            'location' => $location,
            'date' => $normalizedDate,
            'time' => $time,
            'timeWindow' => $this->timeWindowFor($time),
        ];
    }

    /**
     * @return array{label: string, start: string, end: string}|null
     */
    private function timeWindowFor(string $time): ?array
    {
        return match ($time) {
            'morning' => ['label' => 'Morning', 'start' => '06:00:00', 'end' => '12:00:00'],
            'afternoon' => ['label' => 'Afternoon', 'start' => '12:00:00', 'end' => '17:00:00'],
            'evening' => ['label' => 'Evening', 'start' => '17:00:00', 'end' => '23:00:00'],
            default => null,
        };
    }

    private function likePattern(string $value): string
    {
        return '%'.Str::of($value)->replace(['\\', '%', '_'], ['\\\\', '\%', '\_'])->toString().'%';
    }

    private function cityFromAddress(?string $address): ?string
    {
        $segments = collect(explode(',', (string) $address))
            ->map(fn (string $segment): string => trim($segment))
            ->filter()
            ->values();

        if ($segments->isEmpty()) {
            return null;
        }

        return $segments->count() >= 3
            ? $segments->slice(-2, 1)->first()
            : $segments->last();
    }
}
