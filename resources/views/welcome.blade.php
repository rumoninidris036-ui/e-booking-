<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'E-Booking Court') }} | Smash Your Limits</title>

        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            'secondary-container': '#c3f400',
                            'on-surface': '#e2e2e6',
                            'tertiary-fixed-dim': '#ffb693',
                            'outline-variant': '#424656',
                            'on-primary': '#002b75',
                            'inverse-primary': '#0054d6',
                            'surface': '#111316',
                            'surface-container-lowest': '#0c0e11',
                            'surface-tint': '#b3c5ff',
                            'tertiary-container': '#c04f00',
                            'primary-fixed': '#dae1ff',
                            'primary': '#b3c5ff',
                            'primary-fixed-dim': '#b3c5ff',
                            'on-background': '#e2e2e6',
                            'on-secondary-fixed': '#161e00',
                            'on-primary-container': '#f8f7ff',
                            'on-error': '#690005',
                            'on-tertiary-fixed': '#351000',
                            'surface-variant': '#333538',
                            'surface-bright': '#37393d',
                            'on-secondary-container': '#556d00',
                            'on-tertiary-fixed-variant': '#7a3000',
                            'on-tertiary-container': '#fff7f4',
                            'on-surface-variant': '#c2c6d8',
                            'secondary-fixed-dim': '#abd600',
                            'surface-container-highest': '#333538',
                            'surface-container': '#1e2023',
                            'secondary-fixed': '#c3f400',
                            'inverse-surface': '#e2e2e6',
                            'tertiary': '#ffb693',
                            'tertiary-fixed': '#ffdbcc',
                            'outline': '#8c90a1',
                            'error': '#ffb4ab',
                            'surface-container-high': '#282a2d',
                            'on-error-container': '#ffdad6',
                            'on-primary-fixed': '#001849',
                            'on-primary-fixed-variant': '#003fa4',
                            'on-secondary-fixed-variant': '#3c4d00',
                            'on-secondary': '#283500',
                            'surface-container-low': '#1a1c1f',
                            'secondary': '#ffffff',
                            'background': '#111316',
                            'on-tertiary': '#561f00',
                            'error-container': '#93000a',
                            'primary-container': '#0066ff',
                            'surface-dim': '#111316',
                            'inverse-on-surface': '#2f3034',
                        },
                        borderRadius: {
                            DEFAULT: '0.25rem',
                            lg: '0.5rem',
                            xl: '0.75rem',
                            full: '9999px',
                        },
                        spacing: {
                            gutter: '16px',
                            'skew-angle': '6deg',
                            'margin-mobile': '20px',
                            base: '8px',
                            'margin-desktop': '40px',
                        },
                        fontFamily: {
                            'body-lg': ['Inter', 'sans-serif'],
                            'body-md': ['Inter', 'sans-serif'],
                            'headline-xl': ['Montserrat', 'sans-serif'],
                            'headline-xl-mobile': ['Montserrat', 'sans-serif'],
                            'headline-lg': ['Montserrat', 'sans-serif'],
                            'headline-md': ['Montserrat', 'sans-serif'],
                            'label-bold': ['Inter', 'sans-serif'],
                        },
                        fontSize: {
                            'body-lg': ['18px', { lineHeight: '1.6', fontWeight: '400' }],
                            'body-md': ['16px', { lineHeight: '1.5', fontWeight: '400' }],
                            'headline-xl': ['48px', { lineHeight: '1.1', letterSpacing: '-0.02em', fontWeight: '800' }],
                            'headline-xl-mobile': ['32px', { lineHeight: '1.2', fontWeight: '800' }],
                            'headline-lg': ['32px', { lineHeight: '1.2', fontWeight: '700' }],
                            'headline-md': ['24px', { lineHeight: '1.3', fontWeight: '700' }],
                            'label-bold': ['14px', { lineHeight: '1', letterSpacing: '0.05em', fontWeight: '600' }],
                        },
                    },
                },
            };
        </script>

        <style>
            .skew-container {
                transform: skewY(-6deg);
            }

            .unskew-content {
                transform: skewY(6deg);
            }

            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }

            .btn-tactile {
                box-shadow: 0 4px 0 0 #3c4d00;
            }

            .btn-tactile:active {
                transform: translateY(2px);
                box-shadow: 0 2px 0 0 #3c4d00;
            }
        </style>
    </head>
    <body class="overflow-x-hidden bg-surface font-body-md text-on-surface">
        @php
            $fallbackImage = 'https://lh3.googleusercontent.com/aida-public/AB6AXuB59syCrEtoscrLtnVVbKFlVvYLgoQiQKa20vyksDs2Eq_taI-K_yu4U1RCbSt4osetGfsidCQzQ8GdqVYnQld6BAUziQKOZlTa0egECgMHdPUNRxGzg0vzY83Pk2t-b5uU76rsMj4_KzJ4XhaJCMRir6D7Gl3dKAd_U-OBM70re_uqNRz2R8_ZnNHFoaz_Pnb8OYxRGrJDt-jhH70Vx1zn_kQxXIPQRDfP0k6p5dX1gvO_Z_Ko_l1JAOXd_KBEzS8kf9xgIIklZ2bY';
            $featuredCourts = collect($homepageFields ?? [])->map(fn ($field) => [
                'name' => $field->name,
                'slug' => $field->slug,
                'rating' => '4.9',
                'location' => $field->address ?: 'Lokasi tersedia',
                'price' => 'Rp'.number_format((float) $field->price_per_hour, 0, ',', '.').'/jam',
                'badge' => 'Available',
                'badge_class' => 'bg-secondary-container/90 text-on-secondary',
                'image' => $field->cover_image_url ?: $fallbackImage,
            ])->values();

            $primaryCta = route('public.fields.index');
            $secondaryCta = auth()->check() ? \App\Support\RoleHome::urlFor(auth()->user()) : route('login');
        @endphp

        <nav class="fixed top-0 z-50 w-full border-b border-white/10 bg-surface/80 shadow-sm backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-gutter py-4 md:px-margin-desktop">
                <a href="{{ url('/') }}" class="font-headline-md text-headline-md font-black italic tracking-tighter text-secondary-container">
                    SMASHCOURT
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a class="border-b-2 border-secondary-container pb-1 font-body-md text-body-md font-bold text-secondary-container" href="{{ route('public.fields.index') }}">
                        Explore Courts
                    </a>
                    <a class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container" href="#benefits">
                        Memberships
                    </a>
                    <a class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container" href="#arenas">
                        Featured
                    </a>
                    <a class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container" href="#cta">
                        Coaching
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    @guest
                        <a href="{{ route('login') }}" class="hidden font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container md:block">
                            Login
                        </a>
                    @else
                        <a href="{{ \App\Support\RoleHome::urlFor(auth()->user()) }}" class="hidden font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container md:block">
                            Dashboard
                        </a>
                    @endguest

                    <a href="{{ $primaryCta }}" class="rounded-full bg-secondary-container px-6 py-2.5 font-label-bold text-label-bold uppercase text-on-secondary btn-tactile">
                        Explore Court
                    </a>
                </div>
            </div>
        </nav>

        <section class="relative flex min-h-[921px] items-center pt-20">
            <div class="absolute inset-0 z-0 overflow-hidden">
                <div class="absolute inset-0 z-10 bg-gradient-to-r from-surface via-surface/80 to-transparent"></div>
                <img
                    class="h-full w-full object-cover"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAr8fCfPi4HjvnoYT4r9YDmUYAgDJnYRMFNJaLxp0nwHJp5a-3ti-KklhSFAfMsBtgbDYnjhsBLH1bQVUSQ-WywMrvH2gI-chtmQJkFZFXj3RwCkGlosyxqzcRmT5fFG0bdXWLO1U5-kxknI4NVfEbtCKme89twyZtHY2nA9hYXJGtk3gi0QzwjP5n0w8V4TJjEFO2FYBbMatVTk_7Mk-LzCPpLBdZFz0itsONU3aPNkd_sDPNQyPuhGNOIeN3SlfC7EMSUEru2SqPa"
                    alt="Badminton player mid-smash in a neon-lit court"
                >
            </div>

            <div class="relative z-20 mx-auto w-full max-w-7xl px-gutter md:px-margin-desktop">
                <div class="max-w-2xl">
                    <h1 class="mb-6 font-headline-xl-mobile text-headline-xl-mobile leading-tight text-secondary md:font-headline-xl md:text-headline-xl">
                        SMASH YOUR <span class="italic text-secondary-container">LIMITS.</span><br>BOOK YOUR COURT.
                    </h1>

                    <p class="mb-10 max-w-lg font-body-lg text-body-lg text-on-surface-variant">
                        Experience pro-grade badminton facilities with instant digital booking. High-intensity lighting,
                        precision surfaces, and 24/7 access for the dedicated athlete.
                    </p>

                    <div class="flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('public.fields.index') }}" class="flex items-center justify-center gap-3 rounded-lg bg-secondary-container px-10 py-5 font-headline-md text-headline-md uppercase italic text-on-secondary btn-tactile">
                            Explore Court
                            <span class="material-symbols-outlined">bolt</span>
                        </a>

                        <a href="#arenas" class="flex items-center justify-center gap-3 rounded-lg border-2 border-primary px-10 py-5 font-headline-md text-headline-md uppercase text-primary transition-all hover:bg-primary/10">
                            Featured Courts
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div id="search" class="relative z-30 mx-auto -mt-12 max-w-6xl px-gutter md:px-margin-desktop">
            <div class="rounded-xl border border-white/5 bg-surface-container-high p-4 shadow-2xl backdrop-blur-xl md:p-6">
                <form class="grid grid-cols-1 gap-4 md:grid-cols-4" action="{{ route('public.fields.index') }}" method="GET">
                    <div class="flex flex-col gap-2">
                        <label class="ml-1 font-label-bold text-label-bold uppercase text-on-surface-variant">Location</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">location_on</span>
                            <input class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-3 pl-10 text-on-surface transition-all focus:border-primary-container focus:ring-1 focus:ring-primary-container" placeholder="Where do you play?" type="text">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="ml-1 font-label-bold text-label-bold uppercase text-on-surface-variant">Date</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">calendar_today</span>
                            <input class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-3 pl-10 text-on-surface transition-all focus:border-primary-container focus:ring-1 focus:ring-primary-container" type="date">
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="ml-1 font-label-bold text-label-bold uppercase text-on-surface-variant">Time</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">schedule</span>
                            <select class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-container-low py-3 pl-10 text-on-surface transition-all focus:border-primary-container focus:ring-1 focus:ring-primary-container">
                                <option>Any Time</option>
                                <option>Morning (6am-12pm)</option>
                                <option>Afternoon (12pm-5pm)</option>
                                <option>Evening (5pm-11pm)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-end">
                        <a href="{{ route('public.fields.index') }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-container py-4 font-label-bold text-label-bold uppercase text-on-primary-container transition-all hover:brightness-110">
                            <span class="material-symbols-outlined">search</span>
                            Find My Court
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <section id="benefits" class="relative mt-32 overflow-hidden">
            <div class="skew-container absolute inset-0 -z-10 origin-left bg-surface-container"></div>
            <div class="unskew-content mx-auto max-w-7xl px-gutter py-24 md:px-margin-desktop">
                <div class="grid grid-cols-1 gap-12 md:grid-cols-3">
                    <div class="group flex flex-col items-center text-center">
                        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl border border-secondary-container/20 bg-secondary-container/10 transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined !text-4xl text-secondary-container">verified</span>
                        </div>
                        <h3 class="mb-3 font-headline-md text-headline-md uppercase italic text-secondary">Instant Confirmation</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            No waiting for callbacks. Your booking is confirmed the second you pay through our secure portal.
                        </p>
                    </div>

                    <div class="group flex flex-col items-center text-center">
                        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined !text-4xl text-primary">sports_handball</span>
                        </div>
                        <h3 class="mb-3 font-headline-md text-headline-md uppercase italic text-secondary">Pro-Grade Surface</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            Tournament-standard mats, bright lighting, and reliable digital access built for serious rallies.
                        </p>
                    </div>

                    <div class="group flex flex-col items-center text-center">
                        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl border border-tertiary/20 bg-tertiary/10 transition-transform group-hover:scale-110">
                            <span class="material-symbols-outlined !text-4xl text-tertiary">update</span>
                        </div>
                        <h3 class="mb-3 font-headline-md text-headline-md uppercase italic text-secondary">24/7 Availability</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            From sunrise drills to late-night matches, the booking flow stays fast and available whenever you need it.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="arenas" class="mx-auto max-w-7xl px-gutter py-24 md:px-margin-desktop">
            <div class="mb-12 flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <div>
                    <h2 class="mb-2 font-headline-lg text-headline-lg uppercase italic tracking-tight text-secondary">Explore Courts</h2>
                    <div class="h-1 w-24 bg-secondary-container"></div>
                </div>

                <a class="flex items-center gap-2 font-label-bold text-label-bold uppercase text-primary transition-all hover:gap-4" href="{{ route('public.fields.index') }}">
                    View All Courts <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-base md:grid-cols-2 md:gap-8 lg:grid-cols-3">
                @forelse ($featuredCourts as $court)
                    <article class="group overflow-hidden rounded-xl border border-white/5 bg-surface-container shadow-xl transition-all hover:border-primary/30">
                        <a href="{{ route('public.fields.show', ['slug' => $court['slug']]) }}" class="block">
                            <div class="relative h-64 overflow-hidden">
                                <img class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" src="{{ $court['image'] }}" alt="{{ $court['name'] }} court">
                                <div class="absolute left-4 top-4">
                                    <span class="rounded px-3 py-1 font-label-bold text-label-bold uppercase backdrop-blur-sm {{ $court['badge_class'] }}">{{ $court['badge'] }}</span>
                                </div>
                            </div>
                        </a>
                        <div class="p-6">
                            <div class="mb-4 flex items-start justify-between">
                                <a href="{{ route('public.fields.show', ['slug' => $court['slug']]) }}" class="font-headline-md text-headline-md text-secondary hover:text-secondary-container">
                                    {{ $court['name'] }}
                                </a>
                                <div class="flex items-center text-secondary-container">
                                    <span class="material-symbols-outlined !text-lg">star</span>
                                    <span class="ml-1 font-label-bold text-label-bold">{{ $court['rating'] }}</span>
                                </div>
                            </div>
                            <div class="mb-6 flex items-center gap-4">
                                <div class="flex items-center gap-1 text-on-surface-variant">
                                    <span class="material-symbols-outlined !text-lg">location_on</span>
                                    <span class="text-label-bold">{{ $court['location'] }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-on-surface-variant">
                                    <span class="material-symbols-outlined !text-lg">currency_exchange</span>
                                    <span class="font-bold text-secondary">{{ $court['price'] }}</span>
                                </div>
                            </div>
                            <a href="{{ route('public.fields.show', ['slug' => $court['slug']]) }}" class="block w-full rounded {{ $loop->last ? 'bg-secondary-container text-on-secondary btn-tactile' : 'bg-surface-variant text-on-surface hover:bg-secondary-container hover:text-on-secondary' }} py-3 text-center font-label-bold text-label-bold uppercase transition-colors">
                                View Details
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-outline-variant bg-surface-container p-10 text-center">
                        <h3 class="font-headline-md text-headline-md text-secondary">Belum ada lapangan aktif</h3>
                        <p class="mt-3 text-on-surface-variant">Owner bisa menambahkan lapangan dari dashboard, lalu court akan tampil otomatis di homepage.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section id="cta" class="px-gutter py-20 md:px-margin-desktop">
            <div class="group relative mx-auto max-w-5xl overflow-hidden rounded-3xl bg-primary-container p-12 text-center">
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
                <div class="relative z-10">
                    <h2 class="mb-6 font-headline-xl-mobile text-headline-xl-mobile uppercase italic text-on-primary-container md:font-headline-xl md:text-headline-xl">
                        Ready to Dominate?
                    </h2>
                    <p class="mx-auto mb-10 max-w-xl font-body-lg text-body-lg text-on-primary-container/80">
                        Join players already smashing their limits with instant booking, premium courts, and fast online payment.
                    </p>
                    <a href="{{ route('public.fields.index') }}" class="inline-block rounded-xl bg-secondary-container px-12 py-6 font-headline-md text-headline-md uppercase italic text-on-secondary btn-tactile">
                        Explore Courts
                    </a>
                </div>
            </div>
        </section>

        <footer class="w-full border-t border-outline-variant bg-surface-container-lowest py-12">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-base px-gutter md:flex-row md:px-margin-desktop">
                <div class="flex flex-col gap-4">
                    <div class="font-headline-md text-headline-md font-black uppercase italic text-secondary-container">SMASHCOURT</div>
                    <p class="max-w-xs font-body-md text-body-md text-on-surface-variant">
                        © {{ now()->year }} SMASHCOURT. Engineered for performance, built for fast booking.
                    </p>
                </div>

                <div class="flex flex-wrap justify-center gap-x-8 gap-y-4">
                    <a class="font-body-md text-body-md text-on-surface-variant transition-colors hover:text-secondary-container" href="#benefits">Privacy Policy</a>
                    <a class="font-body-md text-body-md text-on-surface-variant transition-colors hover:text-secondary-container" href="#arenas">Terms of Service</a>
                    <a class="font-body-md text-body-md text-on-surface-variant transition-colors hover:text-secondary-container" href="#cta">Partner with Us</a>
                    <a class="font-body-md text-body-md text-on-surface-variant transition-colors hover:text-secondary-container" href="{{ $secondaryCta }}">Contact Support</a>
                    <a class="font-body-md text-body-md text-on-surface-variant transition-colors hover:text-secondary-container" href="{{ route('public.fields.index') }}">Explore Courts</a>
                </div>
            </div>
        </footer>
    </body>
</html>
