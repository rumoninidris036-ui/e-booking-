@php
    $homeUrl = url('/');
    $courtsUrl = route('public.fields.index');
    $recommendedUrl = url('/#arenas');
    $isHome = request()->is('/');
    $isCourts = request()->routeIs('public.fields.*');
    $authUrl = auth()->check() ? \App\Support\RoleHome::urlFor(auth()->user()) : route('login');
    $authLabel = auth()->check() ? 'Dashboard' : 'Login';
@endphp

<nav x-data="{ open: false }" class="fixed top-0 z-50 w-full border-b border-white/10 bg-surface/80 shadow-sm backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-gutter py-4 md:px-margin-desktop">
        <a href="{{ $homeUrl }}" class="font-headline-md text-headline-md font-black italic tracking-tighter text-secondary-container">
            SMASHCOURT
        </a>

        <div class="hidden items-center gap-8 md:flex">
            <a
                class="{{ $isHome ? 'border-b-2 border-secondary-container pb-1 font-body-md text-body-md font-bold text-secondary-container' : 'font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container' }}"
                href="{{ $homeUrl }}"
            >
                Home
            </a>
            <a
                class="{{ $isCourts ? 'border-b-2 border-secondary-container pb-1 font-body-md text-body-md font-bold text-secondary-container' : 'font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container' }}"
                href="{{ $courtsUrl }}"
            >
                Lapangan
            </a>
            <a
                class="font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container"
                href="{{ $recommendedUrl }}"
            >
                Rekomendasi
            </a>
        </div>

        <div class="flex items-center gap-4">
            <a href="{{ $authUrl }}" class="hidden font-body-md text-body-md text-on-surface transition-colors hover:text-secondary-container md:block">
                {{ $authLabel }}
            </a>

            <a href="{{ $courtsUrl }}" class="rounded-full bg-secondary-container px-6 py-2.5 font-label-bold text-label-bold uppercase text-on-secondary shadow-[0_4px_0_0_#3c4d00] transition-transform hover:-translate-y-px active:translate-y-[2px]">
                Lihat Lapangan
            </a>

            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-on-surface transition-colors hover:bg-white/5 hover:text-secondary-container md:hidden" aria-label="Toggle navigation">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/10 bg-surface/95 md:hidden">
        <div class="mx-auto max-w-7xl px-gutter py-4 md:px-margin-desktop">
            <div class="flex flex-col gap-3">
                <a class="rounded-lg px-4 py-3 font-body-md text-body-md text-on-surface transition-colors hover:bg-white/5 hover:text-secondary-container" href="{{ $homeUrl }}">
                    Home
                </a>
                <a class="rounded-lg px-4 py-3 font-body-md text-body-md text-on-surface transition-colors hover:bg-white/5 hover:text-secondary-container" href="{{ $courtsUrl }}">
                    Lapangan
                </a>
                <a class="rounded-lg px-4 py-3 font-body-md text-body-md text-on-surface transition-colors hover:bg-white/5 hover:text-secondary-container" href="{{ $recommendedUrl }}">
                    Rekomendasi
                </a>
                <a class="rounded-lg px-4 py-3 font-body-md text-body-md text-on-surface transition-colors hover:bg-white/5 hover:text-secondary-container" href="{{ $authUrl }}">
                    {{ $authLabel }}
                </a>
            </div>
        </div>
    </div>
</nav>
