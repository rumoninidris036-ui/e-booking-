<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | {{ $field->name }}</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <style>
            body { font-family: Manrope, sans-serif; }
            .leaflet-container { background: #eef4fb; }
        </style>
    </head>
    <body class="min-h-screen bg-shell font-body text-ink">
        @php
            $defaultLatitude = $defaultLatitude ?? -3.6954;
            $defaultLongitude = $defaultLongitude ?? 128.1814;
            $fieldLatitude = old('latitude', $field->latitude ?? $defaultLatitude);
            $fieldLongitude = old('longitude', $field->longitude ?? $defaultLongitude);
            $fieldOpenTime = old('open_time', substr((string) ($field->open_time ?? \App\Services\Booking\FieldScheduleService::DEFAULT_OPEN_TIME), 0, 5));
            $fieldCloseTime = old('close_time', substr((string) ($field->close_time ?? \App\Services\Booking\FieldScheduleService::DEFAULT_CLOSE_TIME), 0, 5));
            $fieldSlotDuration = old('slot_duration_minutes', $field->slot_duration_minutes ?? \App\Services\Booking\FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES);
            $selectedFacilityIds = collect(old('facility_ids', $field->facilities->pluck('id')->all()))->map(fn ($id) => (int) $id);
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            <x-role-sidebar />

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <div>
                            <p class="hidden text-xs font-bold uppercase tracking-[0.22em] text-brand md:block">Kelola Venue</p>
                            <h1 class="font-display text-xl font-bold md:text-2xl">{{ $field->name }}</h1>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <a href="{{ route('owner.fields.index') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Kembali</a>
                            <div class="border-l border-line pl-4">
                                @include('layouts.topbar-profile-menu')
                            </div>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 md:px-6">
                    @if (session('status'))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (($errors ?? null)?->any())
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
                <section class="space-y-6 rounded-3xl border border-line bg-panel p-6 shadow-card">
                    <div class="overflow-hidden rounded-2xl bg-slate-100">
                        @if ($field->cover_image_url)
                            <img src="{{ $field->cover_image_url }}" alt="{{ $field->name }}" class="h-72 w-full object-cover">
                        @else
                            <div class="flex h-72 items-center justify-center text-3xl font-extrabold text-slate-400">{{ strtoupper(substr($field->name, 0, 2)) }}</div>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Status</p>
                            <p class="mt-2 font-bold">{{ $field->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Harga / jam</p>
                            <p class="mt-2 font-bold">Rp{{ number_format((float) $field->price_per_hour, 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Booking</p>
                            <p class="mt-2 font-bold">{{ number_format((int) $field->bookings_count ?? 0) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Fasilitas</p>
                            <p class="mt-2 font-bold">{{ $field->facilities->count() }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Alamat</p>
                        <p class="mt-2 text-sm">{{ $field->address ?: 'Alamat belum diisi.' }}</p>
                    </div>

                    @if ($field->galleryImages->isNotEmpty())
                        <div>
                            <p class="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Galeri</p>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($field->galleryImages as $galleryImage)
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        <img src="{{ $galleryImage->url }}" alt="{{ $field->name }} gallery {{ $loop->iteration }}" class="h-28 w-full object-cover">
                                        @if ($galleryImage->caption)
                                            <div class="px-3 py-2 text-xs text-slate-600">{{ $galleryImage->caption }}</div>
                                        @endif
                                        <div class="px-3 pb-3 pt-2">
                                            <form method="POST" action="{{ route('owner.fields.gallery-images.destroy', [$field, $galleryImage]) }}" onsubmit="return confirm('Hapus foto ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-bold uppercase tracking-[0.16em] text-rose-600">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                <section class="rounded-3xl border border-line bg-panel p-6 shadow-card">
                    <form method="POST" action="{{ route('owner.fields.update', $field) }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Nama Lapangan</label>
                                <input name="name" value="{{ old('name', $field->name) }}" required class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Harga per Jam</label>
                                <input name="price_per_hour" type="number" min="0" value="{{ old('price_per_hour', $field->price_per_hour) }}" required class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Alamat</label>
                            <input name="address" value="{{ old('address', $field->address) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Deskripsi</label>
                            <textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ old('description', $field->description) }}</textarea>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Jam Buka</label>
                                <input name="open_time" value="{{ $fieldOpenTime }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Jam Tutup</label>
                                <input name="close_time" value="{{ $fieldCloseTime }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Durasi Slot</label>
                                <input name="slot_duration_minutes" type="number" min="30" max="240" step="15" value="{{ $fieldSlotDuration }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Cover Baru</label>
                            <input name="cover_image" type="file" accept="image/*" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Gallery Baru</label>
                            <input name="gallery_image" type="file" accept="image/*" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <input name="gallery_caption" value="{{ old('gallery_caption') }}" placeholder="Caption foto baru" class="mt-3 w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Fasilitas</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($facilities as $facility)
                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                        <input type="checkbox" name="facility_ids[]" value="{{ $facility->id }}" @checked($selectedFacilityIds->contains($facility->id))>
                                        {{ $facility->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (string) (int) $field->is_active) === '1')>
                                Aktifkan lapangan
                            </label>
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold">
                                <input type="checkbox" name="remove_cover_image" value="1">
                                Hapus cover lama
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Latitude</label>
                                <input id="latitude" name="latitude" value="{{ $fieldLatitude }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Longitude</label>
                                <input id="longitude" name="longitude" value="{{ $fieldLongitude }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            </div>
                        </div>

                        <div id="field-map" data-field-map data-lat-input="latitude" data-lng-input="longitude" data-lat="{{ $fieldLatitude }}" data-lng="{{ $fieldLongitude }}" class="h-[360px] overflow-hidden rounded-3xl border border-slate-200"></div>

                        <button type="submit" class="w-full rounded-2xl bg-slate-900 px-5 py-4 text-sm font-extrabold uppercase tracking-[0.18em] text-white">Simpan Perubahan</button>
                    </form>

                    <form id="delete-field" method="POST" action="{{ route('owner.fields.destroy', $field) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Hapus lapangan ini?')" class="w-full rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-extrabold uppercase tracking-[0.18em] text-rose-700">Hapus Lapangan</button>
                    </form>
                </section>
            </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            const defaultCenter = [{{ $defaultLatitude }}, {{ $defaultLongitude }}];

            function toCoordinate(value, fallback) {
                const parsed = Number.parseFloat(value);
                return Number.isFinite(parsed) ? parsed : fallback;
            }

            const element = document.querySelector('[data-field-map]');

            if (element && window.L) {
                const latitudeInput = document.getElementById(element.dataset.latInput);
                const longitudeInput = document.getElementById(element.dataset.lngInput);
                const map = L.map(element, { scrollWheelZoom: false }).setView([
                    toCoordinate(element.dataset.lat, defaultCenter[0]),
                    toCoordinate(element.dataset.lng, defaultCenter[1]),
                ], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                const marker = L.marker(map.getCenter(), { draggable: true }).addTo(map);

                const sync = (latlng) => {
                    latitudeInput.value = latlng.lat.toFixed(7);
                    longitudeInput.value = latlng.lng.toFixed(7);
                };

                marker.on('dragend', () => sync(marker.getLatLng()));
                map.on('click', (event) => {
                    marker.setLatLng(event.latlng);
                    sync(event.latlng);
                });

                window.setTimeout(() => map.invalidateSize(), 150);
            }
        </script>
    </body>
</html>
