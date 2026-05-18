<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Field\CreateBadmintonFieldAction;
use App\Actions\Field\DeleteBadmintonFieldAction;
use App\Actions\Field\UpdateBadmintonFieldAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBadmintonFieldRequest;
use App\Http\Requests\Owner\UpdateBadmintonFieldRequest;
use App\Models\BadmintonField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadmintonFieldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fields = $request->user()
            ->ownedFields()
            ->with('facilities')
            ->latest()
            ->paginate(10);

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

    public function store(
        StoreBadmintonFieldRequest $request,
        CreateBadmintonFieldAction $createBadmintonFieldAction,
    ): JsonResponse {
        $field = $createBadmintonFieldAction->handle(
            owner: $request->user(),
            attributes: $request->validated(),
            coverImage: $request->file('cover_image'),
        );

        return response()->json([
            'message' => 'Badminton field created successfully.',
            'data' => $field,
        ], 201);
    }

    public function show(BadmintonField $badmintonField): JsonResponse
    {
        $this->authorize('view', $badmintonField);

        return response()->json([
            'data' => $badmintonField->load(['facilities', 'owner']),
            'meta' => [
                'map' => [
                    'provider' => 'OpenStreetMap',
                    'library' => 'Leaflet.js',
                    'marker' => $badmintonField->map_marker,
                ],
            ],
        ]);
    }

    public function update(
        UpdateBadmintonFieldRequest $request,
        BadmintonField $badmintonField,
        UpdateBadmintonFieldAction $updateBadmintonFieldAction,
    ): JsonResponse {
        $this->authorize('update', $badmintonField);

        $field = $updateBadmintonFieldAction->handle(
            badmintonField: $badmintonField,
            attributes: $request->validated(),
            coverImage: $request->file('cover_image'),
        );

        return response()->json([
            'message' => 'Badminton field updated successfully.',
            'data' => $field,
        ]);
    }

    public function destroy(
        BadmintonField $badmintonField,
        DeleteBadmintonFieldAction $deleteBadmintonFieldAction,
    ): JsonResponse {
        $this->authorize('delete', $badmintonField);

        $deleteBadmintonFieldAction->handle($badmintonField);

        return response()->json([
            'message' => 'Badminton field deleted successfully.',
        ]);
    }
}
