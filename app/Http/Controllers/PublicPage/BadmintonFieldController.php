<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use App\Services\Recommendations\FieldRecommendationCriteria;
use App\Services\Recommendations\FieldRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BadmintonFieldController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $fields = BadmintonField::query()
            ->with(['facilities', 'owner:id,name', 'galleryImages'])
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        if (! $request->expectsJson()) {
            return view('public.fields.index', [
                'fields' => $fields,
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
}
