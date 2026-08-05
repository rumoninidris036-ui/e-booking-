@php
    $homeUrl = url('/');
    $courtsUrl = route('public.fields.index');
    $recommendedUrl = url('/#arenas');
    $isHome = request()->is('/');
    $isCourts = request()->routeIs('public.fields.*');
    $authUrl = auth()->check() ? \App\Support\RoleHome::urlFor(auth()->user()) : route('login');
    $authLabel = auth()->check() ? 'Dashboard' : 'Login';
@endphp

<nav id="navbar" x-data="{ open: false }" class="fixed inset-x-0 top-0 z-[1100] border-b border-white/5 bg-surface/95 text-on-surface shadow-[0_1px_0_rgba(255,255,255,0.03)] backdrop-blur-md transition-transform duration-300">
    <div class="mx-auto grid h-[84px] max-w-7xl grid-cols-[1fr_auto_1fr] items-center px-gutter md:px-margin-desktop">
        <a href="{{ $homeUrl }}" class="justify-self-start font-headline-md text-[24px] font-black italic leading-none tracking-[-0.07em] text-secondary-container transition-opacity hover:opacity-85">
            SMASHCOURT
        </a>

        <div class="hidden items-center gap-10 md:flex">
            <a href="{{ $homeUrl }}" @class([
                'border-b-2 border-secondary-container pb-2 font-bold text-secondary-container' => $isHome,
                'pb-2 text-on-surface transition-colors hover:text-secondary-container' => ! $isHome,
            ])>
                Home
            </a>
            <a href="{{ $courtsUrl }}" @class([
                'border-b-2 border-secondary-container pb-2 font-bold text-secondary-container' => $isCourts,
                'pb-2 text-on-surface transition-colors hover:text-secondary-container' => ! $isCourts,
            ])>
                Lapangan
            </a>
            <a href="{{ $recommendedUrl }}" class="pb-2 text-on-surface transition-colors hover:text-secondary-container">
                Rekomendasi
            </a>
        </div>

        <div class="hidden justify-self-end md:block">
            <a href="{{ $authUrl }}" class="rounded-full bg-secondary-container px-7 py-3 font-label-bold text-label-bold uppercase tracking-[0.04em] text-on-secondary shadow-[0_4px_0_#3c4d00] transition hover:-translate-y-px hover:brightness-105 active:translate-y-[2px] active:shadow-[0_2px_0_#3c4d00]">
                {{ $authLabel }}
            </a>
        </div>

        <button @click="open = ! open" :aria-expanded="open.toString()" class="justify-self-end rounded-lg p-2 text-on-surface transition hover:bg-white/5 hover:text-secondary-container md:hidden" aria-label="Buka navigasi">
            <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-cloak x-show="open" style="display: none;" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-cloak x-show="open" x-transition style="display: none;" class="border-t border-white/10 bg-surface md:hidden">
        <div class="mx-auto max-w-7xl px-gutter py-4">
            <div class="flex flex-col gap-1">
                <a href="{{ $homeUrl }}" class="rounded-lg px-4 py-3 transition hover:bg-white/5 hover:text-secondary-container">Home</a>
                <a href="{{ $courtsUrl }}" class="rounded-lg px-4 py-3 transition hover:bg-white/5 hover:text-secondary-container">Lapangan</a>
                <a href="{{ $recommendedUrl }}" class="rounded-lg px-4 py-3 transition hover:bg-white/5 hover:text-secondary-container">Rekomendasi</a>
                <a href="{{ $authUrl }}" class="rounded-lg px-4 py-3 transition hover:bg-white/5 hover:text-secondary-container">{{ $authLabel }}</a>
                <a href="{{ $authUrl }}" class="mt-2 rounded-full bg-secondary-container px-4 py-3 text-center font-label-bold uppercase text-on-secondary">{{ $authLabel }}</a>
            </div>
        </div>
    </div>
</nav>
