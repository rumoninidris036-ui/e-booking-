<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Booking Owner</title>

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
            $summary = $summary ?? [
                'total_bookings' => 0,
                'pending_bookings' => 0,
                'paid_bookings' => 0,
                'finished_bookings' => 0,
                'cancelled_bookings' => 0,
                'total_revenue' => 0,
            ];
            $filters = $filters ?? [
                'search' => '',
                'status' => 'all',
                'field_id' => null,
                'date' => '',
            ];
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
            $navItems = [
                ['label' => 'Dashboard', 'href' => route('owner.dashboard'), 'active' => false, 'icon' => 'D'],
                ['label' => 'Lapangan Saya', 'href' => route('owner.fields.index'), 'active' => false, 'icon' => 'L'],
                ['label' => 'Jadwal', 'href' => route('owner.schedules.index'), 'active' => false, 'icon' => 'J'],
                ['label' => 'Booking', 'href' => route('owner.bookings.index'), 'active' => true, 'icon' => 'B'],
            ];
            $kpis = [
                ['label' => 'Total Booking', 'value' => number_format((int) $summary['total_bookings']), 'hint' => 'semua status', 'tone' => 'text-brand bg-brandSoft', 'icon' => 'BK'],
                ['label' => 'Pending', 'value' => number_format((int) $summary['pending_bookings']), 'hint' => 'menunggu payment', 'tone' => 'text-amber-700 bg-amber-50', 'icon' => 'PN'],
                ['label' => 'Paid', 'value' => number_format((int) $summary['paid_bookings']), 'hint' => 'siap dimainkan', 'tone' => 'text-emerald-700 bg-emerald-50', 'icon' => 'PD'],
                ['label' => 'Finished', 'value' => number_format((int) $summary['finished_bookings']), 'hint' => 'selesai', 'tone' => 'text-blue-700 bg-blue-50', 'icon' => 'FN'],
                ['label' => 'Revenue', 'value' => $compactRupiah((float) $summary['total_revenue']), 'hint' => 'payment sukses', 'tone' => 'text-lime-700 bg-lime-50', 'icon' => 'RV'],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            <aside class="hidden border-r border-white/10 bg-nav text-white lg:block">
                <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-brand font-display text-sm font-bold">SC</div>
                    <div>
                        <div class="font-display text-sm font-bold">SmashCourt</div>
                        <div class="text-[10px] uppercase tracking-[0.22em] text-slate-400">Owner Panel</div>
                    </div>
                </div>

                <nav class="px-3 py-6">
                    <div class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Management</div>
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

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <div>
                            <p class="hidden text-xs font-bold uppercase tracking-[0.22em] text-brand md:block">Booking Operations</p>
                            <h1 class="font-display text-xl font-bold md:text-2xl">Daftar Booking</h1>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <a href="{{ route('owner.fields.index') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Lapangan</a>
                            <a href="{{ route('owner.dashboard') }}" class="rounded-xl bg-brand px-4 py-2 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-blue-600">Dashboard</a>
                            <div class="hidden items-center gap-3 border-l border-line pl-4 md:flex">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand font-bold text-white">{{ strtoupper(substr($owner->name, 0, 2)) }}</div>
                                <div>
                                    <div class="text-sm font-bold">{{ $owner->name }}</div>
                                    <div class="text-xs text-slateSoft">Owner Venue</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 md:px-6">
                    @if (session('status'))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (($errors ?? null)?->any())
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

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

                    <section class="rounded-3xl border border-line bg-panel shadow-card">
                        <div class="flex flex-col gap-4 border-b border-line px-5 py-5 xl:flex-row xl:items-end xl:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.22em] text-brand">Booking List</p>
                                <h2 class="mt-1 font-display text-2xl font-bold">Booking Masuk</h2>
                                <p class="mt-2 text-sm text-slateSoft">{{ $bookings->total() }} booking cocok dengan filter saat ini</p>
                            </div>
                            <form action="{{ route('owner.bookings.index') }}" method="GET" class="grid gap-3 md:grid-cols-[minmax(220px,1fr)_150px_170px_150px_auto] xl:w-[860px]">
                                <input name="search" type="search" value="{{ $filters['search'] }}" class="rounded-xl border-line bg-slate-50 px-4 py-2 text-sm focus:border-brand focus:bg-white focus:ring-brand/20" placeholder="Kode, customer, kontak">
                                <select name="status" class="rounded-xl border-line bg-slate-50 text-sm focus:border-brand focus:ring-brand/20">
                                    <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
                                    @foreach (\App\Models\Booking::statuses() as $status)
                                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <select name="field_id" class="rounded-xl border-line bg-slate-50 text-sm focus:border-brand focus:ring-brand/20">
                                    <option value="">Semua lapangan</option>
                                    @foreach ($fields as $field)
                                        <option value="{{ $field->id }}" @selected((string) $filters['field_id'] === (string) $field->id)>{{ $field->name }}</option>
                                    @endforeach
                                </select>
                                <input name="date" type="date" value="{{ $filters['date'] }}" class="rounded-xl border-line bg-slate-50 px-4 py-2 text-sm focus:border-brand focus:bg-white focus:ring-brand/20">
                                <button type="submit" class="rounded-xl bg-ink px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700">Filter</button>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-line text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.16em] text-slateSoft">
                                    <tr>
                                        <th class="px-5 py-4">Booking</th>
                                        <th class="px-5 py-4">Customer</th>
                                        <th class="px-5 py-4">Lapangan</th>
                                        <th class="px-5 py-4">Status</th>
                                        <th class="px-5 py-4 text-right">Amount</th>
                                        <th class="px-5 py-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line bg-white">
                                    @forelse ($bookings as $booking)
                                        @php
                                            $payment = $booking->payments->first();
                                            $customerName = $booking->customer_name ?? $booking->user?->name ?? 'Guest Customer';
                                            $customerContact = $booking->customer_contact ?? $booking->customer_email ?? $booking->user?->email ?? '-';
                                            $canMarkPaid = $booking->status === \App\Models\Booking::STATUS_PENDING;
                                            $canFinish = $booking->status === \App\Models\Booking::STATUS_PAID;
                                            $canCancel = in_array($booking->status, [\App\Models\Booking::STATUS_PENDING, \App\Models\Booking::STATUS_PAID], true);
                                        @endphp
                                        <tr id="booking-{{ $booking->id }}" class="{{ (string) request('focus') === (string) $booking->id ? 'bg-brandSoft/40' : '' }}">
                                            <td class="px-5 py-4 align-top">
                                                <div class="font-bold">{{ $booking->booking_code }}</div>
                                                <div class="mt-1 text-xs text-slateSoft">{{ $booking->booking_date?->format('d M Y') }} · {{ substr((string) $booking->start_time, 0, 5) }}-{{ substr((string) $booking->end_time, 0, 5) }}</div>
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <div class="font-bold">{{ $customerName }}</div>
                                                <div class="mt-1 text-xs text-slateSoft">{{ $customerContact }}</div>
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <div class="font-bold">{{ $booking->field?->name ?? '-' }}</div>
                                                <div class="mt-1 text-xs text-slateSoft">{{ $booking->field?->slug ?? '-' }}</div>
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <div class="flex flex-wrap gap-2">
                                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $bookingBadge($booking->status) }}">{{ $booking->status }}</span>
                                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $paymentBadge($payment?->status) }}">{{ $payment?->status ?? 'unpaid' }}</span>
                                                </div>
                                                @if ($payment?->invoice_number)
                                                    <div class="mt-2 text-xs font-semibold text-slateSoft">{{ $payment->invoice_number }}</div>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-right align-top font-bold">
                                                {{ $rupiah((float) ($payment?->amount ?? $booking->price_per_hour)) }}
                                            </td>
                                            <td class="min-w-[260px] px-5 py-4 align-top">
                                                <div class="flex flex-wrap gap-2">
                                                    @if ($canMarkPaid)
                                                        <form method="POST" action="{{ route('owner.bookings.update-status', $booking) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="{{ \App\Models\Booking::STATUS_PAID }}">
                                                            <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">Mark Paid</button>
                                                        </form>
                                                    @endif
                                                    @if ($canFinish)
                                                        <form method="POST" action="{{ route('owner.bookings.update-status', $booking) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="{{ \App\Models\Booking::STATUS_FINISHED }}">
                                                            <button type="submit" class="rounded-xl bg-brand px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-600">Finish</button>
                                                        </form>
                                                    @endif
                                                    @if ($canCancel)
                                                        <form method="POST" action="{{ route('owner.bookings.update-status', $booking) }}" class="flex min-w-[210px] gap-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="{{ \App\Models\Booking::STATUS_CANCELLED }}">
                                                            <input name="cancellation_reason" type="text" class="min-w-0 flex-1 rounded-xl border-line bg-slate-50 px-3 py-2 text-xs focus:border-rose-500 focus:ring-rose-500/20" placeholder="Alasan cancel">
                                                            <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100">Cancel</button>
                                                        </form>
                                                    @endif
                                                    @unless ($canMarkPaid || $canFinish || $canCancel)
                                                        <span class="rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slateSoft ring-1 ring-line">Final</span>
                                                    @endunless
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-12 text-center">
                                                <h3 class="font-display text-2xl font-bold">Belum ada booking</h3>
                                                <p class="mt-2 text-sm text-slateSoft">Booking akan muncul saat customer memilih slot lapangan kamu.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($bookings->hasPages())
                            <div class="border-t border-line px-5 py-4">
                                {{ $bookings->links() }}
                            </div>
                        @endif
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
