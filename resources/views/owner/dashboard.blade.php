<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Owner Dashboard</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

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
                            limePop: '#b7f500',
                            nav: '#141b24',
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

        <style>
            .leaflet-container {
                background: #eef4fb;
                font-family: Manrope, sans-serif;
            }
        </style>
    </head>
    <body class="min-h-screen bg-shell font-body text-ink">
        @php
            $summary = $dashboard['summary'];
            $filters = $dashboard['filters'];
            $trends = collect($dashboard['trends']);
            $fieldStats = collect($dashboard['field_statistics']);
            $recentBookings = collect($dashboard['recent_bookings']);
            $recentTransactions = collect($dashboard['recent_transactions']);
            $notifications = collect($dashboard['notifications']);
            $peakHours = collect($dashboard['peak_hours']);
            $mapFields = collect($dashboard['map_fields']);
            $maxFieldBookings = max(1, (int) $fieldStats->max('total_bookings'));

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
                \App\Models\Booking::STATUS_CANCELLED => 'bg-rose-50 text-rose-700 ring-rose-200',
                \App\Models\Booking::STATUS_FINISHED => 'bg-blue-50 text-blue-700 ring-blue-200',
                default => 'bg-amber-50 text-amber-700 ring-amber-200',
            };

            $paymentBadge = fn (?string $status): string => match ($status) {
                \App\Models\Payment::STATUS_SUCCESS => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                \App\Models\Payment::STATUS_FAILED => 'bg-rose-50 text-rose-700 ring-rose-200',
                \App\Models\Payment::STATUS_PENDING => 'bg-amber-50 text-amber-700 ring-amber-200',
                default => 'bg-slate-50 text-slate-600 ring-slate-200',
            };

            $kpis = [
                [
                    'label' => 'Total Booking',
                    'value' => number_format((int) $summary['total_bookings']),
                    'hint' => 'periode terpilih',
                    'tone' => 'text-brand bg-brandSoft',
                    'icon' => 'BK',
                ],
                [
                    'label' => 'Booking Pending',
                    'value' => number_format((int) $summary['pending_bookings']),
                    'hint' => 'menunggu payment',
                    'tone' => 'text-amber-700 bg-amber-50',
                    'icon' => 'PN',
                ],
                [
                    'label' => 'Booking Paid',
                    'value' => number_format((int) $summary['paid_bookings']),
                    'hint' => 'slot aman',
                    'tone' => 'text-emerald-700 bg-emerald-50',
                    'icon' => 'PD',
                ],
                [
                    'label' => 'Total Revenue',
                    'value' => $compactRupiah((float) $summary['total_revenue']),
                    'hint' => number_format((int) $summary['successful_transactions']).' transaksi sukses',
                    'tone' => 'text-lime-700 bg-lime-50',
                    'icon' => 'RV',
                ],
                [
                    'label' => 'Active Fields',
                    'value' => number_format((int) $summary['active_fields']),
                    'hint' => number_format((int) $summary['total_fields']).' total lapangan',
                    'tone' => 'text-sky-700 bg-sky-50',
                    'icon' => 'FD',
                ],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            @include("layouts.role-sidebar")

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <form action="{{ route('owner.dashboard') }}" method="GET" class="hidden w-full max-w-md md:block">
                            <input type="hidden" name="period" value="{{ $filters['period'] }}">
                            @if ($filters['field_id'])
                                <input type="hidden" name="field_id" value="{{ $filters['field_id'] }}">
                            @endif
                            <label class="relative block">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slateSoft">/</span>
                                <input name="search" type="search" class="h-10 w-full rounded-xl border border-line bg-slate-50 pl-10 pr-4 text-sm outline-none transition focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10" placeholder="Search booking, customer, lapangan...">
                            </label>
                        </form>

                        <a href="{{ route('owner.dashboard') }}" class="font-display text-lg font-bold text-brand md:hidden">SmashCourt</a>

                        <div class="ml-auto flex items-center gap-4">
                            <a href="{{ route('public.fields.index') }}" class="hidden rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand md:inline-flex">View Public</a>
                            <div class="relative hidden h-10 w-10 items-center justify-center rounded-full border border-line bg-white md:flex">
                                <span class="text-lg">!</span>
                                @if ((int) $summary['pending_bookings'] > 0)
                                    <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white"></span>
                                @endif
                            </div>
                            <div class="border-l border-line pl-4">
                                @include('layouts.topbar-profile-menu')
                            </div>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 md:px-6">
                    <section class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand">Owner Workspace</p>
                            <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-ink md:text-4xl">Dashboard Owner</h1>
                            <p class="mt-2 text-sm text-slateSoft">Pantau booking, pendapatan, performa lapangan, dan lokasi venue kamu secara real-time.</p>
                        </div>

                        <form action="{{ route('owner.dashboard') }}" method="GET" class="grid gap-3 rounded-2xl border border-line bg-white p-3 shadow-sm sm:grid-cols-2 xl:grid-cols-[150px_150px_150px_auto]">
                            <select name="period" class="rounded-xl border-line text-sm focus:border-brand focus:ring-brand/20">
                                <option value="today" @selected($filters['period'] === 'today')>Hari ini</option>
                                <option value="7_days" @selected($filters['period'] === '7_days')>7 hari</option>
                                <option value="month" @selected($filters['period'] === 'month')>Bulan ini</option>
                                <option value="custom" @selected($filters['period'] === 'custom')>Custom</option>
                            </select>
                            <input name="date_from" type="date" value="{{ $filters['date_from'] }}" class="rounded-xl border-line text-sm focus:border-brand focus:ring-brand/20">
                            <input name="date_to" type="date" value="{{ $filters['date_to'] }}" class="rounded-xl border-line text-sm focus:border-brand focus:ring-brand/20">
                            <button type="submit" class="rounded-xl bg-brand px-5 py-2 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-blue-600">Apply Filter</button>
                        </form>
                    </section>

                    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ($kpis as $kpi)
                            <article class="rounded-3xl border border-line bg-panel p-5 shadow-card">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">{{ $kpi['label'] }}</p>
                                        <p class="mt-3 font-display text-3xl font-bold">{{ $kpi['value'] }}</p>
                                    </div>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-xs font-extrabold {{ $kpi['tone'] }}">{{ $kpi['icon'] }}</div>
                                </div>
                                <div class="mt-4 flex items-center gap-2 text-xs text-slateSoft">
                                    <span class="rounded-full bg-emerald-50 px-2 py-1 font-bold text-emerald-700">live</span>
                                    <span>{{ $kpi['hint'] }}</span>
                                </div>
                            </article>
                        @endforeach
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="flex items-start justify-between border-b border-line px-5 py-4">
                                <div>
                                    <h2 class="font-display text-lg font-bold">Booking & Revenue Trend</h2>
                                    <p class="text-xs text-slateSoft">{{ $filters['date_from'] }} sampai {{ $filters['date_to'] }}</p>
                                </div>
                                <span class="rounded-full bg-brandSoft px-3 py-1 text-xs font-bold text-brand">Last {{ $trends->count() }} days</span>
                            </div>
                            <div class="h-[320px] p-5">
                                <canvas id="bookingRevenueChart"></canvas>
                            </div>
                        </article>

                        <article id="schedule" class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="border-b border-line px-5 py-4">
                                <h2 class="font-display text-lg font-bold">Peak Hours</h2>
                                <p class="text-xs text-slateSoft">Jam paling ramai berdasarkan booking.</p>
                            </div>
                            <div class="space-y-4 p-5">
                                @forelse ($peakHours as $slot)
                                    <div class="rounded-2xl border border-line bg-slate-50 p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="font-bold">{{ $slot['start_time'] }} - {{ $slot['end_time'] }}</div>
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-brand ring-1 ring-line">{{ $slot['total_bookings'] }} booking</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-slateSoft">Belum ada data peak hour.</div>
                                @endforelse
                            </div>
                        </article>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="border-b border-line px-5 py-4">
                                <h2 class="font-display text-lg font-bold">Popular Courts</h2>
                                <p class="text-xs text-slateSoft">Ranking lapangan berdasarkan booking.</p>
                            </div>
                            <div class="space-y-4 p-5">
                                @forelse ($fieldStats->take(6) as $field)
                                    <div>
                                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                            <div class="font-bold">{{ $field['name'] }}</div>
                                            <div class="text-slateSoft">{{ $field['total_bookings'] }} booking</div>
                                        </div>
                                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-brand" style="width: {{ min(100, ((int) $field['total_bookings'] / $maxFieldBookings) * 100) }}%"></div>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between text-xs text-slateSoft">
                                            <span>{{ $field['is_active'] ? 'Active' : 'Nonactive' }}</span>
                                            <span>{{ $rupiah((float) $field['total_revenue']) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-slateSoft">Belum ada lapangan.</div>
                                @endforelse
                            </div>
                        </article>

                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="border-b border-line px-5 py-4">
                                <h2 class="font-display text-lg font-bold">Map & Location Overview</h2>
                                <p class="text-xs text-slateSoft">Marker lapangan owner berbasis OpenStreetMap.</p>
                            </div>
                            <div class="p-5">
                                <div id="ownerMap" class="h-[300px] overflow-hidden rounded-2xl border border-line"></div>
                            </div>
                        </article>
                    </section>

                    <section class="grid gap-6 2xl:grid-cols-[1.25fr_0.75fr]">
                        <article class="rounded-3xl border border-line bg-panel shadow-card">
                            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                                <div>
                                    <h2 class="font-display text-lg font-bold">Recent Bookings</h2>
                                    <p class="text-xs text-slateSoft">Booking terbaru untuk lapangan kamu.</p>
                                </div>
                                <a href="{{ route('owner.bookings.index') }}" class="text-sm font-bold text-brand">View all</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-line text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slateSoft">
                                        <tr>
                                            <th class="px-5 py-4">Booking</th>
                                            <th class="px-5 py-4">Customer</th>
                                            <th class="px-5 py-4">Field</th>
                                            <th class="px-5 py-4">Status</th>
                                            <th class="px-5 py-4 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        @forelse ($recentBookings as $booking)
                                            <tr class="hover:bg-slate-50/70">
                                                <td class="px-5 py-4">
                                                    <div class="font-bold">{{ $booking['booking_code'] }}</div>
                                                    <div class="text-xs text-slateSoft">{{ $booking['booking_date'] }}, {{ $booking['start_time'] }}</div>
                                                </td>
                                                <td class="px-5 py-4">{{ $booking['customer_name'] }}</td>
                                                <td class="px-5 py-4 text-slateSoft">{{ $booking['field_name'] }}</td>
                                                <td class="px-5 py-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $bookingBadge($booking['status']) }}">{{ $booking['status'] }}</span>
                                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $paymentBadge($booking['payment_status']) }}">{{ $booking['payment_status'] ?? 'unpaid' }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4 text-right font-bold">{{ $rupiah((float) $booking['amount']) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-5 py-10 text-center text-sm text-slateSoft">Belum ada booking pada periode ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <aside class="space-y-6">
                            <article id="transactions" class="rounded-3xl border border-line bg-panel shadow-card">
                                <div class="border-b border-line px-5 py-4">
                                    <h2 class="font-display text-lg font-bold">Recent Transactions</h2>
                                    <p class="text-xs text-slateSoft">Payment terbaru dan invoice.</p>
                                </div>
                                <div class="divide-y divide-line">
                                    @forelse ($recentTransactions as $transaction)
                                        <div class="px-5 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-bold">{{ $transaction['order_id'] }}</div>
                                                    <div class="text-xs text-slateSoft">{{ $transaction['customer_name'] }} - {{ $transaction['field_name'] }}</div>
                                                </div>
                                                <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $paymentBadge($transaction['status']) }}">{{ $transaction['status'] }}</span>
                                            </div>
                                            <div class="mt-3 flex items-center justify-between text-sm">
                                                <span class="font-bold">{{ $rupiah((float) $transaction['amount']) }}</span>
                                                @if ($transaction['invoice_download_url'])
                                                    <a href="{{ $transaction['invoice_download_url'] }}" class="font-bold text-brand">Invoice</a>
                                                @else
                                                    <span class="text-xs text-slateSoft">No invoice</span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-5 py-10 text-center text-sm text-slateSoft">Belum ada transaksi.</div>
                                    @endforelse
                                </div>
                            </article>

                            <article id="notifications" class="rounded-3xl border border-line bg-panel shadow-card">
                                <div class="border-b border-line px-5 py-4">
                                    <h2 class="font-display text-lg font-bold">Notifications</h2>
                                    <p class="text-xs text-slateSoft">Aktivitas terbaru.</p>
                                </div>
                                <div class="divide-y divide-line">
                                    @forelse ($notifications as $notification)
                                        <div class="px-5 py-4">
                                            <div class="font-bold">{{ $notification['title'] }}</div>
                                            <div class="mt-1 text-sm text-slateSoft">{{ $notification['message'] }}</div>
                                            <div class="mt-2 text-xs text-slateSoft">{{ $notification['time'] }}</div>
                                        </div>
                                    @empty
                                        <div class="px-5 py-10 text-center text-sm text-slateSoft">Belum ada notifikasi.</div>
                                    @endforelse
                                </div>
                            </article>
                        </aside>
                    </section>
                </main>
            </div>
        </div>

        <script>
            const trends = @json($trends->values());
            const mapFields = @json($mapFields->values());

            const currencyFormatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            });

            const chartCanvas = document.getElementById('bookingRevenueChart');

            if (chartCanvas && window.Chart) {
                new Chart(chartCanvas, {
                    type: 'line',
                    data: {
                        labels: trends.map((item) => item.label),
                        datasets: [
                            {
                                label: 'Bookings',
                                data: trends.map((item) => item.total_bookings),
                                borderColor: '#0f7ae5',
                                backgroundColor: 'rgba(15, 122, 229, 0.14)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Revenue',
                                data: trends.map((item) => item.total_revenue),
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22, 163, 74, 0.10)',
                                fill: true,
                                tension: 0.4,
                                yAxisID: 'revenue',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label(context) {
                                        if (context.dataset.label === 'Revenue') {
                                            return `Revenue: ${currencyFormatter.format(context.raw)}`;
                                        }

                                        return `Bookings: ${context.raw}`;
                                    },
                                },
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#e5edf6',
                                },
                                ticks: {
                                    precision: 0,
                                },
                            },
                            revenue: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                                ticks: {
                                    callback(value) {
                                        return currencyFormatter.format(value).replace(',00', '');
                                    },
                                },
                            },
                            x: {
                                grid: {
                                    color: '#eef3f8',
                                },
                            },
                        },
                    },
                });
            }

            const mapElement = document.getElementById('ownerMap');

            if (mapElement && window.L) {
                const defaultCenter = [-3.6954, 128.1814];
                const firstField = mapFields[0];
                const map = L.map(mapElement, {
                    scrollWheelZoom: false,
                }).setView(firstField ? [firstField.latitude, firstField.longitude] : defaultCenter, firstField ? 13 : 12);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                const bounds = [];

                mapFields.forEach((field) => {
                    const marker = L.circleMarker([field.latitude, field.longitude], {
                        radius: 9,
                        color: field.status === 'active' ? '#16a34a' : '#dc2626',
                        fillColor: field.status === 'active' ? '#22c55e' : '#ef4444',
                        fillOpacity: 0.85,
                        weight: 2,
                    }).addTo(map);

                    marker.bindPopup(`
                        <strong>${field.name}</strong><br>
                        ${field.total_bookings} booking<br>
                        Revenue ${currencyFormatter.format(field.total_revenue)}<br>
                        Status: ${field.status}
                    `);

                    bounds.push([field.latitude, field.longitude]);
                });

                if (bounds.length > 1) {
                    map.fitBounds(bounds, {
                        padding: [28, 28],
                    });
                }

                if (bounds.length === 0) {
                    L.marker(defaultCenter).addTo(map).bindPopup('Belum ada koordinat lapangan.').openPopup();
                }
            }
        </script>
    </body>
</html>
