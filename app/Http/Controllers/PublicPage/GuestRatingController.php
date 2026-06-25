<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPage;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class GuestRatingController extends Controller
{
    public function create(Request $request, Booking $booking): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $booking->loadMissing(['field:id,name,slug', 'rating.booking:id,booking_code,customer_name,user_id', 'rating.booking.user:id,name']);

        if ($booking->rating()->exists()) {
            return view('public.rating.already-rated', [
                'booking' => $booking,
                'field' => $booking->field,
                'rating' => $booking->rating,
            ]);
        }

        return view('public.rating.form', [
            'booking' => $booking,
            'field' => $booking->field,
            'storeUrl' => URL::signedRoute('public.rating.store', ['booking' => $booking->id]),
        ]);
    }

    public function store(Request $request, Booking $booking): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking->loadMissing(['field:id,name,slug']);

        $wasExistingRating = false;

        $rating = DB::transaction(function () use ($booking, $validated, &$wasExistingRating): Rating {
            $lockedBooking = Booking::query()
                ->with(['field:id,name,slug'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $existingRating = $lockedBooking->rating()->with(['booking:id,booking_code,customer_name,user_id', 'booking.user:id,name'])->first();

            if ($existingRating !== null) {
                $wasExistingRating = true;

                return $existingRating;
            }

            return Rating::query()->create([
                'booking_id' => $lockedBooking->id,
                'badminton_field_id' => $lockedBooking->badminton_field_id,
                'score' => (int) $validated['score'],
                'comment' => $validated['comment'] ?? null,
            ])->load(['booking:id,booking_code,customer_name,user_id', 'booking.user:id,name', 'field:id,name,slug']);
        });

        $rating->loadMissing(['booking.field:id,name,slug', 'booking.user:id,name', 'field:id,name,slug']);

        if ($wasExistingRating) {
            return view('public.rating.already-rated', [
                'booking' => $booking,
                'field' => $booking->field,
                'rating' => $rating,
            ]);
        }

        return view('public.rating.success', [
            'booking' => $rating->booking,
            'field' => $rating->field,
            'rating' => $rating,
        ]);
    }
}
