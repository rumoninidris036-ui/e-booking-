@php
    $user = auth()->user();
    $panelLabel = $user?->hasRole('admin') ? 'Admin Panel' : 'Owner Panel';
    $sectionLabel = $user?->hasRole('admin') ? 'Control' : 'Management';
    $navItems = [];

    if ($user?->hasRole('admin')) {
        $navItems = [
            ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'D'],
            ['label' => 'Users', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*'), 'icon' => 'U'],
            ['label' => 'Lapangan', 'href' => route('admin.fields.index'), 'active' => request()->routeIs('admin.fields.*'), 'icon' => 'L'],
        ];
    } elseif ($user?->hasRole('owner')) {
        $navItems = [
            ['label' => 'Dashboard', 'href' => route('owner.dashboard'), 'active' => request()->routeIs('owner.dashboard'), 'icon' => 'D'],
            ['label' => 'Lapangan Saya', 'href' => route('owner.fields.index'), 'active' => request()->routeIs('owner.fields.*'), 'icon' => 'L'],
            ['label' => 'Jadwal', 'href' => route('owner.schedules.index'), 'active' => request()->routeIs('owner.schedules.*'), 'icon' => 'J'],
            ['label' => 'Booking', 'href' => route('owner.bookings.index'), 'active' => request()->routeIs('owner.bookings.*'), 'icon' => 'B'],
        ];
    }
@endphp

<aside class="border-b border-white/10 bg-nav text-white lg:sticky lg:top-0 lg:h-screen lg:w-[232px] lg:border-b-0 lg:border-r">
    <div class="flex items-center gap-3 px-4 py-4 lg:h-16 lg:border-b lg:border-white/10 lg:px-5">
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand font-display text-sm font-bold shadow-lg shadow-brand/20">SC</div>
        <div class="min-w-0 flex-1">
            <div class="font-display text-sm font-bold leading-tight">SmashCourt</div>
            <div class="truncate text-[10px] uppercase tracking-[0.22em] text-slate-400">{{ $panelLabel }}</div>
        </div>
        <div class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-300 lg:hidden">{{ $sectionLabel }}</div>
    </div>

    <nav class="overflow-x-auto px-3 pb-3 lg:overflow-visible lg:px-3 lg:py-6">
        <div class="mb-3 hidden px-3 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500 lg:block">{{ $sectionLabel }}</div>
        <div class="flex gap-2 lg:block lg:space-y-1 lg:gap-0">
            @foreach ($navItems as $item)
                <a href="{{ $item['href'] }}" class="group flex min-w-max items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold transition duration-200 lg:min-w-0 {{ $item['active'] ? 'bg-brand text-white shadow-lg shadow-brand/20 ring-1 ring-brand/30' : 'text-slate-300 hover:bg-white/5 hover:text-white hover:translate-x-0.5' }}">
                    <span class="flex h-7 w-7 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-[10px] font-bold transition group-hover:border-white/20 group-hover:bg-white/10">{{ $item['icon'] }}</span>
                    <span class="whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
</aside>
