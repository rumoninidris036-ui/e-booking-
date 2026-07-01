<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Jadwal Owner</title>

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
            $formatTime = function (?string $time, string $fallback): string {
                $value = (string) ($time ?: $fallback);

                return preg_match('/^\d{2}:\d{2}/', $value) === 1 ? substr($value, 0, 5) : $fallback;
            };
            $openTime = $formatTime($selectedField?->open_time, \App\Services\Booking\FieldScheduleService::DEFAULT_OPEN_TIME);
            $closeTime = $formatTime($selectedField?->close_time, \App\Services\Booking\FieldScheduleService::DEFAULT_CLOSE_TIME);
            $slotDurationMinutes = (int) ($selectedField?->slot_duration_minutes ?? \App\Services\Booking\FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES);
            $kpis = [
                ['label' => 'Total Slot', 'value' => number_format((int) $summary['total_slots']), 'hint' => $openTime.'-'.$closeTime, 'tone' => 'text-brand bg-brandSoft', 'icon' => 'SL'],
                ['label' => 'Tersedia', 'value' => number_format((int) $summary['available_slots']), 'hint' => 'bisa dibooking', 'tone' => 'text-emerald-700 bg-emerald-50', 'icon' => 'OK'],
                ['label' => 'Terisi', 'value' => number_format((int) $summary['booked_slots']), 'hint' => 'punya booking', 'tone' => 'text-amber-700 bg-amber-50', 'icon' => 'BK'],
                ['label' => 'Pending', 'value' => number_format((int) $summary['pending_bookings']), 'hint' => 'menunggu pembayaran', 'tone' => 'text-orange-700 bg-orange-50', 'icon' => 'PN'],
                ['label' => 'Lunas', 'value' => number_format((int) $summary['paid_bookings']), 'hint' => 'slot aman', 'tone' => 'text-sky-700 bg-sky-50', 'icon' => 'PD'],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[232px_1fr]">
            @include("layouts.role-sidebar")

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur-xl">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 md:px-6">
                        <div>
                            <p class="hidden text-xs font-bold uppercase tracking-[0.22em] text-brand md:block">Jadwal Lapangan</p>
                            <h1 class="font-display text-xl font-bold md:text-2xl">Jadwal Lapangan</h1>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <a href="{{ route('owner.bookings.index') }}" class="rounded-xl border border-line px-4 py-2 text-sm font-bold text-ink transition hover:border-brand hover:text-brand">Booking</a>
                            <a href="{{ route('owner.dashboard') }}" class="rounded-xl bg-brand px-4 py-2 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-blue-600">Dashboard</a>
                            <div class="border-l border-line pl-4">
                                @include('layouts.topbar-profile-menu')
                            </div>
                        </div>
                    </div>
                </header>

                <main class="space-y-6 px-4 py-6 md:px-6">
                    <section class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-brand">Jadwal Harian</p>
                            <h2 class="mt-2 font-display text-3xl font-bold tracking-tight text-ink md:text-4xl">{{ $selectedField?->name ?? 'Belum ada lapangan' }}</h2>
                            <p class="mt-2 text-sm text-slateSoft">Pantau slot tersedia dan slot yang sudah terisi untuk operasional harian.</p>
                        </div>

                        <form action="{{ route('owner.schedules.index') }}" method="GET" class="grid gap-3 rounded-2xl border border-line bg-white p-3 shadow-sm sm:grid-cols-[220px_170px_auto]">
                            <select name="field_id" class="rounded-xl border-line text-sm focus:border-brand focus:ring-brand/20">
                                @foreach ($fields as $field)
                                    <option value="{{ $field->id }}" @selected($selectedField?->id === $field->id)>{{ $field->name }}</option>
                                @endforeach
                            </select>
                            <input name="date" type="date" value="{{ $date }}" class="rounded-xl border-line text-sm focus:border-brand focus:ring-brand/20">
                            <button type="submit" class="rounded-xl bg-ink px-5 py-2 text-sm font-bold text-white transition hover:bg-slate-700">Tampilkan</button>
                        </form>
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

                    <section class="rounded-3xl border border-line bg-panel shadow-card">
                        <div class="flex flex-col gap-2 border-b border-line px-5 py-5 md:flex-row md:items-end md:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.22em] text-brand">Timeline Slot</p>
                                <h2 class="mt-1 font-display text-2xl font-bold">{{ \Carbon\CarbonImmutable::parse($date)->format('d M Y') }}</h2>
                            </div>
                            <p class="text-sm text-slateSoft">{{ $openTime }}-{{ $closeTime }} · {{ $slotDurationMinutes }} menit per slot</p>
                        </div>

                        @if ($selectedField === null)
                            <div class="px-5 py-12 text-center">
                                <h3 class="font-display text-2xl font-bold">Belum ada lapangan</h3>
                                <p class="mt-2 text-sm text-slateSoft">Tambahkan lapangan dulu agar jadwal harian bisa ditampilkan.</p>
                                <a href="{{ route('owner.fields.index') }}#create-field" class="mt-5 inline-flex rounded-xl bg-brand px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand/20 transition hover:bg-blue-600">Tambah Lapangan</a>
                            </div>
                        @else
                            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($slots as $slot)
                                    @php
                                        $booking = $slot['booking_id'] ? $bookings->get($slot['booking_id']) : null;
                                        $payment = $booking?->payments->first();
                                        $customerName = $booking?->customer_name ?? $booking?->user?->name ?? 'Slot tersedia';
                                        $customerContact = $booking?->customer_contact ?? $booking?->customer_email ?? $booking?->user?->email;
                                    @endphp
                                    <article class="rounded-2xl border p-4 {{ $booking === null ? 'border-emerald-200 bg-emerald-50/60' : 'border-line bg-white' }}">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="font-display text-xl font-bold">{{ $formatTime($slot['start_time'] ?? null, '00:00') }}-{{ $formatTime($slot['end_time'] ?? null, '00:00') }}</p>
                                                <p class="mt-1 text-xs font-semibold text-slateSoft">{{ $booking === null ? 'Tersedia' : $booking->booking_code }}</p>
                                            </div>
                                            @if ($booking === null)
                                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">tersedia</span>
                                            @else
                                                <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $bookingBadge($booking->status) }}">{{ $booking->status }}</span>
                                            @endif
                                        </div>

                                        @if ($booking !== null)
                                            <div class="mt-4 rounded-xl bg-slate-50 p-3">
                                                <div class="text-sm font-bold">{{ $customerName }}</div>
                                                @if ($customerContact)
                                                    <div class="mt-1 text-xs text-slateSoft">{{ $customerContact }}</div>
                                                @endif
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $paymentBadge($payment?->status) }}">{{ $payment?->status ?? 'unpaid' }}</span>
                                                    <a href="{{ route('owner.bookings.index', ['focus' => $booking->id, 'date' => $date]) }}#booking-{{ $booking->id }}" class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-brand ring-1 ring-line">Detail</a>
                                                </div>
                                            </div>
                                        @else
                                            <p class="mt-4 text-sm font-semibold text-emerald-800">Slot ini masih kosong dan bisa dipesan customer.</p>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
