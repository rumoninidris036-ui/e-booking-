<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SmashCourt | Book {{ $field->name }}</title>

        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            tertiary: '#ffb693',
                            'surface-container-low': '#1a1c1f',
                            'on-tertiary-container': '#fff7f4',
                            'primary-fixed-dim': '#b3c5ff',
                            'surface-container-high': '#282a2d',
                            'primary-container': '#0066ff',
                            'surface-variant': '#333538',
                            'inverse-primary': '#0054d6',
                            error: '#ffb4ab',
                            'secondary-fixed-dim': '#abd600',
                            'on-secondary-fixed-variant': '#3c4d00',
                            'surface-dim': '#111316',
                            'inverse-surface': '#e2e2e6',
                            'secondary-container': '#c3f400',
                            'outline-variant': '#424656',
                            'on-secondary-container': '#556d00',
                            primary: '#b3c5ff',
                            secondary: '#ffffff',
                            'on-tertiary-fixed': '#351000',
                            'on-surface-variant': '#c2c6d8',
                            'on-secondary': '#283500',
                            'on-tertiary': '#561f00',
                            'tertiary-container': '#c04f00',
                            'on-primary-container': '#f8f7ff',
                            'on-error': '#690005',
                            'tertiary-fixed': '#ffdbcc',
                            'secondary-fixed': '#c3f400',
                            'error-container': '#93000a',
                            'on-surface': '#e2e2e6',
                            'surface-container': '#1e2023',
                            'on-primary': '#002b75',
                            'on-tertiary-fixed-variant': '#7a3000',
                            'on-primary-fixed': '#001849',
                            'on-secondary-fixed': '#161e00',
                            surface: '#111316',
                            'inverse-on-surface': '#2f3034',
                            background: '#111316',
                            'on-background': '#e2e2e6',
                            'surface-tint': '#b3c5ff',
                            'tertiary-fixed-dim': '#ffb693',
                            'on-primary-fixed-variant': '#003fa4',
                            outline: '#8c90a1',
                            'on-error-container': '#ffdad6',
                            'surface-container-lowest': '#0c0e11',
                            'surface-container-highest': '#333538',
                            'primary-fixed': '#dae1ff',
                            'surface-bright': '#37393d',
                        },
                        borderRadius: {
                            DEFAULT: '0.25rem',
                            lg: '0.5rem',
                            xl: '0.75rem',
                            full: '9999px',
                        },
                        spacing: {
                            'skew-angle': '6deg',
                            'margin-mobile': '20px',
                            'margin-desktop': '40px',
                            base: '8px',
                            gutter: '16px',
                        },
                        fontFamily: {
                            'body-md': ['Inter', 'sans-serif'],
                            'label-bold': ['Inter', 'sans-serif'],
                            'headline-xl-mobile': ['Montserrat', 'sans-serif'],
                            'headline-md': ['Montserrat', 'sans-serif'],
                            'headline-xl': ['Montserrat', 'sans-serif'],
                            'headline-lg': ['Montserrat', 'sans-serif'],
                            'body-lg': ['Inter', 'sans-serif'],
                        },
                        fontSize: {
                            'body-md': ['16px', { lineHeight: '1.5', fontWeight: '400' }],
                            'label-bold': ['14px', { lineHeight: '1', letterSpacing: '0.05em', fontWeight: '600' }],
                            'headline-xl-mobile': ['32px', { lineHeight: '1.2', fontWeight: '800' }],
                            'headline-md': ['24px', { lineHeight: '1.3', fontWeight: '700' }],
                            'headline-xl': ['48px', { lineHeight: '1.1', letterSpacing: '-0.02em', fontWeight: '800' }],
                            'headline-lg': ['32px', { lineHeight: '1.2', fontWeight: '700' }],
                            'body-lg': ['18px', { lineHeight: '1.6', fontWeight: '400' }],
                        },
                    },
                },
            };
        </script>

        <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }

            .skew-container {
                transform: skewY(-2deg);
            }

            .unskew-content {
                transform: skewY(2deg);
            }

            .glass-panel {
                background: rgba(28, 31, 38, 0.7);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .neon-glow-primary {
                box-shadow: 0 0 15px rgba(179, 197, 255, 0.3);
            }

            .neon-glow-secondary {
                box-shadow: 0 4px 0 0 #8eb000;
            }

            .leaflet-container {
                background: #1a1c1f;
            }
        </style>
    </head>
    <body class="overflow-x-hidden bg-background font-body-md text-on-background selection:bg-secondary-container selection:text-black">
        @php
            $coverImage = $field->cover_image_url ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAhdBw-PaiSWK4P8gnpAPJY-s617sXbd_2ldvDJWE5ktxsMPeGCYflbTZX-ANdN3aJ2tft1lRBGVkTuIE0mv9E4qWV8lp3jZR-C-7K9oEwW-4LvMJzr9676APnUkjvMOzo2kA8kJ63bhrQ_UJ-YVW2BstnzHhZVwYZatrqFbhmjZxVEOD-YatkuuhqUiQLQ47JAXNVrzK4AmlLd-_9OZWL0OEuzZ0wsVi0sOt0v7KQ90r2yLAprZ38wv4jeN6Vh6WTNvLEBv-bu6MVe';
            $featureIcons = [
                ['icon' => 'grid_view', 'title' => 'Synthetic Mat', 'subtitle' => 'Olympic standard'],
                ['icon' => 'lightbulb', 'title' => 'HD Lighting', 'subtitle' => 'Flicker-free'],
                ['icon' => 'ac_unit', 'title' => 'Air Conditioned', 'subtitle' => 'Climate control'],
                ['icon' => 'shower', 'title' => 'Locker Rooms', 'subtitle' => 'Premium facilities'],
            ];
            $selectedSlotData = collect($slots)->firstWhere('start_time', $selectedSlot);
            $scheduleState = [
                'selectedDate' => $selectedDate->toDateString(),
                'selectedSlot' => $selectedSlotData !== null && ($selectedSlotData['status'] ?? null) !== 'booked'
                    ? $selectedSlotData['start_time']
                    : null,
                'slots' => $slots,
                'dateOptions' => collect($dateOptions)->map(fn ($dateOption) => [
                    'value' => $dateOption->toDateString(),
                    'dayLabel' => $dateOption->format('D'),
                    'dayNumber' => $dateOption->format('d'),
                ])->all(),
                'pricePerHour' => (float) $field->price_per_hour,
                'slotDurationMinutes' => (int) ($field->slot_duration_minutes ?? \App\Services\Booking\FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES),
                'scheduleUrl' => route('public.fields.schedule', ['slug' => $field->slug]),
            ];
        @endphp

        <x-public-navbar />

        <main class="pb-24 pt-16">
            <section class="relative flex h-[409px] items-end overflow-hidden md:h-[512px]">
                <img src="{{ $coverImage }}" alt="{{ $field->name }}" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-background via-background/40 to-transparent"></div>
                <div class="relative w-full px-[20px] pb-12 md:px-[40px]">
                    <div class="mx-auto max-w-7xl">
                        <span class="mb-4 inline-block rounded-full bg-secondary-container px-3 py-1 font-label-bold text-label-bold text-black">PREMIUM COURT</span>
                        <h1 class="font-headline-xl-mobile text-headline-xl-mobile uppercase text-secondary md:font-headline-xl md:text-headline-xl">{{ $field->name }}</h1>
                        <div class="mt-4 flex flex-wrap gap-6">
                            <div class="flex items-center gap-2 text-on-surface-variant">
                                <span class="material-symbols-outlined">location_on</span>
                                <span class="font-body-md text-body-md">{{ $field->address ?? 'Location pending' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-on-surface-variant">
                                <span class="material-symbols-outlined">star</span>
                                <span class="font-body-md text-body-md">{{ $field->facilities->count() }} facility highlights</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto -mt-8 max-w-7xl px-[20px] md:px-[40px]">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                    <div class="space-y-8 lg:col-span-8">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            @foreach ($featureIcons as $feature)
                                <div class="glass-panel flex flex-col gap-2 rounded-xl p-4">
                                    <span class="material-symbols-outlined text-secondary-container">{{ $feature['icon'] }}</span>
                                    <p class="font-label-bold text-label-bold uppercase text-secondary">{{ $feature['title'] }}</p>
                                    <p class="text-[12px] text-on-surface-variant">{{ $feature['subtitle'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="rounded-2xl bg-surface-container p-6 md:p-8">
                            <div class="mb-6 flex items-center justify-between">
                                <h2 class="font-headline-md text-headline-md text-secondary">Select Date</h2>
                                <div id="selected-month-label" class="rounded-full border border-secondary-container/20 bg-secondary-container/10 px-4 py-2 text-[12px] font-label-bold uppercase text-secondary-container">
                                    {{ $selectedDate->format('F Y') }}
                                </div>
                            </div>

                            <div id="date-selector" class="flex gap-4 overflow-x-auto pb-4">
                                @foreach ($dateOptions as $dateOption)
                                    @php
                                        $isSelectedDate = $dateOption->isSameDay($selectedDate);
                                    @endphp
                                    <button
                                        type="button"
                                        data-date-option
                                        data-date="{{ $dateOption->toDateString() }}"
                                        class="flex w-20 flex-shrink-0 flex-col items-center justify-center rounded-xl px-2 py-4 transition-all {{ $isSelectedDate ? 'bg-secondary-container/10 ring-2 ring-secondary-container/50 border-secondary-container' : 'glass-panel hover:bg-surface-variant' }}"
                                    >
                                        <span class="mb-1 text-[12px] font-label-bold uppercase {{ $isSelectedDate ? 'text-secondary-container' : 'text-on-surface-variant' }}">
                                            {{ $dateOption->format('D') }}
                                        </span>
                                        <span class="text-2xl font-bold text-secondary">{{ $dateOption->format('d') }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-2xl bg-surface-container p-6 md:p-8">
                            <div class="mb-6 flex items-center justify-between">
                                <h2 class="font-headline-md text-headline-md text-secondary">Available Slots</h2>
                                <div class="flex gap-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-3 w-3 rounded-full bg-secondary-container"></div>
                                        <span class="text-[12px] font-label-bold uppercase text-on-surface-variant">Selected</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-3 w-3 rounded-full bg-surface-variant"></div>
                                        <span class="text-[12px] font-label-bold uppercase text-on-surface-variant">Booked</span>
                                    </div>
                                </div>
                            </div>

                            <div id="schedule-feedback" class="mb-4 hidden rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-sm text-on-surface-variant"></div>

                            <div id="slot-selector" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                @foreach ($slots as $slot)
                                    @php
                                        $isBooked = $slot['status'] === 'booked';
                                        $isSelectedSlot = $slot['start_time'] === $selectedSlot;
                                    @endphp

                                    @if ($isBooked)
                                        <div data-slot-disabled class="cursor-not-allowed rounded-lg border border-white/5 bg-surface-container-high py-3 text-center text-on-surface-variant opacity-50 line-through">
                                            {{ $slot['start_time'] }}-{{ $slot['end_time'] }}
                                        </div>
                                    @else
                                        <button
                                            type="button"
                                            data-slot-option
                                            data-slot-time="{{ $slot['start_time'] }}"
                                            class="rounded-lg py-3 text-center transition-colors {{ $isSelectedSlot ? 'bg-secondary-container font-bold text-black shadow-[0_0_15px_rgba(195,244,0,0.3)]' : 'border border-white/10 bg-surface-variant/20 text-on-surface hover:bg-surface-variant hover:text-secondary' }}"
                                        >
                                            {{ $slot['start_time'] }}-{{ $slot['end_time'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="group relative rounded-2xl bg-surface-container p-4 md:p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-secondary">
                                    <span class="material-symbols-outlined text-secondary-container">location_on</span>
                                    <h2 class="font-headline-md text-headline-md">{{ $field->name }} Location</h2>
                                </div>
                                <a
                                    href="https://www.openstreetmap.org/?mlat={{ $mapMeta['marker']['latitude'] ?? '' }}&mlon={{ $mapMeta['marker']['longitude'] ?? '' }}#map=16/{{ $mapMeta['marker']['latitude'] ?? '' }}/{{ $mapMeta['marker']['longitude'] ?? '' }}"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="flex items-center gap-2 rounded-lg border border-secondary-container px-4 py-2 text-[12px] font-label-bold uppercase text-secondary-container transition-colors hover:bg-secondary-container/10"
                                >
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    View Full Map
                                </a>
                            </div>

                            <div class="relative h-[300px] overflow-hidden rounded-xl">
                                <div id="booking-map" class="h-full w-full"></div>
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-background/60 to-transparent"></div>
                            </div>

                            <div class="mt-4 flex items-center justify-between text-on-surface-variant">
                                <p class="font-body-md text-body-md">{{ $field->address ?? 'Alamat belum tersedia.' }}</p>
                                <span class="text-[12px] font-label-bold uppercase">OSM Enabled</span>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6 lg:col-span-4">
                        <div class="glass-panel sticky top-24 rounded-2xl p-6">
                            <h2 class="mb-6 font-headline-md text-headline-md text-secondary">Booking Summary</h2>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant">Court</span>
                                    <span class="font-semibold text-secondary">{{ $field->name }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant">Date</span>
                                    <span id="booking-summary-date" class="font-semibold text-secondary">{{ $selectedDate->format('d M Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant">Slot</span>
                                    <span id="booking-summary-slot" class="font-semibold text-secondary">{{ $selectedSlotData !== null ? $selectedSlotData['start_time'].'-'.$selectedSlotData['end_time'] : 'Choose a slot' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant">Duration</span>
                                    <span id="booking-summary-duration" class="font-semibold text-secondary">{{ (int) ($field->slot_duration_minutes ?? \App\Services\Booking\FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES) }} menit</span>
                                </div>
                                <div class="border-t border-white/10 pt-4">
                                    <div class="flex items-center justify-between">
                                        <span class="font-label-bold text-label-bold uppercase text-on-surface-variant">Total</span>
                                        <span class="font-headline-md text-headline-md text-secondary">
                                            Rp{{ number_format((float) $field->price_per_hour, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if ($errors->any())
                                <div class="mt-8 rounded-xl border border-error-container bg-error-container/10 p-4 text-sm text-error">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div id="booking-summary-hint" class="mt-8 rounded-xl border border-outline-variant bg-surface-container-low p-4 text-sm text-on-surface-variant">
                                Isi data customer dan pilih slot terlebih dulu untuk mengaktifkan tombol booking.
                            </div>

                            <form method="POST" action="{{ route('public.fields.bookings.store', ['slug' => $field->slug]) }}" class="mt-4 space-y-4">
                                @csrf
                                <input id="booking-date-input" type="hidden" name="booking_date" value="{{ $selectedDate->toDateString() }}">
                                <input id="booking-slot-input" type="hidden" name="start_time" value="{{ $selectedSlotData['start_time'] ?? '' }}">

                                <div>
                                    <label for="customer_name" class="mb-2 block text-[12px] font-label-bold uppercase text-on-surface-variant">Nama Customer</label>
                                    <input
                                        id="customer_name"
                                        name="customer_name"
                                        type="text"
                                        value="{{ old('customer_name', auth()->user()?->name) }}"
                                        required
                                        autocomplete="name"
                                        class="w-full rounded-xl border border-white/10 bg-surface-container-low px-4 py-3 text-secondary placeholder:text-on-surface-variant focus:border-secondary-container focus:ring-secondary-container"
                                        placeholder="Nama lengkap"
                                    >
                                </div>

                                <div>
                                    <label for="customer_contact" class="mb-2 block text-[12px] font-label-bold uppercase text-on-surface-variant">Nomor WhatsApp / Telegram</label>
                                    <input
                                        id="customer_contact"
                                        name="customer_contact"
                                        type="text"
                                        value="{{ old('customer_contact') }}"
                                        required
                                        inputmode="tel"
                                        autocomplete="tel"
                                        class="w-full rounded-xl border border-white/10 bg-surface-container-low px-4 py-3 text-secondary placeholder:text-on-surface-variant focus:border-secondary-container focus:ring-secondary-container"
                                        placeholder="08xxxxxxxxxx atau @telegram"
                                    >
                                </div>

                                <div>
                                    <label for="customer_email" class="mb-2 block text-[12px] font-label-bold uppercase text-on-surface-variant">Email Opsional</label>
                                    <input
                                        id="customer_email"
                                        name="customer_email"
                                        type="email"
                                        value="{{ old('customer_email', auth()->user()?->email) }}"
                                        autocomplete="email"
                                        class="w-full rounded-xl border border-white/10 bg-surface-container-low px-4 py-3 text-secondary placeholder:text-on-surface-variant focus:border-secondary-container focus:ring-secondary-container"
                                        placeholder="nama@email.com"
                                    >
                                </div>

                                <button id="confirm-booking-button" type="submit" @disabled($selectedSlotData === null) class="w-full rounded-xl bg-secondary-container py-4 font-label-bold text-label-bold uppercase text-black neon-glow-secondary transition-transform disabled:cursor-not-allowed disabled:opacity-50 active:translate-y-[2px]">
                                    Confirm Booking
                                </button>
                            </form>

                            <a href="{{ route('public.fields.show', ['slug' => $field->slug]) }}" class="mt-4 block w-full rounded-xl border border-primary px-6 py-4 text-center font-label-bold text-label-bold uppercase text-primary transition-all hover:bg-primary/10">
                                Back To Detail
                            </a>
                        </div>
                    </aside>
                </div>
            </section>
        </main>

        <script>
            const marker = @json($mapMeta['marker']);
            const scheduleState = @json($scheduleState);
            const dateSelector = document.getElementById('date-selector');
            const slotSelector = document.getElementById('slot-selector');
            const scheduleFeedback = document.getElementById('schedule-feedback');
            const selectedMonthLabel = document.getElementById('selected-month-label');
            const bookingSummaryDate = document.getElementById('booking-summary-date');
            const bookingSummarySlot = document.getElementById('booking-summary-slot');
            const bookingSummaryDuration = document.getElementById('booking-summary-duration');
            const bookingSummaryHint = document.getElementById('booking-summary-hint');
            const bookingDateInput = document.getElementById('booking-date-input');
            const bookingSlotInput = document.getElementById('booking-slot-input');
            const confirmBookingButton = document.getElementById('confirm-booking-button');

            const slotButtonClass = 'border border-white/10 bg-surface-variant/20 text-on-surface hover:bg-surface-variant hover:text-secondary';
            const slotSelectedClass = 'bg-secondary-container font-bold text-black shadow-[0_0_15px_rgba(195,244,0,0.3)]';

            function formatMonthLabel(dateString) {
                return new Intl.DateTimeFormat('en-US', {
                    month: 'long',
                    year: 'numeric',
                }).format(new Date(`${dateString}T00:00:00`));
            }

            function formatSummaryDate(dateString) {
                return new Intl.DateTimeFormat('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                }).format(new Date(`${dateString}T00:00:00`));
            }

            function selectedSlotDetails() {
                return scheduleState.slots.find((slot) => slot.start_time === scheduleState.selectedSlot) ?? null;
            }

            function updateSummary() {
                const selectedSlot = selectedSlotDetails();

                selectedMonthLabel.textContent = formatMonthLabel(scheduleState.selectedDate);
                bookingSummaryDate.textContent = formatSummaryDate(scheduleState.selectedDate);
                bookingSummarySlot.textContent = selectedSlot
                    ? `${selectedSlot.start_time}-${selectedSlot.end_time}`
                    : 'Choose a slot';

                if (bookingSummaryDuration) {
                    bookingSummaryDuration.textContent = `${scheduleState.slotDurationMinutes ?? 60} menit`;
                }

                if (bookingDateInput) {
                    bookingDateInput.value = scheduleState.selectedDate;
                }

                if (bookingSlotInput) {
                    bookingSlotInput.value = scheduleState.selectedSlot ?? '';
                }

                if (confirmBookingButton) {
                    confirmBookingButton.disabled = ! scheduleState.selectedSlot;
                }

                if (bookingSummaryHint && confirmBookingButton) {
                    bookingSummaryHint.textContent = scheduleState.selectedSlot
                        ? 'Slot sudah siap. Lanjutkan booking saat kamu yakin dengan jadwal yang dipilih.'
                        : 'Isi data customer dan pilih slot terlebih dulu untuk mengaktifkan tombol booking.';
                }
            }

            function renderDateOptions() {
                const buttons = dateSelector.querySelectorAll('[data-date-option]');

                buttons.forEach((button) => {
                    const isSelected = button.dataset.date === scheduleState.selectedDate;

                    button.classList.toggle('glass-panel', ! isSelected);
                    button.classList.toggle('hover:bg-surface-variant', ! isSelected);
                    button.classList.toggle('bg-secondary-container/10', isSelected);
                    button.classList.toggle('ring-2', isSelected);
                    button.classList.toggle('ring-secondary-container/50', isSelected);
                    button.classList.toggle('border-secondary-container', isSelected);

                    const label = button.querySelector('span:first-child');
                    if (label) {
                        label.classList.toggle('text-secondary-container', isSelected);
                        label.classList.toggle('text-on-surface-variant', ! isSelected);
                    }
                });
            }

            function renderSlots() {
                slotSelector.innerHTML = '';

                if (! scheduleState.slots.length) {
                    slotSelector.innerHTML = '<div class="col-span-full rounded-xl border border-outline-variant bg-surface-container-low p-4 text-center text-sm text-on-surface-variant">Belum ada slot untuk tanggal ini.</div>';
                    updateSummary();
                    return;
                }

                scheduleState.slots.forEach((slot) => {
                    if (slot.status === 'booked') {
                        const blockedSlot = document.createElement('div');
                        blockedSlot.className = 'cursor-not-allowed rounded-lg border border-white/5 bg-surface-container-high py-3 text-center text-on-surface-variant opacity-50 line-through';
                        blockedSlot.textContent = `${slot.start_time}-${slot.end_time}`;
                        slotSelector.appendChild(blockedSlot);
                        return;
                    }

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.slotOption = 'true';
                    button.dataset.slotTime = slot.start_time;
                    button.textContent = `${slot.start_time}-${slot.end_time}`;

                    const isSelected = scheduleState.selectedSlot === slot.start_time;
                    button.className = `rounded-lg py-3 text-center transition-colors ${isSelected ? slotSelectedClass : slotButtonClass}`;

                    button.addEventListener('click', () => {
                        scheduleState.selectedSlot = slot.start_time;
                        renderSlots();
                        updateSummary();
                        syncUrl();
                    });

                    slotSelector.appendChild(button);
                });

                updateSummary();
            }

            function setFeedback(message, type = 'info') {
                if (! message) {
                    scheduleFeedback.classList.add('hidden');
                    scheduleFeedback.textContent = '';
                    return;
                }

                scheduleFeedback.classList.remove('hidden');
                scheduleFeedback.textContent = message;
                scheduleFeedback.classList.toggle('border-error-container', type === 'error');
                scheduleFeedback.classList.toggle('text-error', type === 'error');
                scheduleFeedback.classList.toggle('border-outline-variant', type !== 'error');
                scheduleFeedback.classList.toggle('text-on-surface-variant', type !== 'error');
            }

            function syncUrl() {
                const url = new URL(window.location.href);
                url.searchParams.set('date', scheduleState.selectedDate);

                if (scheduleState.selectedSlot) {
                    url.searchParams.set('slot', scheduleState.selectedSlot);
                } else {
                    url.searchParams.delete('slot');
                }

                window.history.replaceState({}, '', url);
            }

            async function loadSchedule(date) {
                scheduleState.selectedDate = date;
                scheduleState.selectedSlot = null;

                renderDateOptions();
                renderSlots();
                setFeedback('Memuat slot terbaru...');
                syncUrl();

                try {
                    const response = await fetch(`${scheduleState.scheduleUrl}?date=${date}`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (! response.ok) {
                        throw new Error('Gagal memuat jadwal.');
                    }

                    const payload = await response.json();
                    scheduleState.slots = payload.data?.slots ?? [];
                    scheduleState.slotDurationMinutes = payload.meta?.slot_duration_minutes ?? scheduleState.slotDurationMinutes;
                    setFeedback('');
                    renderSlots();
                } catch (error) {
                    scheduleState.slots = [];
                    renderSlots();
                    setFeedback('Jadwal belum bisa dimuat. Coba pilih tanggal lagi sebentar.', 'error');
                }
            }

            dateSelector.querySelectorAll('[data-date-option]').forEach((button) => {
                button.addEventListener('click', () => {
                    const nextDate = button.dataset.date;

                    if (! nextDate || nextDate === scheduleState.selectedDate) {
                        return;
                    }

                    loadSchedule(nextDate);
                });
            });

            if (marker && marker.latitude && marker.longitude) {
                const map = L.map('booking-map', {
                    scrollWheelZoom: false,
                }).setView([marker.latitude, marker.longitude], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                L.marker([marker.latitude, marker.longitude])
                    .addTo(map)
                    .bindPopup(`<strong>${marker.name}</strong><br>${marker.address ?? ''}`)
                    .openPopup();
            }

            renderDateOptions();
            renderSlots();
            updateSummary();
        </script>
    </body>
</html>
