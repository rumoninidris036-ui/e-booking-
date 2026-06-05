<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'latest',
        ];

        $owners = $this->ownersQuery()
            ->withCount('ownedFields')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['sort'] === 'name', fn ($query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'fields', fn ($query) => $query->orderByDesc('owned_fields_count'))
            ->when($filters['sort'] === 'latest', fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        $ownerIds = $owners->getCollection()->pluck('id');
        $metrics = DB::table('users')
            ->leftJoin('badminton_fields', 'badminton_fields.owner_id', '=', 'users.id')
            ->leftJoin('bookings', 'bookings.badminton_field_id', '=', 'badminton_fields.id')
            ->leftJoin('payments', function ($join): void {
                $join->on('payments.booking_id', '=', 'bookings.id')
                    ->where('payments.status', Payment::STATUS_SUCCESS);
            })
            ->whereIn('users.id', $ownerIds)
            ->groupBy('users.id')
            ->select([
                'users.id',
                DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                DB::raw('COALESCE(SUM(payments.amount), 0) as total_revenue'),
            ])
            ->get()
            ->keyBy('id');

        return view('admin.users.index', [
            'admin' => $request->user(),
            'owners' => $owners,
            'metrics' => $metrics,
            'filters' => $filters,
            'summary' => [
                'total_owners' => $this->ownersQuery()->count(),
                'registered_today' => $this->ownersQuery()->whereDate('created_at', now()->toDateString())->count(),
                'with_fields' => $this->ownersQuery()->has('ownedFields')->count(),
            ],
        ]);
    }

    private function ownersQuery(): Builder
    {
        return User::query()->whereHas('roles', function ($query): void {
            $query
                ->where('name', 'owner')
                ->where('guard_name', 'web');
        });
    }
}
