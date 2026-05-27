<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Payment {{ $payment->order_id }}</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">

        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            background: '#111316',
                            surface: '#1a1c1f',
                            panel: '#1e2023',
                            panelHigh: '#282a2d',
                            muted: '#c2c6d8',
                            line: '#424656',
                            primary: '#b3c5ff',
                            accent: '#c3f400',
                            danger: '#ffb4ab',
                        },
                        fontFamily: {
                            display: ['Montserrat', 'sans-serif'],
                            body: ['Inter', 'sans-serif'],
                        },
                    },
                },
            };
        </script>
    </head>
    <body class="min-h-screen bg-background font-body text-white">
        @php
            $paymentStatus = $payment->status;
            $bookingStatus = $booking->status;
            $customerName = $booking->customer_name ?: $booking->user?->name ?: 'Customer';
            $statusPalette = match ($payment->status) {
                \App\Models\Payment::STATUS_SUCCESS => [
                    'label' => 'Payment Confirmed',
                    'badge' => 'border-accent/30 bg-accent/10 text-accent',
                    'panel' => 'border-accent/30 bg-accent/10 text-white',
                    'description' => 'Pembayaran berhasil diverifikasi. Slot kamu sudah aman dan booking sudah dianggap paid.',
                ],
                \App\Models\Payment::STATUS_FAILED => [
                    'label' => 'Payment Needs Retry',
                    'badge' => 'border-danger/30 bg-danger/10 text-danger',
                    'panel' => 'border-danger/30 bg-danger/10 text-danger',
                    'description' => 'Pembayaran sebelumnya belum berhasil. Kamu masih bisa melanjutkan bayar lagi selama booking belum dibatalkan.',
                ],
                default => [
                    'label' => 'Waiting For Payment',
                    'badge' => 'border-primary/30 bg-primary/10 text-primary',
                    'panel' => 'border-primary/30 bg-primary/10 text-white',
                    'description' => 'Sistem masih menunggu pembayaran selesai atau sinkronisasi status terbaru dari Midtrans.',
                ],
            };
        @endphp
        <main
            id="payment-page"
            data-payment-show-url="{{ $paymentUrl }}"
            data-payment-store-url="{{ $paymentStoreUrl }}"
            data-invoice-download-url="{{ $invoiceDownloadUrl }}"
            data-booking-url="{{ route('public.fields.booking', ['slug' => $field->slug, 'date' => $booking->booking_date->format('Y-m-d'), 'slot' => substr((string) $booking->start_time, 0, 5)]) }}"
            data-payment-status="{{ $paymentStatus }}"
            data-booking-status="{{ $bookingStatus }}"
            data-snap-redirect-url="{{ $snapRedirectUrl ?? '' }}"
            class="mx-auto flex min-h-screen max-w-6xl items-center px-5 py-10 md:px-10"
        >
            <div class="grid w-full gap-8 lg:grid-cols-[1.25fr_0.95fr]">
                <section class="overflow-hidden rounded-3xl border border-white/10 bg-surface shadow-2xl shadow-black/20">
                    <div class="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,_rgba(195,244,0,0.22),_transparent_35%),linear-gradient(135deg,_rgba(179,197,255,0.18),_transparent_65%)] px-6 py-8 md:px-10">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-accent">Secure Checkout</p>
                        <h1 class="mt-3 font-display text-3xl font-extrabold uppercase tracking-tight text-white md:text-5xl">Complete Your Court Payment</h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-muted md:text-base">
                            Booking kamu sudah dibuat dan slot sudah ditahan sementara. Lanjutkan pembayaran melalui Midtrans untuk mengamankan jadwal secara penuh.
                        </p>
                    </div>

                    <div class="space-y-8 px-6 py-8 md:px-10">
                        @if (session('status'))
                            <div class="rounded-2xl border border-accent/30 bg-accent/10 px-4 py-4 text-sm text-white">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-panel px-5 py-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Booking Code</p>
                                <p class="mt-2 text-xl font-semibold text-white">{{ $booking->booking_code }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-panel px-5 py-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Payment Status</p>
                                <div id="payment-status-badge" class="mt-2 inline-flex rounded-full border px-4 py-2 text-sm font-semibold uppercase tracking-[0.18em] {{ $statusPalette['badge'] }}">
                                    {{ $paymentStatus }}
                                </div>
                            </div>
                        </div>

                        <div id="payment-status-panel" class="rounded-3xl border px-5 py-5 {{ $statusPalette['panel'] }}">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em]">Status Overview</p>
                            <h2 id="payment-status-label" class="mt-2 font-display text-2xl font-bold uppercase tracking-tight">{{ $statusPalette['label'] }}</h2>
                            <p id="payment-status-description" class="mt-3 text-sm leading-7 text-inherit/90">
                                {{ $statusPalette['description'] }}
                            </p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-panel px-5 py-6">
                            <h2 class="font-display text-2xl font-bold uppercase tracking-tight text-white">Booking Summary</h2>

                            <div class="mt-6 space-y-4 text-sm text-muted">
                                <div class="flex items-center justify-between gap-4">
                                    <span>Court</span>
                                    <span class="text-right font-semibold text-white">{{ $field->name }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <span>Date</span>
                                    <span class="text-right font-semibold text-white">{{ $booking->booking_date->format('d M Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <span>Session</span>
                                    <span class="text-right font-semibold text-white">{{ substr((string) $booking->start_time, 0, 5) }} - {{ substr((string) $booking->end_time, 0, 5) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <span>Customer</span>
                                    <span class="text-right font-semibold text-white">{{ $customerName }}</span>
                                </div>
                                <div class="border-t border-white/10 pt-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-xs font-semibold uppercase tracking-[0.22em] text-muted">Total Payment</span>
                                        <span class="font-display text-2xl font-extrabold text-accent">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-panelHigh px-5 py-6">
                            <h2 class="font-display text-xl font-bold uppercase tracking-tight text-white">Payment Notes</h2>
                            <ul class="mt-4 space-y-3 text-sm leading-6 text-muted">
                                <li>Pembayaran diproses di halaman Midtrans resmi.</li>
                                <li>Status booking akan ter-update otomatis setelah callback Midtrans diterima.</li>
                                <li>Jangan tutup tab sebelum proses di Midtrans selesai.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <aside class="rounded-3xl border border-white/10 bg-surface p-6 shadow-2xl shadow-black/20 md:p-8">
                    <h2 class="font-display text-2xl font-bold uppercase tracking-tight text-white">Next Step</h2>
                    <p class="mt-3 text-sm leading-7 text-muted">
                        Kamu akan dibawa ke halaman pembayaran Midtrans yang aman. Pastikan order ID dan nominal sudah sesuai sebelum membayar.
                    </p>

                    <div id="payment-action-area">
                        @if ($paymentStatus === \App\Models\Payment::STATUS_SUCCESS)
                            <div class="mt-8 rounded-2xl border border-accent/30 bg-accent/10 px-4 py-5 text-sm leading-6 text-white">
                                Payment sudah sukses. Kamu tidak perlu menekan tombol bayar lagi.
                            </div>
                            <a
                                href="{{ $invoiceDownloadUrl }}"
                                class="mt-4 block w-full rounded-2xl bg-accent px-6 py-4 text-center text-sm font-bold uppercase tracking-[0.18em] text-black transition-transform hover:-translate-y-0.5"
                            >
                                Download Invoice PDF
                            </a>
                        @elseif ($paymentStatus === \App\Models\Payment::STATUS_PENDING && $snapRedirectUrl !== null)
                            <a
                                href="{{ $snapRedirectUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-8 block w-full rounded-2xl bg-accent px-6 py-4 text-center text-sm font-bold uppercase tracking-[0.18em] text-black transition-transform hover:-translate-y-0.5"
                            >
                                Continue To Pay
                            </a>

                            <p class="mt-4 text-xs leading-6 text-muted">
                                Midtrans akan dibuka di tab baru. Halaman ini tetap terbuka untuk memantau status pembayaranmu.
                            </p>
                        @elseif ($bookingStatus === \App\Models\Booking::STATUS_PENDING)
                            <form method="POST" action="{{ $paymentStoreUrl }}" class="mt-8">
                                @csrf
                                <button type="submit" class="w-full rounded-2xl bg-accent px-6 py-4 text-sm font-bold uppercase tracking-[0.18em] text-black transition-transform hover:-translate-y-0.5">
                                    Continue To Pay
                                </button>
                            </form>

                            <p class="mt-4 text-xs leading-6 text-muted">
                                Kalau sesi lama gagal, sistem akan membuat sesi pembayaran Midtrans baru yang tetap terikat ke booking yang sama.
                            </p>
                        @else
                            <div class="mt-8 rounded-2xl border border-danger/30 bg-danger/10 px-4 py-4 text-sm leading-6 text-danger">
                                Booking ini sudah tidak bisa diproses untuk pembayaran lagi dari halaman ini.
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 rounded-2xl border border-white/10 bg-panel px-4 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted">Live Status</p>
                                <p id="payment-live-status" class="mt-2 text-sm text-white">
                                    {{ $paymentStatus === \App\Models\Payment::STATUS_PENDING ? 'Monitoring payment status automatically...' : 'Status sudah sinkron.' }}
                                </p>
                            </div>
                            <button
                                id="refresh-payment-status-button"
                                type="button"
                                class="rounded-xl border border-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-white transition-colors hover:bg-white/5"
                            >
                                Refresh Status
                            </button>
                        </div>
                    </div>

                    <a
                        href="{{ route('public.fields.booking', ['slug' => $field->slug, 'date' => $booking->booking_date->format('Y-m-d'), 'slot' => substr((string) $booking->start_time, 0, 5)]) }}"
                        class="mt-4 block w-full rounded-2xl border border-white/10 px-6 py-4 text-center text-sm font-bold uppercase tracking-[0.18em] text-white transition-colors hover:bg-white/5"
                    >
                        Back To Booking
                    </a>
                </aside>
            </div>
        </main>
        <script>
            const paymentPage = document.getElementById('payment-page');

            if (paymentPage) {
                const paymentStatusBadge = document.getElementById('payment-status-badge');
                const paymentStatusPanel = document.getElementById('payment-status-panel');
                const paymentStatusLabel = document.getElementById('payment-status-label');
                const paymentStatusDescription = document.getElementById('payment-status-description');
                const paymentActionArea = document.getElementById('payment-action-area');
                const paymentLiveStatus = document.getElementById('payment-live-status');
                const refreshPaymentStatusButton = document.getElementById('refresh-payment-status-button');

                const state = {
                    paymentShowUrl: paymentPage.dataset.paymentShowUrl,
                    paymentStoreUrl: paymentPage.dataset.paymentStoreUrl,
                    invoiceDownloadUrl: paymentPage.dataset.invoiceDownloadUrl,
                    bookingUrl: paymentPage.dataset.bookingUrl,
                    paymentStatus: paymentPage.dataset.paymentStatus,
                    bookingStatus: paymentPage.dataset.bookingStatus,
                    snapRedirectUrl: paymentPage.dataset.snapRedirectUrl,
                    pollingHandle: null,
                    isRefreshing: false,
                };

                const statusMap = {
                    success: {
                        badge: 'border-accent/30 bg-accent/10 text-accent',
                        panel: 'border-accent/30 bg-accent/10 text-white',
                        label: 'Payment Confirmed',
                        description: 'Pembayaran berhasil diverifikasi. Slot kamu sudah aman dan booking sudah dianggap paid.',
                        liveText: 'Payment sudah berhasil dan status halaman ini sudah sinkron.',
                    },
                    failed: {
                        badge: 'border-danger/30 bg-danger/10 text-danger',
                        panel: 'border-danger/30 bg-danger/10 text-danger',
                        label: 'Payment Needs Retry',
                        description: 'Pembayaran sebelumnya belum berhasil. Kamu masih bisa melanjutkan bayar lagi selama booking belum dibatalkan.',
                        liveText: 'Pembayaran belum berhasil. Kamu bisa lanjut bayar lagi dari halaman ini.',
                    },
                    pending: {
                        badge: 'border-primary/30 bg-primary/10 text-primary',
                        panel: 'border-primary/30 bg-primary/10 text-white',
                        label: 'Waiting For Payment',
                        description: 'Sistem masih menunggu pembayaran selesai atau sinkronisasi status terbaru dari Midtrans.',
                        liveText: 'Monitoring payment status automatically...',
                    },
                };

                function renderActionArea() {
                    if (state.paymentStatus === 'success') {
                        paymentActionArea.innerHTML = `
                            <div class="mt-8 rounded-2xl border border-accent/30 bg-accent/10 px-4 py-5 text-sm leading-6 text-white">
                                Payment sudah sukses. Kamu tidak perlu menekan tombol bayar lagi.
                            </div>
                            <a
                                href="${state.invoiceDownloadUrl}"
                                class="mt-4 block w-full rounded-2xl bg-accent px-6 py-4 text-center text-sm font-bold uppercase tracking-[0.18em] text-black transition-transform hover:-translate-y-0.5"
                            >
                                Download Invoice PDF
                            </a>
                        `;
                        return;
                    }

                    if (state.paymentStatus === 'pending' && state.snapRedirectUrl) {
                        paymentActionArea.innerHTML = `
                            <a
                                href="${state.snapRedirectUrl}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-8 block w-full rounded-2xl bg-accent px-6 py-4 text-center text-sm font-bold uppercase tracking-[0.18em] text-black transition-transform hover:-translate-y-0.5"
                            >
                                Continue To Pay
                            </a>
                            <p class="mt-4 text-xs leading-6 text-muted">
                                Midtrans akan dibuka di tab baru. Halaman ini tetap terbuka untuk memantau status pembayaranmu.
                            </p>
                        `;
                        return;
                    }

                    if (state.bookingStatus === 'pending') {
                        paymentActionArea.innerHTML = `
                            <form method="POST" action="${state.paymentStoreUrl}" class="mt-8">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button type="submit" class="w-full rounded-2xl bg-accent px-6 py-4 text-sm font-bold uppercase tracking-[0.18em] text-black transition-transform hover:-translate-y-0.5">
                                    Continue To Pay
                                </button>
                            </form>
                            <p class="mt-4 text-xs leading-6 text-muted">
                                Kalau sesi lama gagal, sistem akan membuat sesi pembayaran Midtrans baru yang tetap terikat ke booking yang sama.
                            </p>
                        `;
                        return;
                    }

                    paymentActionArea.innerHTML = `
                        <div class="mt-8 rounded-2xl border border-danger/30 bg-danger/10 px-4 py-4 text-sm leading-6 text-danger">
                            Booking ini sudah tidak bisa diproses untuk pembayaran lagi dari halaman ini.
                        </div>
                    `;
                }

                function applyStatusUi() {
                    const config = statusMap[state.paymentStatus] ?? statusMap.pending;

                    paymentStatusBadge.className = `mt-2 inline-flex rounded-full border px-4 py-2 text-sm font-semibold uppercase tracking-[0.18em] ${config.badge}`;
                    paymentStatusBadge.textContent = state.paymentStatus;

                    paymentStatusPanel.className = `rounded-3xl border px-5 py-5 ${config.panel}`;
                    paymentStatusLabel.textContent = config.label;
                    paymentStatusDescription.textContent = config.description;
                    paymentLiveStatus.textContent = config.liveText;

                    renderActionArea();
                }

                function stopPolling() {
                    if (state.pollingHandle) {
                        window.clearInterval(state.pollingHandle);
                        state.pollingHandle = null;
                    }
                }

                async function refreshPaymentStatus() {
                    if (state.isRefreshing) {
                        return;
                    }

                    state.isRefreshing = true;
                    paymentLiveStatus.textContent = 'Checking latest payment status...';

                    try {
                        const response = await fetch(state.paymentShowUrl, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Failed to refresh payment status.');
                        }

                        const payload = await response.json();
                        const paymentData = payload.data ?? {};
                        const bookingData = paymentData.booking ?? {};

                        state.paymentStatus = paymentData.status ?? state.paymentStatus;
                        state.bookingStatus = bookingData.status ?? state.bookingStatus;
                        state.snapRedirectUrl = paymentData.snap_redirect_url ?? state.snapRedirectUrl;

                        applyStatusUi();

                        if (state.paymentStatus === 'success') {
                            stopPolling();
                        }
                    } catch (error) {
                        paymentLiveStatus.textContent = 'Belum bisa mengambil status terbaru. Coba refresh status lagi sebentar.';
                    } finally {
                        state.isRefreshing = false;
                    }
                }

                refreshPaymentStatusButton.addEventListener('click', refreshPaymentStatus);

                window.addEventListener('storage', (event) => {
                    if (event.key !== 'midtrans-payment-update' || !event.newValue) {
                        return;
                    }

                    try {
                        const payload = JSON.parse(event.newValue);

                        if (String(payload.paymentId) !== '{{ $payment->id }}') {
                            return;
                        }

                        refreshPaymentStatus();
                    } catch (error) {
                        // Ignore malformed storage payloads.
                    }
                });

                window.addEventListener('focus', () => {
                    if (state.paymentStatus === 'pending') {
                        refreshPaymentStatus();
                    }
                });

                applyStatusUi();

                if (state.paymentStatus === 'pending') {
                    state.pollingHandle = window.setInterval(refreshPaymentStatus, 6000);
                }
            }
        </script>
    </body>
</html>
