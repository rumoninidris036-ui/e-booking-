<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse|View
    {
        $dashboard = $this->dashboardData();

        if ($request->expectsJson()) {
            return response()->json(['data' => $dashboard]);
        }

        return view('admin.dashboard', [
            'admin' => $request->user(),
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardData(): array
    {
        return [
            'summary' => [
                'total_users' => User::query()->count(),
                'total_admins' => $this->usersWithRole('admin')->count(),
                'total_owners' => $this->usersWithRole('owner')->count(),
                'total_customers' => $this->usersWithRole('customer')->count(),
                'total_transactions' => Payment::query()->count(),
                'successful_transactions' => Payment::query()->where('status', Payment::STATUS_SUCCESS)->count(),
                'total_transaction_amount' => (float) Payment::query()
                    ->where('status', Payment::STATUS_SUCCESS)
                    ->sum('amount'),
            ],
            'field_monitoring' => [
                'total_fields' => BadmintonField::query()->count(),
                'active_fields' => BadmintonField::query()->where('is_active', true)->count(),
                'inactive_fields' => BadmintonField::query()->where('is_active', false)->count(),
                'recent_fields' => BadmintonField::query()
                    ->with('owner:id,name,email')
                    ->latest()
                    ->limit(6)
                    ->get(['id', 'owner_id', 'name', 'slug', 'is_active', 'created_at']),
            ],
            'booking_monitoring' => [
                'total_bookings' => Booking::query()->count(),
                'pending_bookings' => Booking::query()->where('status', Booking::STATUS_PENDING)->count(),
                'paid_bookings' => Booking::query()->where('status', Booking::STATUS_PAID)->count(),
                'finished_bookings' => Booking::query()->where('status', Booking::STATUS_FINISHED)->count(),
                'cancelled_bookings' => Booking::query()->where('status', Booking::STATUS_CANCELLED)->count(),
                'recent_bookings' => Booking::query()
                    ->with(['field:id,name,slug', 'user:id,name,email'])
                    ->latest('booking_date')
                    ->latest('start_time')
                    ->limit(6)
                    ->get(['id', 'booking_code', 'badminton_field_id', 'user_id', 'booking_date', 'start_time', 'end_time', 'status', 'price_per_hour']),
            ],
            'owner_monitoring' => [
                'recent_owners' => $this->usersWithRole('owner')
                    ->withCount('ownedFields')
                    ->latest()
                    ->limit(6)
                    ->get(['id', 'name', 'email', 'created_at']),
                'top_owners' => DB::table('users')
                    ->join('model_has_roles', function ($join): void {
                        $join->on('model_has_roles.model_id', '=', 'users.id')
                            ->where('model_has_roles.model_type', User::class);
                    })
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->leftJoin('badminton_fields', 'badminton_fields.owner_id', '=', 'users.id')
                    ->leftJoin('bookings', 'bookings.badminton_field_id', '=', 'badminton_fields.id')
                    ->leftJoin('payments', function ($join): void {
                        $join->on('payments.booking_id', '=', 'bookings.id')
                            ->where('payments.status', Payment::STATUS_SUCCESS);
                    })
                    ->where('roles.name', 'owner')
                    ->groupBy('users.id', 'users.name', 'users.email')
                    ->select([
                        'users.id',
                        'users.name',
                        'users.email',
                        DB::raw('COUNT(DISTINCT badminton_fields.id) as total_fields'),
                        DB::raw('COUNT(DISTINCT bookings.id) as total_bookings'),
                        DB::raw('COALESCE(SUM(payments.amount), 0) as total_revenue'),
                    ])
                    ->orderByDesc('total_revenue')
                    ->orderByDesc('total_bookings')
                    ->limit(5)
                    ->get(),
            ],
        ];
    }

    private function usersWithRole(string $role): Builder
    {
        return User::query()->whereHas('roles', function ($query) use ($role): void {
            $query
                ->where('name', $role)
                ->where('guard_name', 'web');
        });
    }
}
