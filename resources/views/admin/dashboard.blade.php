<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Admin Dashboard</title>

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
            $summary = $dashboard['summary'];
            $fields = collect($dashboard['field_monitoring']['recent_fields']);
            $bookings = collect($dashboard['booking_monitoring']['recent_bookings']);
            $recentOwners = collect($dashboard['owner_monitoring']['recent_owners']);
            $topOwners = collect($dashboard['owner_monitoring']['top_owners']);
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
            $bookingBadge = fn (?string $status): string => match ($status) {
                \App\Models\Booking::STATUS_PAID => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                \App\Models\Booking::STATUS_EXPIRED => 'bg-rose-50 text-rose-700 ring-rose-200',
                \App\Models\Booking::STATUS_CANCELLED => 'bg-rose-50 text-rose-700 ring-rose-200',
                \App\Models\Booking::STATUS_FINISHED => 'bg-blue-50 text-blue-700 ring-blue-200',
                default => 'bg-amber-50 text-amber-700 ring-amber-200',
            };
            $kpis = [
                ['label' => 'Owner Terdaftar', 'value' => number_format((int) $summary['total_owners']), 'hint' => 'user register', 'tone' => 'text-brand bg-brandSoft', 'icon' => 'OW'],
                ['label' => 'Total Lapangan', 'value' => number_format((int) $dashboard['field_monitoring']['total_fields']), 'hint' => $dashboard['field_monitoring']['active_fields'].' aktif', 'tone' => 'text-sky-700 bg-sky-50', 'icon' => 'LP'],
                ['label' => 'Total Booking', 'value' => number_format((int) $dashboard['booking_monitoring']['total_bookings']), 'hint' => $dashboard['booking_monitoring']['pending_bookings'].' menunggu', 'tone' => 'text-amber-700 bg-amber-50', 'icon' => 'BK'],
                ['label' => 'Transaksi Sukses', 'value' => number_format((int) $summary['successful_transactions']), 'hint' => 'pembayaran sukses', 'tone' => 'text-emerald-700 bg-emerald-50', 'icon' => 'OK'],
                ['label' => 'Revenue', 'value' => $compactRupiah((float) $summary['total_transaction_amount']), 'hint' => 'semua owner', 'tone' => 'text-lime-700 bg-lime-50', 'icon' => 'RV'],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            @include("layouts.role-sidebar")

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <div>
                            <p class="hidden text-xs font-bold uppercase tracking-[0.22em] text-brand md:block">Ruang Admin</p>
                            <h1 class="font-display text-xl font-bold md:text-2xl">Dashboard Admin</h1>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <a href="{{ route('public.fields.index') }}" class="hidden rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand md:inline-flex">Lihat Publik</a>
                            <a href="{{ route('admin.fields.index') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Lapangan</a>
                            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Pengguna</a>
                            <div class="border-l border-line pl-4">
                                @include('layouts.topbar-profile-menu')
                            </div>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 md:px-6">
                    <section>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand">Ringkasan Platform</p>
                        <h2 class="mt-2 font-display text-3xl font-bold tracking-tight md:text-4xl">Monitor owner, lapangan, booking, dan pembayaran</h2>
                        <p class="mt-2 text-sm text-slateSoft">User yang daftar masuk sebagai owner dan bisa dipantau dari menu pengguna.</p>
                    </section>

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

                    <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                                <div>
                                    <h2 class="font-display text-lg font-bold">Owner Terbaru</h2>
                                    <p class="text-xs text-slateSoft">Akun owner yang baru mendaftar.</p>
                                </div>
                                <a href="{{ route('admin.users.index') }}" class="text-sm font-bold text-brand">Kelola</a>
                            </div>
                            <div class="divide-y divide-line">
                                @forelse ($recentOwners as $owner)
                                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                                        <div>
                                            <div class="font-bold">{{ $owner->name }}</div>
                                            <div class="text-xs text-slateSoft">{{ $owner->email }}</div>
                                        </div>
                                        <span class="rounded-full bg-brandSoft px-3 py-1 text-xs font-bold text-brand">{{ $owner->owned_fields_count }} lapangan</span>
                                    </div>
                                @empty
                                    <div class="px-5 py-10 text-center text-sm text-slateSoft">Belum ada owner terdaftar.</div>
                                @endforelse
                            </div>
                        </article>

                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="border-b border-line px-5 py-4">
                                <h2 class="font-display text-lg font-bold">Owner Teratas</h2>
                                <p class="text-xs text-slateSoft">Urutan berdasarkan revenue sukses.</p>
                            </div>
                            <div class="space-y-4 p-5">
                                @forelse ($topOwners as $owner)
                                    <div>
                                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                            <div class="font-bold">{{ $owner->name }}</div>
                                            <div class="text-slateSoft">{{ $owner->total_bookings }} booking</div>
                                        </div>
                                        <div class="flex items-center justify-between text-xs text-slateSoft">
                                            <span>{{ $owner->total_fields }} lapangan</span>
                                            <span class="font-bold text-ink">{{ $rupiah((float) $owner->total_revenue) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-slateSoft">Belum ada data owner.</div>
                                @endforelse
                            </div>
                        </article>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-2">
                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="border-b border-line px-5 py-4">
                                <h2 class="font-display text-lg font-bold">Lapangan Terbaru</h2>
                                <p class="text-xs text-slateSoft">Venue yang baru dibuat owner.</p>
                            </div>
                            <div class="divide-y divide-line">
                                @forelse ($fields as $field)
                                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                                        <div>
                                            <div class="font-bold">{{ $field->name }}</div>
                                            <div class="text-xs text-slateSoft">{{ $field->owner?->name ?? 'Tanpa owner' }}</div>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $field->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' }}">{{ $field->is_active ? 'active' : 'nonactive' }}</span>
                                    </div>
                                @empty
                                    <div class="px-5 py-10 text-center text-sm text-slateSoft">Belum ada lapangan.</div>
                                @endforelse
                            </div>
                        </article>

                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="border-b border-line px-5 py-4">
                                <h2 class="font-display text-lg font-bold">Booking Terbaru</h2>
                                <p class="text-xs text-slateSoft">Aktivitas booking seluruh platform.</p>
                            </div>
                            <div class="divide-y divide-line">
                                @forelse ($bookings as $booking)
                                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                                        <div>
                                            <div class="font-bold">{{ $booking->booking_code }}</div>
                                            <div class="text-xs text-slateSoft">{{ $booking->field?->name }} · {{ $booking->booking_date?->format('d M Y') }} {{ substr((string) $booking->start_time, 0, 5) }}</div>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $bookingBadge($booking->status) }}">{{ $booking->status }}</span>
                                    </div>
                                @empty
                                    <div class="px-5 py-10 text-center text-sm text-slateSoft">Belum ada booking.</div>
                                @endforelse
                            </div>
                        </article>
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
