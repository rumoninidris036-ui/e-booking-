<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SMASHCOURT | {{ $field->name }}</title>

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
                            'on-primary-fixed-variant': '#003fa4',
                            'primary-fixed-dim': '#b3c5ff',
                            'tertiary-fixed': '#ffdbcc',
                            'surface-bright': '#37393d',
                            'surface-dim': '#111316',
                            'surface-container-lowest': '#0c0e11',
                            'surface-variant': '#333538',
                            'outline': '#8c90a1',
                            'tertiary-container': '#c04f00',
                            'background': '#111316',
                            'secondary': '#ffffff',
                            'tertiary-fixed-dim': '#ffb693',
                            'surface-tint': '#b3c5ff',
                            'inverse-primary': '#0054d6',
                            'on-surface-variant': '#c2c6d8',
                            'inverse-on-surface': '#2f3034',
                            'surface': '#111316',
                            'secondary-container': '#c3f400',
                            'secondary-fixed-dim': '#abd600',
                            'on-primary': '#002b75',
                            'surface-container-high': '#282a2d',
                            'secondary-fixed': '#c3f400',
                            'error-container': '#93000a',
                            'on-surface': '#e2e2e6',
                            'on-secondary-container': '#556d00',
                            'on-primary-container': '#f8f7ff',
                            'tertiary': '#ffb693',
                            'on-tertiary-fixed-variant': '#7a3000',
                            'on-tertiary': '#561f00',
                            'error': '#ffb4ab',
                            'inverse-surface': '#e2e2e6',
                            'surface-container': '#1e2023',
                            'on-tertiary-container': '#fff7f4',
                            'on-background': '#e2e2e6',
                            'surface-container-low': '#1a1c1f',
                            'on-error-container': '#ffdad6',
                            'outline-variant': '#424656',
                            'on-secondary-fixed-variant': '#3c4d00',
                            'on-tertiary-fixed': '#351000',
                            'primary-container': '#0066ff',
                            'on-secondary': '#283500',
                            'on-error': '#690005',
                            'primary': '#b3c5ff',
                            'on-primary-fixed': '#001849',
                            'on-secondary-fixed': '#161e00',
                            'surface-container-highest': '#333538',
                            'primary-fixed': '#dae1ff',
                        },
                        borderRadius: {
                            DEFAULT: '0.25rem',
                            lg: '0.5rem',
                            xl: '0.75rem',
                            full: '9999px',
                        },
                        spacing: {
                            gutter: '16px',
                            'margin-mobile': '20px',
                            'margin-desktop': '40px',
                            base: '8px',
                        },
                        fontFamily: {
                            'label-bold': ['Inter', 'sans-serif'],
                            'headline-md': ['Montserrat', 'sans-serif'],
                            'headline-lg': ['Montserrat', 'sans-serif'],
                            'headline-xl': ['Montserrat', 'sans-serif'],
                            'body-md': ['Inter', 'sans-serif'],
                            'body-lg': ['Inter', 'sans-serif'],
                            'headline-xl-mobile': ['Montserrat', 'sans-serif'],
                        },
                        fontSize: {
                            'label-bold': ['14px', { lineHeight: '1', letterSpacing: '0.05em', fontWeight: '600' }],
                            'headline-md': ['24px', { lineHeight: '1.3', fontWeight: '700' }],
                            'headline-lg': ['32px', { lineHeight: '1.2', fontWeight: '700' }],
                            'headline-xl': ['48px', { lineHeight: '1.1', letterSpacing: '-0.02em', fontWeight: '800' }],
                            'body-md': ['16px', { lineHeight: '1.5', fontWeight: '400' }],
                            'body-lg': ['18px', { lineHeight: '1.6', fontWeight: '400' }],
                            'headline-xl-mobile': ['32px', { lineHeight: '1.2', fontWeight: '800' }],
                        },
                    },
                },
            };
        </script>

        <style>
            .skew-container { transform: skewY(-2deg); }
            .unskew-content { transform: skewY(2deg); }
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
            .glass-nav { backdrop-filter: blur(12px); background: rgba(17, 19, 22, 0.8); border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
            .neon-glow { box-shadow: 0 0 15px rgba(195, 244, 0, 0.3); }
            .leaflet-container { background: #1a1c1f; }
        </style>
    </head>
    <body class="overflow-x-hidden bg-background font-body-md text-on-background">
        @php
            $primaryCta = route('public.fields.booking', ['slug' => $field->slug]);
            $coverImage = $field->cover_image_url ?: 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1400&q=80';
            $sideImageOne = 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=900&q=80';
            $sideImageTwo = 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=900&q=80';
            $facilityIcons = [
                'toilet' => 'wc',
                'mushola' => 'mosque',
                'kantin' => 'local_cafe',
                'parkiran' => 'local_parking',
                'wifi' => 'wifi',
                'shower' => 'shower',
                'locker-room' => 'lock',
            ];
        @endphp

        <nav class="glass-nav fixed top-0 z-50 w-full shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-gutter py-4 md:px-margin-desktop">
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="font-headline-md text-headline-md font-black italic tracking-tighter text-secondary-container">SMASHCOURT</a>
                </div>
                <div class="hidden items-center gap-8 md:flex">
                    <a class="border-b-2 border-secondary-container pb-1 font-body-md text-body-md font-bold text-secondary-container" href="{{ url('/') }}">Find Courts</a>
                    <a class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container" href="#facilities">Facilities</a>
                    <a class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container" href="#location">Location</a>
                    <a class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container" href="#cta">Booking</a>
                </div>
                <div class="flex items-center gap-4">
                    @guest
                        <a href="{{ route('login') }}" class="hidden font-label-bold text-label-bold uppercase text-on-surface transition-colors hover:text-secondary-container md:block">Login</a>
                    @else
                        <a href="{{ \App\Support\RoleHome::urlFor(auth()->user()) }}" class="hidden font-label-bold text-label-bold uppercase text-on-surface transition-colors hover:text-secondary-container md:block">Dashboard</a>
                    @endguest
                    <a href="{{ $primaryCta }}" class="rounded-lg bg-secondary-container px-6 py-2 font-label-bold text-label-bold uppercase text-on-secondary neon-glow transition-transform hover:scale-105 active:scale-95">Book Now</a>
                </div>
            </div>
        </nav>

        <main class="pt-20">
            <section class="mx-auto max-w-7xl px-gutter py-8 md:px-margin-desktop">
                <div class="grid h-[500px] grid-cols-1 gap-4 md:h-[600px] md:grid-cols-12">
                    <div class="group relative overflow-hidden rounded-xl md:col-span-8">
                        <img class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" src="{{ $coverImage }}" alt="{{ $field->name }} main image">
                        <div class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent opacity-60"></div>
                        <div class="absolute bottom-6 left-6 z-10">
                            <span class="mb-2 inline-block rounded-full bg-secondary-container px-4 py-1 font-label-bold text-label-bold text-on-secondary">PREMIUM FACILITY</span>
                            <h1 class="font-headline-xl text-headline-xl-mobile uppercase italic text-secondary md:text-headline-xl">{{ $field->name }}</h1>
                        </div>
                    </div>

                    <div class="hidden grid-rows-2 gap-4 md:grid md:col-span-4">
                        <div class="relative overflow-hidden rounded-xl">
                            <img class="h-full w-full object-cover" src="{{ $sideImageOne }}" alt="{{ $field->name }} detail photo">
                        </div>
                        <div class="relative overflow-hidden rounded-xl">
                            <img class="h-full w-full object-cover" src="{{ $sideImageTwo }}" alt="{{ $field->name }} lounge photo">
                        </div>
                    </div>
                </div>
            </section>

            <section class="skew-container overflow-hidden bg-surface-container-low py-12">
                <div class="unskew-content mx-auto flex max-w-7xl flex-col items-center justify-between gap-8 px-gutter md:flex-row md:px-margin-desktop">
                    <div class="flex items-center gap-6">
                        <div class="rounded-xl border-l-4 border-secondary-container bg-secondary-container/10 p-6">
                            <p class="mb-1 font-label-bold text-label-bold uppercase tracking-widest text-secondary-container">Peak Rate</p>
                            <p class="font-headline-xl text-headline-xl-mobile text-secondary md:text-headline-xl">
                                Rp{{ number_format((float) $field->price_per_hour, 0, ',', '.') }}<span class="text-body-lg">/hr</span>
                            </p>
                        </div>
                        <div>
                            <p class="mb-2 font-headline-md text-headline-md text-on-surface">Pro-Grade Surface</p>
                            <p class="max-w-sm text-on-surface-variant">
                                {{ $field->description ?: 'Lapangan premium dengan suasana kompetitif, pencahayaan terang, dan permukaan permainan yang nyaman untuk sesi latihan maupun pertandingan.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex flex-col items-center rounded-xl bg-surface-variant px-6 py-4">
                            <span class="material-symbols-outlined mb-2 text-4xl text-secondary-container">timer</span>
                            <span class="font-label-bold text-label-bold uppercase">Open 24/7</span>
                        </div>
                        <div class="flex flex-col items-center rounded-xl bg-surface-variant px-6 py-4">
                            <span class="material-symbols-outlined mb-2 text-4xl text-secondary-container">stadium</span>
                            <span class="font-label-bold text-label-bold uppercase">{{ $field->facilities->count() }} Facilities</span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="facilities" class="mx-auto max-w-7xl px-gutter py-20 md:px-margin-desktop">
                <h2 class="mb-12 border-l-8 border-secondary-container pl-6 font-headline-lg text-headline-lg uppercase italic text-secondary">Elite Facilities</h2>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-5">
                    @forelse ($field->facilities as $facility)
                        <div class="group flex flex-col items-center justify-center rounded-xl border border-outline-variant bg-surface-container p-8 transition-colors hover:border-secondary-container">
                            <span class="material-symbols-outlined mb-4 text-4xl text-on-surface-variant group-hover:text-secondary-container">
                                {{ $facilityIcons[$facility->slug] ?? 'sports_score' }}
                            </span>
                            <span class="text-center font-label-bold text-label-bold uppercase">{{ $facility->name }}</span>
                        </div>
                    @empty
                        <div class="col-span-full rounded-xl border border-outline-variant bg-surface-container p-8 text-on-surface-variant">
                            Fasilitas untuk lapangan ini belum ditambahkan.
                        </div>
                    @endforelse
                </div>
            </section>

            <section id="location" class="mx-auto max-w-7xl px-gutter pb-24 md:px-margin-desktop">
                <div class="grid grid-cols-1 items-stretch gap-8 md:grid-cols-2">
                    <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-white/5 bg-surface-container p-8 md:p-12">
                        <div class="z-10">
                            <h3 class="mb-4 font-headline-lg text-headline-lg text-secondary">Location</h3>
                            <p class="mb-4 font-body-lg text-body-lg text-on-surface-variant">
                                {{ $field->address ?: 'Alamat venue belum diisi.' }}
                            </p>
                            <p class="mb-8 text-on-surface-variant">
                                Dikelola oleh {{ $field->owner?->name ?? 'Owner Venue' }}. Peta menggunakan {{ $mapMeta['provider'] }} dengan {{ $mapMeta['library'] }}.
                            </p>
                            <a id="cta" href="{{ $primaryCta }}" class="inline-flex items-center gap-2 rounded-xl bg-secondary-container px-10 py-4 font-label-bold text-label-bold uppercase text-on-secondary transition-transform hover:scale-105">
                                Continue To Booking
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                        <div class="absolute -bottom-10 -right-10 opacity-10">
                            <span class="material-symbols-outlined text-[200px]">sports_tennis</span>
                        </div>
                    </div>

                    <div class="h-[400px] overflow-hidden rounded-3xl border border-white/10">
                        <div id="field-map" class="h-full w-full"></div>
                    </div>
                </div>
            </section>

            <section id="booking-preview" class="mx-auto max-w-7xl px-gutter pb-24 md:px-margin-desktop">
                <div class="rounded-3xl border border-white/10 bg-surface-container p-8 md:p-10">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="mb-2 font-label-bold text-label-bold uppercase tracking-widest text-secondary-container">Next Step</p>
                            <h3 class="font-headline-md text-headline-md text-secondary">Lanjut pilih tanggal dan slot booking</h3>
                            <p class="mt-3 max-w-2xl text-on-surface-variant">
                                Setelah melihat detail venue, publik sekarang bisa masuk ke halaman booking untuk memilih tanggal dan jam.
                                Login baru dibutuhkan saat benar-benar melakukan konfirmasi booking.
                            </p>
                        </div>
                        <a href="{{ $primaryCta }}" class="inline-flex items-center gap-2 rounded-xl border border-primary px-6 py-4 font-label-bold text-label-bold uppercase text-primary transition-all hover:bg-primary/10">
                            Open Booking Page
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="w-full border-t border-outline-variant bg-surface-container-lowest py-12">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-base px-gutter md:flex-row md:px-margin-desktop">
                <div class="mb-6 md:mb-0">
                    <span class="font-headline-md text-headline-md font-black text-secondary-container">SMASHCOURT</span>
                    <p class="mt-2 font-body-md text-body-md text-on-surface-variant">© {{ now()->year }} SMASHCOURT. Engineered for Performance.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-6">
                    <a class="font-body-md text-body-md text-on-surface-variant opacity-80 transition-colors hover:text-secondary-container hover:opacity-100" href="{{ url('/') }}">Find Courts</a>
                    <a class="font-body-md text-body-md text-on-surface-variant opacity-80 transition-colors hover:text-secondary-container hover:opacity-100" href="#facilities">Facilities</a>
                    <a class="font-body-md text-body-md text-on-surface-variant opacity-80 transition-colors hover:text-secondary-container hover:opacity-100" href="#location">Location</a>
                    <a class="font-body-md text-body-md text-on-surface-variant opacity-80 transition-colors hover:text-secondary-container hover:opacity-100" href="{{ $primaryCta }}">Contact Support</a>
                </div>
            </div>
        </footer>

        <script>
            const marker = @json($mapMeta['marker']);

            if (marker && marker.latitude && marker.longitude) {
                const map = L.map('field-map', {
                    scrollWheelZoom: false,
                }).setView([marker.latitude, marker.longitude], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                L.marker([marker.latitude, marker.longitude])
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width: 180px;">
                            <strong>${marker.name}</strong><br>
                            <span>${marker.address ?? ''}</span><br>
                            <span>Rp${new Intl.NumberFormat('id-ID').format(marker.price_per_hour ?? 0)}/jam</span>
                        </div>
                    `)
                    .openPopup();
            } else {
                document.getElementById('field-map').innerHTML = `
                    <div class="flex h-full items-center justify-center bg-surface-variant text-on-surface-variant">
                        Koordinat lapangan belum tersedia.
                    </div>
                `;
            }
        </script>
    </body>
</html>
