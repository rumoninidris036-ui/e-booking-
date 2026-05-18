<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'summary' => [
                    'total_users' => User::query()->count(),
                    'total_admins' => User::role('admin')->count(),
                    'total_owners' => User::role('owner')->count(),
                    'total_customers' => User::role('customer')->count(),
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
                        ->limit(5)
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
                        ->limit(5)
                        ->get(['id', 'booking_code', 'badminton_field_id', 'user_id', 'booking_date', 'start_time', 'end_time', 'status', 'price_per_hour']),
                ],
            ],
        ]);
    }
}
