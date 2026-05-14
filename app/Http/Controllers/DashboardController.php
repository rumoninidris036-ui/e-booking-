<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $roleLabel = $request->user()?->getRoleNames()->first() ?? 'customer';

        return view('dashboard', [
            'roleLabel' => $roleLabel,
        ]);
    }
}
