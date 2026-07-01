<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Pengguna Admin</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

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
    </head>
    <body class="min-h-screen bg-shell font-body text-ink">
        @php
            $rupiah = fn (float|int $amount): string => 'Rp '.number_format((float) $amount, 0, ',', '.');
            $kpis = [
                ['label' => 'Total Owner', 'value' => number_format((int) $summary['total_owners']), 'hint' => 'akun terdaftar', 'tone' => 'text-brand bg-brandSoft', 'icon' => 'OW'],
                ['label' => 'Daftar Hari Ini', 'value' => number_format((int) $summary['registered_today']), 'hint' => now()->format('d M Y'), 'tone' => 'text-emerald-700 bg-emerald-50', 'icon' => 'TD'],
                ['label' => 'Punya Lapangan', 'value' => number_format((int) $summary['with_fields']), 'hint' => 'owner aktif setup', 'tone' => 'text-sky-700 bg-sky-50', 'icon' => 'LP'],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            @include("layouts.role-sidebar")

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <div>
                            <p class="hidden text-xs font-bold uppercase tracking-[0.22em] text-brand md:block">User Control</p>
                            <h1 class="font-display text-xl font-bold md:text-2xl">Pengguna Owner</h1>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <a href="{{ route('admin.fields.index') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Lapangan</a>
                            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Dashboard</a>
                            <div class="border-l border-line pl-4">
                                @include('layouts.topbar-profile-menu')
                            </div>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 md:px-6">
                    <section class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand">Registered Owners</p>
                            <h2 class="mt-2 font-display text-3xl font-bold tracking-tight md:text-4xl">Kontrol user owner yang mendaftar</h2>
                            <p class="mt-2 text-sm text-slateSoft">Semua akun dari halaman register otomatis masuk sebagai owner.</p>
                        </div>

                        <form action="{{ route('admin.users.index') }}" method="GET" class="grid gap-3 rounded-2xl border border-line bg-white p-3 shadow-sm sm:grid-cols-[220px_150px_auto]">
                            <input name="search" type="search" value="{{ $filters['search'] }}" class="rounded-xl border-line bg-slate-50 px-4 py-2 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="Cari nama/email">
                            <select name="sort" class="rounded-xl border-line bg-slate-50 text-sm focus:border-brand focus:ring-brand/20">
                                <option value="latest" @selected($filters['sort'] === 'latest')>Terbaru</option>
                                <option value="name" @selected($filters['sort'] === 'name')>Nama A-Z</option>
                                <option value="fields" @selected($filters['sort'] === 'fields')>Lapangan terbanyak</option>
                            </select>
                            <button type="submit" class="rounded-xl bg-ink px-5 py-2 text-sm font-bold text-white transition hover:bg-slate-700">Filter</button>
                        </form>
                    </section>

                    <section class="grid gap-4 md:grid-cols-3">
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

                    <section class="rounded-3xl border border-line bg-panel shadow-card">
                        <div class="flex items-center justify-between border-b border-line px-5 py-5">
                            <div>
                                <h2 class="font-display text-2xl font-bold">Daftar Owner</h2>
                                <p class="mt-1 text-sm text-slateSoft">{{ $owners->total() }} owner cocok dengan filter saat ini</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-line text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slateSoft">
                                    <tr>
                                        <th class="px-5 py-4">Owner</th>
                                        <th class="px-5 py-4">Tanggal Daftar</th>
                                        <th class="px-5 py-4 text-right">Lapangan</th>
                                        <th class="px-5 py-4 text-right">Booking</th>
                                        <th class="px-5 py-4 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @forelse ($owners as $owner)
                                        @php
                                            $ownerMetrics = $metrics->get($owner->id);
                                        @endphp
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-5 py-4">
                                                <div class="font-bold">{{ $owner->name }}</div>
                                                <div class="text-xs text-slateSoft">{{ $owner->email }}</div>
                                            </td>
                                            <td class="px-5 py-4 text-slateSoft">{{ $owner->created_at?->format('d M Y H:i') }}</td>
                                            <td class="px-5 py-4 text-right font-bold">{{ number_format((int) $owner->owned_fields_count) }}</td>
                                            <td class="px-5 py-4 text-right font-bold">{{ number_format((int) ($ownerMetrics?->total_bookings ?? 0)) }}</td>
                                            <td class="px-5 py-4 text-right font-bold">{{ $rupiah((float) ($ownerMetrics?->total_revenue ?? 0)) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slateSoft">Belum ada owner yang cocok.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($owners->hasPages())
                            <div class="border-t border-line px-5 py-4">
                                {{ $owners->links() }}
                            </div>
                        @endif
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
