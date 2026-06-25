<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SMASHCOURT | Explore Courts</title>

        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            background: '#111316',
                            surface: '#111316',
                            'surface-container-lowest': '#0c0e11',
                            'surface-container-low': '#1a1c1f',
                            'surface-container': '#1e2023',
                            'surface-container-high': '#282a2d',
                            'surface-variant': '#333538',
                            'outline-variant': '#424656',
                            outline: '#8c90a1',
                            secondary: '#ffffff',
                            primary: '#b3c5ff',
                            'primary-container': '#0066ff',
                            'secondary-container': '#c3f400',
                            tertiary: '#ffb693',
                            'on-background': '#e2e2e6',
                            'on-surface': '#e2e2e6',
                            'on-surface-variant': '#c2c6d8',
                            'on-primary-container': '#f8f7ff',
                            'on-secondary': '#283500',
                        },
                        borderRadius: {
                            DEFAULT: '0.25rem',
                            lg: '0.5rem',
                            xl: '0.75rem',
                            full: '9999px',
                        },
                        spacing: {
                            gutter: '16px',
                            'margin-desktop': '40px',
                        },
                        fontFamily: {
                            'body-md': ['Inter', 'sans-serif'],
                            'body-lg': ['Inter', 'sans-serif'],
                            'label-bold': ['Inter', 'sans-serif'],
                            'headline-md': ['Montserrat', 'sans-serif'],
                            'headline-lg': ['Montserrat', 'sans-serif'],
                            'headline-xl': ['Montserrat', 'sans-serif'],
                        },
                        fontSize: {
                            'body-md': ['16px', { lineHeight: '1.5', fontWeight: '400' }],
                            'body-lg': ['18px', { lineHeight: '1.6', fontWeight: '400' }],
                            'label-bold': ['14px', { lineHeight: '1', letterSpacing: '0', fontWeight: '600' }],
                            'headline-md': ['24px', { lineHeight: '1.3', fontWeight: '700' }],
                            'headline-lg': ['32px', { lineHeight: '1.2', fontWeight: '700' }],
                            'headline-xl': ['48px', { lineHeight: '1.1', fontWeight: '800' }],
                        },
                    },
                },
            };
        </script>

        <style>
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
            .glass-nav { backdrop-filter: blur(12px); background: rgba(17, 19, 22, 0.82); border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
            .btn-tactile { box-shadow: 0 4px 0 0 #8eb000; transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease; }
            .btn-tactile:hover { filter: brightness(1.05); transform: translateY(-1px); }
            .btn-tactile:active { box-shadow: 0 2px 0 0 #3c4d00; transform: translateY(2px); }
            .detail-link {
                transition: transform 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            }
            .detail-link:hover {
                transform: translateY(-1px);
                border-color: #c3f400;
                background: rgba(195, 244, 0, 0.12);
                color: #c3f400;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            }
            .detail-link:active {
                transform: translateY(1px);
                box-shadow: none;
            }
            .popup-detail-link {
                display: inline-block;
                margin-top: 8px;
                padding: 8px 12px;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 10px;
                color: #e2e2e6;
                text-decoration: none;
            }
            .leaflet-container { background: #1a1c1f; }
        </style>
    </head>
    <body class="overflow-x-hidden bg-background font-body-md text-on-background">
        @php
            $fallbackImage = 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1200&q=80';
            $markers = $mapMeta['markers'] ?? collect();
        @endphp

        <x-public-navbar />

        <main class="pt-20">
            <section class="relative overflow-hidden">
                <div class="absolute inset-0">
                    <img class="h-full w-full object-cover" src="https://images.unsplash.com/photo-1613918431703-aa50889e3be2?auto=format&fit=crop&w=1800&q=80" alt="Indoor badminton court">
                    <div class="absolute inset-0 bg-gradient-to-r from-background via-background/85 to-background/30"></div>
                </div>
                <div class="relative mx-auto grid min-h-[520px] max-w-7xl items-center gap-10 px-gutter py-20 md:grid-cols-2 md:px-margin-desktop">
                    <div>
                        <span class="mb-4 inline-flex rounded-full bg-secondary-container px-4 py-1 font-label-bold text-label-bold uppercase text-on-secondary">Public Fields</span>
                        <h1 class="font-headline-xl text-[40px] uppercase italic leading-tight text-secondary md:text-headline-xl">Explore Courts</h1>
                        <p class="mt-5 max-w-xl font-body-lg text-body-lg text-on-surface-variant">
                            Pilih lapangan aktif, lihat detail fasilitas, lalu lanjut booking dari halaman publik tanpa harus masuk ke dashboard.
                        </p>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-surface-container/90 p-5 shadow-2xl backdrop-blur">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-lg bg-surface-container-high p-4">
                                <p class="font-label-bold text-label-bold uppercase text-on-surface-variant">Courts</p>
                                <p class="mt-2 font-headline-md text-headline-md text-secondary">{{ $fields->total() }}</p>
                            </div>
                            <div class="rounded-lg bg-surface-container-high p-4">
                                <p class="font-label-bold text-label-bold uppercase text-on-surface-variant">Mapped</p>
                                <p class="mt-2 font-headline-md text-headline-md text-secondary">{{ $markers instanceof \Illuminate\Support\Collection ? $markers->count() : count($markers) }}</p>
                            </div>
                            <div class="rounded-lg bg-surface-container-high p-4">
                                <p class="font-label-bold text-label-bold uppercase text-on-surface-variant">Page</p>
                                <p class="mt-2 font-headline-md text-headline-md text-secondary">{{ $fields->currentPage() }}/{{ $fields->lastPage() }}</p>
                            </div>
                        </div>
                        <a href="#courts" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-container py-4 font-label-bold text-label-bold uppercase text-on-primary-container transition hover:brightness-110">
                            <span class="material-symbols-outlined">sports_tennis</span>
                            Browse Available Courts
                        </a>
                    </div>
                </div>
            </section>

            <section id="courts" class="mx-auto max-w-7xl px-gutter py-20 md:px-margin-desktop">
                <div class="mb-10 flex flex-col justify-between gap-5 md:flex-row md:items-end">
                    <div>
                        <h2 class="font-headline-lg text-headline-lg uppercase italic text-secondary">Available Courts</h2>
                        <div class="mt-3 h-1 w-24 bg-secondary-container"></div>
                    </div>
                    <p class="max-w-lg text-on-surface-variant">
                        Menampilkan lapangan yang sudah aktif dari owner. Gunakan tombol detail untuk melihat fasilitas dan lokasi lengkap.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @forelse ($fields as $field)
                        @php
                            $coverImage = $field->cover_image_url ?: $fallbackImage;
                        @endphp
                        <article class="group overflow-hidden rounded-xl border border-white/10 bg-surface-container shadow-xl transition hover:border-secondary-container/50">
                            <a href="{{ route('public.fields.show', ['slug' => $field->slug]) }}" class="block">
                                <div class="relative h-64 overflow-hidden">
                                    <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="{{ $coverImage }}" alt="{{ $field->name }} court">
                                    <div class="absolute inset-0 bg-gradient-to-t from-background/80 via-transparent to-transparent"></div>
                                    <span class="absolute left-4 top-4 rounded bg-secondary-container px-3 py-1 font-label-bold text-label-bold uppercase text-on-secondary">Available</span>
                                </div>
                            </a>

                            <div class="p-6">
                                <div class="mb-4 flex items-start justify-between gap-4">
                                    <a href="{{ route('public.fields.show', ['slug' => $field->slug]) }}" class="font-headline-md text-headline-md text-secondary transition hover:text-secondary-container">{{ $field->name }}</a>
                                    <span class="rounded-full border border-primary/20 px-3 py-1 text-[12px] font-bold uppercase text-primary">{{ $field->facilities->count() }} Facilities</span>
                                </div>

                                <div class="space-y-3 text-on-surface-variant">
                                    <p class="flex gap-2">
                                        <span class="material-symbols-outlined text-primary">location_on</span>
                                        <span>{{ $field->address ?: 'Alamat belum tersedia' }}</span>
                                    </p>
                                    <p class="flex gap-2">
                                        <span class="material-symbols-outlined text-secondary-container">payments</span>
                                        <span class="font-bold text-secondary">Rp{{ number_format((float) $field->price_per_hour, 0, ',', '.') }}/jam</span>
                                    </p>
                                    <p class="flex gap-2">
                                        <span class="material-symbols-outlined text-tertiary">schedule</span>
                                        <span>{{ substr((string) $field->open_time, 0, 5) }} - {{ substr((string) $field->close_time, 0, 5) }}</span>
                                    </p>
                                </div>

                                <div class="mt-6 grid grid-cols-2 gap-3">
                                    <a href="{{ route('public.fields.show', ['slug' => $field->slug]) }}" class="detail-link rounded-lg border border-outline-variant bg-surface-container-low py-3 text-center font-label-bold text-label-bold uppercase text-on-surface hover:shadow-[0_8px_20px_rgba(0,0,0,0.2)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-secondary-container/70">View Details</a>
                                    <a href="{{ route('public.fields.booking', ['slug' => $field->slug]) }}" class="rounded-lg bg-secondary-container py-3 text-center font-label-bold text-label-bold uppercase text-on-secondary btn-tactile">Book</a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-outline-variant bg-surface-container p-10 text-center">
                            <h3 class="font-headline-md text-headline-md text-secondary">Belum ada lapangan aktif</h3>
                            <p class="mt-3 text-on-surface-variant">Lapangan yang diaktifkan owner akan muncul otomatis di halaman ini.</p>
                        </div>
                    @endforelse
                </div>

                @if ($fields->hasPages())
                    <div class="mt-12 rounded-xl border border-white/10 bg-surface-container p-4">
                        {{ $fields->links() }}
                    </div>
                @endif
            </section>

            <section id="map" class="mx-auto max-w-7xl px-gutter pb-24 md:px-margin-desktop">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <p class="font-label-bold text-label-bold uppercase text-secondary-container">{{ $mapMeta['provider'] }} Map</p>
                        <h2 class="mt-3 font-headline-lg text-headline-lg uppercase italic text-secondary">Court Locations</h2>
                        <p class="mt-4 text-on-surface-variant">
                            Marker tampil untuk lapangan yang sudah punya koordinat. Detail court tetap bisa dibuka walaupun koordinat belum diisi.
                        </p>
                    </div>
                    <div class="h-[460px] overflow-hidden rounded-xl border border-white/10 bg-surface-container lg:col-span-8">
                        <div id="fields-map" class="h-full w-full"></div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="w-full border-t border-outline-variant bg-surface-container-lowest py-12">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-gutter md:flex-row md:px-margin-desktop">
                <div>
                    <span class="font-headline-md text-headline-md font-black italic text-secondary-container">SMASHCOURT</span>
                    <p class="mt-2 text-on-surface-variant">Public court directory for fast booking.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-6">
                    <a class="text-on-surface-variant transition hover:text-secondary-container" href="{{ url('/') }}">Home</a>
                    <a class="text-on-surface-variant transition hover:text-secondary-container" href="#courts">Courts</a>
                    <a class="text-on-surface-variant transition hover:text-secondary-container" href="#map">Map</a>
                </div>
            </div>
        </footer>

        <script>
            const markers = @json($markers);
            const mapElement = document.getElementById('fields-map');

            if (markers.length > 0) {
                const map = L.map('fields-map', { scrollWheelZoom: false });
                const bounds = [];

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                markers.forEach((marker) => {
                    bounds.push([marker.latitude, marker.longitude]);

                    L.marker([marker.latitude, marker.longitude])
                        .addTo(map)
                        .bindPopup(`
                            <div style="min-width: 180px;">
                                <strong>${marker.name}</strong><br>
                                <span>${marker.address ?? ''}</span><br>
                                <span>Rp${new Intl.NumberFormat('id-ID').format(marker.price_per_hour ?? 0)}/jam</span><br>
                                <a href="/fields/${marker.slug}" class="detail-link popup-detail-link">View Details</a>
                            </div>
                        `);
                });

                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
            } else {
                mapElement.innerHTML = `
                    <div class="flex h-full items-center justify-center px-6 text-center text-on-surface-variant">
                        Koordinat lapangan belum tersedia.
                    </div>
                `;
            }
        </script>
    </body>
</html>
