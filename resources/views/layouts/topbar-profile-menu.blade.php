@php
    $topbarUser = auth()->user();
    $roleLabel = $topbarUser?->hasRole('admin') ? 'Platform Admin' : 'Owner Venue';
    $initials = strtoupper(substr((string) $topbarUser?->name, 0, 2));
@endphp

@once
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endonce

<div class="relative" x-data="{ open: false }">
    <button type="button" class="flex items-center gap-3 rounded-2xl border border-transparent px-2 py-1.5 text-left transition hover:border-line hover:bg-white focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/10" x-on:click="open = ! open" x-on:keydown.escape.window="open = false">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand font-bold text-white">{{ $initials }}</span>
        <span class="hidden md:block">
            <span class="block text-sm font-bold">{{ $topbarUser?->name }}</span>
            <span class="block text-xs text-slateSoft">{{ $roleLabel }}</span>
        </span>
        <span class="hidden text-slateSoft md:block">⌄</span>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.right x-on:click.outside="open = false" class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-line bg-white shadow-card">
        <div class="border-b border-line px-4 py-3">
            <div class="text-sm font-bold">{{ $topbarUser?->name }}</div>
            <div class="mt-1 truncate text-xs text-slateSoft">{{ $topbarUser?->email }}</div>
        </div>

        <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm font-bold text-ink transition hover:bg-slate-50 hover:text-brand">Profil</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-bold text-rose-700 transition hover:bg-rose-50">
                Log Out
            </button>
        </form>
    </div>
</div>
