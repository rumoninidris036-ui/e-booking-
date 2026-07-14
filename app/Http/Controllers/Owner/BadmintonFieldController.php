<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Actions\Field\CreateBadmintonFieldAction;
use App\Actions\Field\DeleteBadmintonFieldAction;
use App\Actions\Field\DeleteBadmintonFieldGalleryImageAction;
use App\Actions\Field\UpdateBadmintonFieldAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBadmintonFieldRequest;
use App\Http\Requests\Owner\UpdateBadmintonFieldRequest;
use App\Models\BadmintonField;
use App\Models\BadmintonFieldGalleryImage;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BadmintonFieldController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $owner = $request->user();
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'status' => $request->string('status')->toString() ?: 'all',
            'sort' => $request->string('sort')->toString() ?: 'latest',
        ];

        $fields = $owner
            ->ownedFields()
            ->with(['facilities', 'owner:id,name,email', 'galleryImages'])
            ->withCount([
                'bookings',
                'bookings as pending_bookings_count' => fn($query) => $query->where('status', Booking::STATUS_PENDING),
                'bookings as paid_bookings_count' => fn($query) => $query->where('status', Booking::STATUS_PAID),
            ])
            ->select('badminton_fields.*')
            ->selectSub(
                Payment::query()
                    ->selectRaw('COALESCE(SUM(payments.amount), 0)')
                    ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                    ->whereColumn('bookings.badminton_field_id', 'badminton_fields.id')
                    ->where('payments.status', Payment::STATUS_SUCCESS),
                'successful_revenue',
            )
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('address', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when($filters['status'] === 'active', fn($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn($query) => $query->where('is_active', false))
            ->when($filters['status'] === 'mapped', fn($query) => $query->whereNotNull('latitude')->whereNotNull('longitude'))
            ->when($filters['status'] === 'unmapped', fn($query) => $query->where(function ($query): void {
                $query->whereNull('latitude')->orWhereNull('longitude');
            }))
            ->when(
                $filters['sort'] === 'name',
                fn($query) => $query->orderBy('name'),
                fn($query) => $query
                    ->when($filters['sort'] === 'bookings', fn($query) => $query->orderByDesc('bookings_count'))
                    ->when($filters['sort'] === 'revenue', fn($query) => $query->orderByDesc('successful_revenue'))
                    ->latest(),
            )
            ->paginate(10)
            ->withQueryString();

        if (! $request->expectsJson()) {
            return view('owner.fields.index', [
                'fields' => $fields,
                'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
                'filters' => $filters,
                'summary' => $this->summaryForOwner((int) $owner->id),
                'owner' => $owner,
            ]);
        }

        return response()->json([
            'data' => $fields->items(),
            'meta' => [
                'current_page' => $fields->currentPage(),
                'last_page' => $fields->lastPage(),
                'per_page' => $fields->perPage(),
                'total' => $fields->total(),
                'filters' => $filters,
                'summary' => $this->summaryForOwner((int) $owner->id),
                'map' => [
                    'provider' => 'OpenStreetMap',
                    'library' => 'Leaflet.js',
                    'markers' => collect($fields->items())
                        ->map(fn(BadmintonField $field): ?array => $field->map_marker)
                        ->filter()
                        ->values(),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function summaryForOwner(int $ownerId): array
    {
        $fieldQuery = BadmintonField::query()->where('owner_id', $ownerId);

        $totalBookings = Booking::query()
            ->whereHas('field', fn($query) => $query->where('owner_id', $ownerId))
            ->count();

        $totalRevenue = Payment::query()
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('badminton_fields', 'badminton_fields.id', '=', 'bookings.badminton_field_id')
            ->where('badminton_fields.owner_id', $ownerId)
            ->where('payments.status', Payment::STATUS_SUCCESS)
            ->sum('payments.amount');

        return [
            'total_fields' => (clone $fieldQuery)->count(),
            'active_fields' => (clone $fieldQuery)->where('is_active', true)->count(),
            'inactive_fields' => (clone $fieldQuery)->where('is_active', false)->count(),
            'mapped_fields' => (clone $fieldQuery)->whereNotNull('latitude')->whereNotNull('longitude')->count(),
            'total_bookings' => $totalBookings,
            'total_revenue' => (float) $totalRevenue,
        ];
    }

    public function store(
        StoreBadmintonFieldRequest $request,
        CreateBadmintonFieldAction $createBadmintonFieldAction,
    ): JsonResponse|RedirectResponse {
        $field = $createBadmintonFieldAction->handle(
            owner: $request->user(),
            attributes: $request->validated(),
            coverImage: $request->file('cover_image'),
            galleryImage: $request->file('gallery_image'),
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->route('owner.fields.show', $field)
                ->with('status', sprintf('Lapangan %s berhasil ditambahkan.', $field->name));
        }

        return response()->json([
            'message' => 'Badminton field created successfully.',
            'data' => $field,
        ], 201);
    }

    public function show(Request $request, BadmintonField $badmintonField): JsonResponse|RedirectResponse|View
    {
        $this->authorize('view', $badmintonField);
        $field = $badmintonField->loadCount(['bookings'])
            ->load(['facilities', 'owner', 'galleryImages']);

        if (! $request->expectsJson()) {
            return view('owner.fields.show', [
                'field' => $field,
                'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
                'defaultLatitude' => -3.6954,
                'defaultLongitude' => 128.1814,
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

    public function update(
        UpdateBadmintonFieldRequest $request,
        BadmintonField $badmintonField,
        UpdateBadmintonFieldAction $updateBadmintonFieldAction,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $badmintonField);

        $field = $updateBadmintonFieldAction->handle(
            badmintonField: $badmintonField,
            attributes: $request->validated(),
            coverImage: $request->file('cover_image'),
            galleryImage: $request->file('gallery_image'),
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->route('owner.fields.show', $field)
                ->with('status', sprintf('Lapangan %s berhasil diperbarui.', $field->name));
        }

        return response()->json([
            'message' => 'Badminton field updated successfully.',
            'data' => $field,
        ]);
    }

    public function destroyGalleryImage(
        BadmintonField $badmintonField,
        BadmintonFieldGalleryImage $galleryImage,
        DeleteBadmintonFieldGalleryImageAction $deleteBadmintonFieldGalleryImageAction,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $badmintonField);

        abort_unless($galleryImage->badminton_field_id === $badmintonField->id, 404);

        $deleteBadmintonFieldGalleryImageAction->handle($galleryImage);

        if (! request()->expectsJson()) {
            return redirect()
                ->route('owner.fields.show', $badmintonField)
                ->with('status', 'Foto galeri berhasil dihapus.');
        }

        return response()->json([
            'message' => 'Gallery image deleted successfully.',
        ]);
    }

    public function destroy(
        BadmintonField $badmintonField,
        DeleteBadmintonFieldAction $deleteBadmintonFieldAction,
    ): JsonResponse|RedirectResponse {
        $this->authorize('delete', $badmintonField);

        $deleteBadmintonFieldAction->handle($badmintonField);

        if (! request()->expectsJson()) {
            return redirect()
                ->route('owner.fields.index')
                ->with('status', 'Lapangan berhasil dihapus.');
        }

        return response()->json([
            'message' => 'Badminton field deleted successfully.',
        ]);
    }
}
