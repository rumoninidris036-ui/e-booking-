<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BadmintonFieldController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $fields = BadmintonField::query()
            ->with(['facilities', 'owner:id,name'])
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
            ->with(['facilities', 'owner:id,name'])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $request->expectsJson()) {
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
}
