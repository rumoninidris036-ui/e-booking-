<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Lapangan Saya</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            ink: '#0f172a',
                            slateSoft: '#64748b',
                            panel: '#ffffff',
                            shell: '#f4f7fb',
                            line: '#dbe3ef',
                            brand: '#0f7ae5',
                            brandSoft: '#e7f2ff',
                            nav: '#141b24',
                            limePop: '#b7f500',
                        },
                        fontFamily: {
                            body: ['Manrope', 'sans-serif'],
                            display: ['Space Grotesk', 'sans-serif'],
                        },
                        boxShadow: {
                            card: '0 2px 0 rgba(15, 23, 42, 0.08), 0 12px 30px rgba(15, 23, 42, 0.06)',
                        },
                    },
                },
            };
        </script>

        <style>
            .leaflet-container {
                background: #eef4fb;
                font-family: Manrope, sans-serif;
            }
        </style>
    </head>
    <body class="min-h-screen bg-shell font-body text-ink">
        @php
            $defaultLatitude = -3.6954;
            $defaultLongitude = 128.1814;
            $summary = $summary ?? [
                'total_fields' => 0,
                'active_fields' => 0,
                'inactive_fields' => 0,
                'mapped_fields' => 0,
                'total_bookings' => 0,
                'total_revenue' => 0,
            ];
            $filters = $filters ?? [
                'search' => '',
                'status' => 'all',
                'sort' => 'latest',
            ];
            $rupiah = fn (float|int $amount): string => 'Rp '.number_format((float) $amount, 0, ',', '.');
            $compactRupiah = function (float|int $amount): string {
                $amount = (float) $amount;

                if ($amount >= 1000000000) {
                    return 'Rp '.number_format($amount / 1000000000, 1, ',', '.').'B';
                }

                if ($amount >= 1000000) {
                    return 'Rp '.number_format($amount / 1000000, 1, ',', '.').'M';
                }

                if ($amount >= 1000) {
                    return 'Rp '.number_format($amount / 1000, 0, ',', '.').'K';
                }

                return 'Rp '.number_format($amount, 0, ',', '.');
            };
            $navItems = [
                ['label' => 'Dashboard', 'href' => route('owner.dashboard'), 'active' => false, 'icon' => 'D'],
                ['label' => 'Lapangan Saya', 'href' => route('owner.fields.index'), 'active' => true, 'icon' => 'L'],
                ['label' => 'Jadwal', 'href' => route('owner.schedules.index'), 'active' => false, 'icon' => 'J'],
                ['label' => 'Booking', 'href' => route('owner.bookings.index'), 'active' => false, 'icon' => 'B'],
            ];
            $kpis = [
                ['label' => 'Total Lapangan', 'value' => number_format((int) $summary['total_fields']), 'hint' => 'semua venue', 'tone' => 'text-brand bg-brandSoft', 'icon' => 'LP'],
                ['label' => 'Aktif', 'value' => number_format((int) $summary['active_fields']), 'hint' => 'muncul di publik', 'tone' => 'text-emerald-700 bg-emerald-50', 'icon' => 'ON'],
                ['label' => 'Belum Aktif', 'value' => number_format((int) $summary['inactive_fields']), 'hint' => 'draft/nonaktif', 'tone' => 'text-amber-700 bg-amber-50', 'icon' => 'DR'],
                ['label' => 'Sudah Dipin', 'value' => number_format((int) $summary['mapped_fields']), 'hint' => 'koordinat OSM', 'tone' => 'text-sky-700 bg-sky-50', 'icon' => 'OS'],
                ['label' => 'Revenue', 'value' => $compactRupiah((float) $summary['total_revenue']), 'hint' => number_format((int) $summary['total_bookings']).' booking', 'tone' => 'text-lime-700 bg-lime-50', 'icon' => 'RV'],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            <aside class="hidden border-r border-white/10 bg-nav text-white lg:block">
                <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-brand font-display text-sm font-bold">SC</div>
                    <div>
                        <div class="font-display text-sm font-bold">SmashCourt</div>
                        <div class="text-[10px] uppercase tracking-[0.22em] text-slate-400">Owner Panel</div>
                    </div>
                </div>

                <nav class="px-3 py-6">
                    <div class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Management</div>
                    <div class="space-y-1">
                        @foreach ($navItems as $item)
                            <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition {{ $item['active'] ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg border border-white/10 text-[10px] font-bold">{{ $item['icon'] }}</span>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </nav>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <div>
                            <p class="hidden text-xs font-bold uppercase tracking-[0.22em] text-brand md:block">Venue Management</p>
                            <h1 class="font-display text-xl font-bold md:text-2xl">Lapangan Saya</h1>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <a href="{{ route('owner.dashboard') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Dashboard</a>
                            <a href="#create-field" class="rounded-xl bg-brand px-4 py-2 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-blue-600">Tambah Lapangan</a>
                            <div class="hidden items-center gap-3 border-l border-line pl-4 md:flex">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand font-bold text-white">{{ strtoupper(substr($owner->name, 0, 2)) }}</div>
                                <div>
                                    <div class="text-sm font-bold">{{ $owner->name }}</div>
                                    <div class="text-xs text-slateSoft">Owner Venue</div>
                                </div>
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

                    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ($kpis as $kpi)
                            <article class="rounded-2xl border border-line bg-panel p-5 shadow-card">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">{{ $kpi['label'] }}</p>
                                        <p class="mt-3 font-display text-3xl font-bold">{{ $kpi['value'] }}</p>
                                    </div>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl text-xs font-extrabold {{ $kpi['tone'] }}">{{ $kpi['icon'] }}</div>
                                </div>
                                <p class="mt-4 text-xs font-semibold text-slateSoft">{{ $kpi['hint'] }}</p>
                            </article>
                        @endforeach
                    </section>

                    <section id="create-field" class="rounded-3xl border border-line bg-panel shadow-card">
                        <div class="border-b border-line px-5 py-5">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-brand">Create Venue</p>
                            <h2 class="mt-1 font-display text-2xl font-bold">Tambah Lapangan Baru</h2>
                            <p class="mt-2 text-sm text-slateSoft">Klik atau drag marker pada map untuk menyimpan koordinat lapangan berbasis OpenStreetMap.</p>
                        </div>

                        <form method="POST" action="{{ route('owner.fields.store') }}" enctype="multipart/form-data" class="grid gap-6 p-5 xl:grid-cols-[0.95fr_1.05fr]">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Nama Lapangan</label>
                                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="Contoh: Olympic Arena">
                                </div>

                                <div>
                                    <label for="address" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Alamat</label>
                                    <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="Alamat venue">
                                </div>

                                <div>
                                    <label for="description" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Deskripsi</label>
                                    <textarea id="description" name="description" rows="4" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="Deskripsi singkat lapangan">{{ old('description') }}</textarea>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="price_per_hour" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Harga per Jam</label>
                                        <input id="price_per_hour" name="price_per_hour" type="number" min="0" value="{{ old('price_per_hour') }}" required class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="100000">
                                    </div>
                                    <div>
                                        <label for="cover_image" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Cover Image</label>
                                        <input id="cover_image" name="cover_image" type="file" accept="image/*" class="w-full rounded-2xl border border-line bg-slate-50 px-4 py-2 text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-bold file:text-white">
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-line bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Pengaturan Jadwal</p>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                        <div>
                                            <label for="open_time" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Jam Buka</label>
                                            <input id="open_time" name="open_time" type="text" inputmode="numeric" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" value="{{ old('open_time', \App\Services\Booking\FieldScheduleService::DEFAULT_OPEN_TIME) }}" required class="w-full rounded-2xl border-line bg-white px-4 py-3 text-sm focus:border-brand focus:ring-brand/20" placeholder="08:00">
                                        </div>
                                        <div>
                                            <label for="close_time" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Jam Tutup</label>
                                            <input id="close_time" name="close_time" type="text" inputmode="numeric" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" value="{{ old('close_time', \App\Services\Booking\FieldScheduleService::DEFAULT_CLOSE_TIME) }}" required class="w-full rounded-2xl border-line bg-white px-4 py-3 text-sm focus:border-brand focus:ring-brand/20" placeholder="22:00">
                                        </div>
                                        <div>
                                            <label for="slot_duration_minutes" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Durasi Slot</label>
                                            <input id="slot_duration_minutes" name="slot_duration_minutes" type="number" min="30" max="240" step="15" value="{{ old('slot_duration_minutes', \App\Services\Booking\FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES) }}" required class="w-full rounded-2xl border-line bg-white px-4 py-3 text-sm focus:border-brand focus:ring-brand/20" placeholder="60">
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold text-slateSoft">Gunakan format 24 jam, contoh 08:00, 13:30, 22:00.</p>
                                </div>

                                <div>
                                    <p class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Fasilitas</p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($facilities as $facility)
                                            <label class="flex items-center gap-3 rounded-2xl border border-line bg-slate-50 px-4 py-3 text-sm font-semibold">
                                                <input type="checkbox" name="facility_ids[]" value="{{ $facility->id }}" @checked(collect(old('facility_ids', []))->map(fn ($id) => (int) $id)->contains($facility->id)) class="rounded border-line text-brand focus:ring-brand">
                                                {{ $facility->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <label class="flex items-center gap-3 rounded-2xl border border-line bg-slate-50 px-4 py-3 text-sm font-bold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1') class="rounded border-line text-brand focus:ring-brand">
                                    Aktifkan lapangan untuk publik
                                </label>
                            </div>

                            <div class="space-y-4">
                                <div
                                    id="create-map"
                                    data-field-map
                                    data-lat-input="create-latitude"
                                    data-lng-input="create-longitude"
                                    data-lat="{{ old('latitude', $defaultLatitude) }}"
                                    data-lng="{{ old('longitude', $defaultLongitude) }}"
                                    class="h-[420px] overflow-hidden rounded-3xl border border-line"
                                ></div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="create-latitude" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Latitude</label>
                                        <input id="create-latitude" name="latitude" type="text" value="{{ old('latitude', $defaultLatitude) }}" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                    </div>
                                    <div>
                                        <label for="create-longitude" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Longitude</label>
                                        <input id="create-longitude" name="longitude" type="text" value="{{ old('longitude', $defaultLongitude) }}" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                    </div>
                                </div>

                                <button type="submit" class="w-full rounded-2xl bg-brand px-5 py-4 text-sm font-extrabold uppercase tracking-[0.18em] text-white shadow-lg shadow-brand/20 transition hover:bg-blue-600">
                                    Simpan Lapangan
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="rounded-3xl border border-line bg-panel shadow-card">
                        <div class="flex flex-col gap-4 border-b border-line px-5 py-5 xl:flex-row xl:items-end xl:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.22em] text-brand">My Fields</p>
                                <h2 class="mt-1 font-display text-2xl font-bold">Daftar Lapangan</h2>
                                <p class="mt-2 text-sm text-slateSoft">{{ $fields->total() }} lapangan cocok dengan filter saat ini</p>
                            </div>
                            <form action="{{ route('owner.fields.index') }}" method="GET" class="grid gap-3 md:grid-cols-[minmax(220px,1fr)_150px_150px_auto] xl:w-[720px]">
                                <input name="search" type="search" value="{{ $filters['search'] }}" class="rounded-xl border-line bg-slate-50 px-4 py-2 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="Cari nama atau alamat">
                                <select name="status" class="rounded-xl border-line bg-slate-50 text-sm focus:border-brand focus:ring-brand/20">
                                    <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
                                    <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                                    <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                                    <option value="mapped" @selected($filters['status'] === 'mapped')>Sudah dipin</option>
                                    <option value="unmapped" @selected($filters['status'] === 'unmapped')>Belum dipin</option>
                                </select>
                                <select name="sort" class="rounded-xl border-line bg-slate-50 text-sm focus:border-brand focus:ring-brand/20">
                                    <option value="latest" @selected($filters['sort'] === 'latest')>Terbaru</option>
                                    <option value="name" @selected($filters['sort'] === 'name')>Nama A-Z</option>
                                    <option value="bookings" @selected($filters['sort'] === 'bookings')>Booking ramai</option>
                                    <option value="revenue" @selected($filters['sort'] === 'revenue')>Revenue besar</option>
                                </select>
                                <button type="submit" class="rounded-xl bg-ink px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700">Filter</button>
                            </form>
                        </div>

                        <div class="divide-y divide-line">
                            @forelse ($fields as $field)
                                @php
                                    $fieldLatitude = old("fields.{$field->id}.latitude", $field->latitude ?? $defaultLatitude);
                                    $fieldLongitude = old("fields.{$field->id}.longitude", $field->longitude ?? $defaultLongitude);
                                    $fieldOpenTime = old('open_time', substr((string) ($field->open_time ?? \App\Services\Booking\FieldScheduleService::DEFAULT_OPEN_TIME), 0, 5));
                                    $fieldCloseTime = old('close_time', substr((string) ($field->close_time ?? \App\Services\Booking\FieldScheduleService::DEFAULT_CLOSE_TIME), 0, 5));
                                    $fieldSlotDuration = old('slot_duration_minutes', $field->slot_duration_minutes ?? \App\Services\Booking\FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES);
                                    $selectedFacilityIds = collect(old("fields.{$field->id}.facility_ids", $field->facilities->pluck('id')->all()))->map(fn ($id) => (int) $id);
                                @endphp

                                <article id="field-{{ $field->id }}" class="p-5 {{ (string) request('focus') === (string) $field->id ? 'bg-brandSoft/40' : '' }}">
                                    <form method="POST" action="{{ route('owner.fields.update', $field) }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[260px_1fr_0.9fr]">
                                        @csrf
                                        @method('PUT')

                                        <div class="space-y-4">
                                            <div class="aspect-[4/3] overflow-hidden rounded-3xl border border-line bg-slate-100">
                                                @if ($field->cover_image_url)
                                                    <img src="{{ $field->cover_image_url }}" alt="{{ $field->name }}" class="h-full w-full object-cover">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brandSoft to-slate-100 font-display text-3xl font-bold text-brand">
                                                        {{ strtoupper(substr($field->name, 0, 2)) }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="rounded-2xl border border-line bg-slate-50 p-4">
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Status</span>
                                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $field->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' }}">
                                                        {{ $field->is_active ? 'Active' : 'Nonactive' }}
                                                    </span>
                                                </div>
                                                <div class="mt-3 text-sm font-bold">{{ $rupiah((float) $field->price_per_hour) }} / jam</div>
                                                <div class="mt-2 text-xs font-semibold text-slateSoft">{{ $fieldOpenTime }}-{{ $fieldCloseTime }} · {{ $fieldSlotDuration }} menit/slot</div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="rounded-2xl border border-line bg-white p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slateSoft">Booking</p>
                                                    <p class="mt-2 font-display text-xl font-bold">{{ number_format((int) $field->bookings_count) }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-line bg-white p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slateSoft">Revenue</p>
                                                    <p class="mt-2 font-display text-xl font-bold">{{ $compactRupiah((float) $field->successful_revenue) }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-line bg-white p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slateSoft">Pending</p>
                                                    <p class="mt-2 font-display text-xl font-bold">{{ number_format((int) $field->pending_bookings_count) }}</p>
                                                </div>
                                                <div class="rounded-2xl border border-line bg-white p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slateSoft">Paid</p>
                                                    <p class="mt-2 font-display text-xl font-bold">{{ number_format((int) $field->paid_bookings_count) }}</p>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-line bg-slate-50 p-4 text-sm">
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="font-bold text-slateSoft">Koordinat</span>
                                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $field->latitude !== null && $field->longitude !== null ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                                                        {{ $field->latitude !== null && $field->longitude !== null ? 'Sudah dipin' : 'Belum dipin' }}
                                                    </span>
                                                </div>
                                                @if ($field->latitude !== null && $field->longitude !== null)
                                                    <p class="mt-2 text-xs font-semibold text-slateSoft">{{ $field->latitude }}, {{ $field->longitude }}</p>
                                                @endif
                                            </div>

                                            <button type="submit" form="delete-field-{{ $field->id }}" onclick="return confirm('Hapus lapangan ini?')" class="w-full rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-100">
                                                Hapus Lapangan
                                            </button>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <label for="field-{{ $field->id }}-name" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Nama Lapangan</label>
                                                <input id="field-{{ $field->id }}-name" name="name" type="text" value="{{ old("fields.{$field->id}.name", $field->name) }}" required class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                            </div>

                                            <div>
                                                <label for="field-{{ $field->id }}-address" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Alamat</label>
                                                <input id="field-{{ $field->id }}-address" name="address" type="text" value="{{ old("fields.{$field->id}.address", $field->address) }}" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                            </div>

                                            <div>
                                                <label for="field-{{ $field->id }}-description" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Deskripsi</label>
                                                <textarea id="field-{{ $field->id }}-description" name="description" rows="4" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">{{ old("fields.{$field->id}.description", $field->description) }}</textarea>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label for="field-{{ $field->id }}-price" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Harga per Jam</label>
                                                    <input id="field-{{ $field->id }}-price" name="price_per_hour" type="number" min="0" value="{{ old("fields.{$field->id}.price_per_hour", $field->price_per_hour) }}" required class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                                </div>
                                                <div>
                                                    <label for="field-{{ $field->id }}-cover" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Ganti Cover</label>
                                                    <input id="field-{{ $field->id }}-cover" name="cover_image" type="file" accept="image/*" class="w-full rounded-2xl border border-line bg-slate-50 px-4 py-2 text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-bold file:text-white">
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-line bg-slate-50 p-4">
                                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Pengaturan Jadwal</p>
                                                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                                    <div>
                                                        <label for="field-{{ $field->id }}-open-time" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Jam Buka</label>
                                                        <input id="field-{{ $field->id }}-open-time" name="open_time" type="text" inputmode="numeric" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" value="{{ $fieldOpenTime }}" required class="w-full rounded-2xl border-line bg-white px-4 py-3 text-sm focus:border-brand focus:ring-brand/20" placeholder="08:00">
                                                    </div>
                                                    <div>
                                                        <label for="field-{{ $field->id }}-close-time" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Jam Tutup</label>
                                                        <input id="field-{{ $field->id }}-close-time" name="close_time" type="text" inputmode="numeric" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" value="{{ $fieldCloseTime }}" required class="w-full rounded-2xl border-line bg-white px-4 py-3 text-sm focus:border-brand focus:ring-brand/20" placeholder="22:00">
                                                    </div>
                                                    <div>
                                                        <label for="field-{{ $field->id }}-slot-duration" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Durasi Slot</label>
                                                        <input id="field-{{ $field->id }}-slot-duration" name="slot_duration_minutes" type="number" min="30" max="240" step="15" value="{{ $fieldSlotDuration }}" required class="w-full rounded-2xl border-line bg-white px-4 py-3 text-sm focus:border-brand focus:ring-brand/20">
                                                    </div>
                                                </div>
                                                <p class="mt-3 text-xs font-semibold text-slateSoft">Gunakan format 24 jam, contoh 08:00, 13:30, 22:00.</p>
                                            </div>

                                            <div>
                                                <p class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Fasilitas</p>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    @foreach ($facilities as $facility)
                                                        <label class="flex items-center gap-3 rounded-2xl border border-line bg-slate-50 px-4 py-3 text-sm font-semibold">
                                                            <input type="checkbox" name="facility_ids[]" value="{{ $facility->id }}" @checked($selectedFacilityIds->contains($facility->id)) class="rounded border-line text-brand focus:ring-brand">
                                                            {{ $facility->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <label class="flex items-center gap-3 rounded-2xl border border-line bg-slate-50 px-4 py-3 text-sm font-bold">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" @checked((bool) old("fields.{$field->id}.is_active", $field->is_active)) class="rounded border-line text-brand focus:ring-brand">
                                                    Lapangan aktif
                                                </label>
                                                <label class="flex items-center gap-3 rounded-2xl border border-line bg-slate-50 px-4 py-3 text-sm font-bold">
                                                    <input type="checkbox" name="remove_cover_image" value="1" class="rounded border-line text-rose-600 focus:ring-rose-600">
                                                    Hapus cover
                                                </label>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div
                                                id="field-map-{{ $field->id }}"
                                                data-field-map
                                                data-lat-input="field-{{ $field->id }}-latitude"
                                                data-lng-input="field-{{ $field->id }}-longitude"
                                                data-lat="{{ $fieldLatitude }}"
                                                data-lng="{{ $fieldLongitude }}"
                                                class="h-[340px] overflow-hidden rounded-3xl border border-line"
                                            ></div>

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label for="field-{{ $field->id }}-latitude" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Latitude</label>
                                                    <input id="field-{{ $field->id }}-latitude" name="latitude" type="text" value="{{ $fieldLatitude }}" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                                </div>
                                                <div>
                                                    <label for="field-{{ $field->id }}-longitude" class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">Longitude</label>
                                                    <input id="field-{{ $field->id }}-longitude" name="longitude" type="text" value="{{ $fieldLongitude }}" class="w-full rounded-2xl border-line bg-slate-50 px-4 py-3 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                                </div>
                                            </div>

                                            <button type="submit" class="w-full rounded-2xl bg-ink px-5 py-4 text-sm font-extrabold uppercase tracking-[0.18em] text-white transition hover:bg-slate-700">
                                                Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>

                                    <form id="delete-field-{{ $field->id }}" method="POST" action="{{ route('owner.fields.destroy', $field) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </article>
                            @empty
                                <div class="px-5 py-12 text-center">
                                    <h3 class="font-display text-2xl font-bold">Belum ada lapangan</h3>
                                    <p class="mt-2 text-sm text-slateSoft">Tambahkan lapangan pertama dan pin lokasinya di OSM.</p>
                                </div>
                            @endforelse
                        </div>

                        @if ($fields->hasPages())
                            <div class="border-t border-line px-5 py-4">
                                {{ $fields->links() }}
                            </div>
                        @endif
                    </section>
                </main>
            </div>
        </div>

        <script>
            const defaultCenter = [{{ $defaultLatitude }}, {{ $defaultLongitude }}];

            function toCoordinate(value, fallback) {
                const parsed = Number.parseFloat(value);

                return Number.isFinite(parsed) ? parsed : fallback;
            }

            function initializeFieldMap(element) {
                const latitudeInput = document.getElementById(element.dataset.latInput);
                const longitudeInput = document.getElementById(element.dataset.lngInput);
                const latitude = toCoordinate(element.dataset.lat, defaultCenter[0]);
                const longitude = toCoordinate(element.dataset.lng, defaultCenter[1]);
                const map = L.map(element, {
                    scrollWheelZoom: false,
                }).setView([latitude, longitude], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                const marker = L.marker([latitude, longitude], {
                    draggable: true,
                }).addTo(map);

                function writeCoordinates(latlng) {
                    latitudeInput.value = latlng.lat.toFixed(7);
                    longitudeInput.value = latlng.lng.toFixed(7);
                }

                marker.on('dragend', () => {
                    writeCoordinates(marker.getLatLng());
                });

                map.on('click', (event) => {
                    marker.setLatLng(event.latlng);
                    writeCoordinates(event.latlng);
                });

                [latitudeInput, longitudeInput].forEach((input) => {
                    input.addEventListener('change', () => {
                        const nextLatitude = toCoordinate(latitudeInput.value, marker.getLatLng().lat);
                        const nextLongitude = toCoordinate(longitudeInput.value, marker.getLatLng().lng);
                        const nextLatLng = [nextLatitude, nextLongitude];

                        marker.setLatLng(nextLatLng);
                        map.setView(nextLatLng, Math.max(map.getZoom(), 15));
                    });
                });

                window.setTimeout(() => map.invalidateSize(), 150);
            }

            document.querySelectorAll('[data-field-map]').forEach(initializeFieldMap);

            document.querySelectorAll('input[name="open_time"], input[name="close_time"]').forEach((input) => {
                input.addEventListener('change', () => {
                    const match = input.value.trim().match(/^(\d{1,2}):(\d{1,2})$/);

                    if (! match) {
                        return;
                    }

                    const hours = Number.parseInt(match[1], 10);
                    const minutes = Number.parseInt(match[2], 10);

                    if (hours > 23 || minutes > 59) {
                        return;
                    }

                    input.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                });
            });
        </script>
    </body>
</html>
