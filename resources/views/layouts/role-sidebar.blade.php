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

<aside class="hidden border-r border-white/10 bg-nav text-white lg:block">
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-brand font-display text-sm font-bold">SC</div>
        <div>
            <div class="font-display text-sm font-bold">SmashCourt</div>
            <div class="text-[10px] uppercase tracking-[0.22em] text-slate-400">{{ $panelLabel }}</div>
        </div>
    </div>

    <nav class="px-3 py-6">
        <div class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">{{ $sectionLabel }}</div>
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
