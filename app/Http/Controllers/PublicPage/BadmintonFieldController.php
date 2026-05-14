<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use Illuminate\Http\JsonResponse;

class BadmintonFieldController extends Controller
{
    public function index(): JsonResponse
    {
        $fields = BadmintonField::query()
            ->with(['facilities', 'owner:id,name'])
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return response()->json($fields);
    }

    public function show(string $slug): JsonResponse
    {
        $field = BadmintonField::query()
            ->with(['facilities', 'owner:id,name'])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $field,
        ]);
    }
}
