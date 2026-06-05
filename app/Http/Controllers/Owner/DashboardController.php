<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OwnerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OwnerDashboardService $dashboardService,
    ) {}

    public function __invoke(Request $request): JsonResponse|View
    {
        $data = $this->dashboardService->dataFor($request->user(), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $data,
            ]);
        }

        return view('owner.dashboard', [
            'dashboard' => $data,
            'owner' => $request->user(),
        ]);
    }
}
