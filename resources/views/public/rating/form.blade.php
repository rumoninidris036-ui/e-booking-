<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SMASHCOURT') }} | Ulasan Tamu</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100">
        <main class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="absolute inset-x-0 top-0 -z-10 h-72 bg-[radial-gradient(circle_at_top,_rgba(195,244,0,0.16),_transparent_55%)]"></div>

            <div class="grid w-full overflow-hidden rounded-3xl border border-white/10 bg-slate-900 shadow-2xl lg:grid-cols-[0.95fr_1.05fr]">
                <section class="relative overflow-hidden bg-gradient-to-br from-lime-400/20 via-slate-900 to-slate-900 px-6 py-8 sm:px-10 sm:py-12">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(195,244,0,0.18),_transparent_35%)]"></div>
                    <div class="relative">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-lime-300">Ulasan Tamu</p>
                        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ $field->name }}</h1>
                        <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300 sm:text-base">
                            Terima kasih sudah main di SMASHCOURT. Ceritakan pengalamanmu supaya orang lain punya gambaran yang lebih jelas.
                        </p>

                        <div class="mt-8 space-y-4">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Kode Booking</p>
                                <p class="mt-2 font-semibold text-white">{{ $booking->booking_code }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Lapangan</p>
                                <p class="mt-2 font-semibold text-white">{{ $field->name }}</p>
                            </div>
                        </div>

                        <div class="mt-8 rounded-2xl border border-lime-400/20 bg-lime-400/10 p-4 text-sm leading-6 text-lime-50">
                            Satu booking hanya bisa mengirim satu ulasan. Tautan ini akan ditolak kalau sudah pernah dipakai.
                        </div>
                    </div>
                </section>

                <section class="bg-slate-950 px-6 py-8 sm:px-10 sm:py-12">
                    <div class="mx-auto max-w-xl">
                        <div class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full bg-lime-400"></span>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Ulasan Publik</p>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">Kirim Ulasan</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Pilih bintang lalu tulis komentar singkat jika ingin memberi masukan.
                        </p>

                        @if ($errors->any())
                            <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ $storeUrl }}" class="mt-6 space-y-6">
                            @csrf

                            <div>
                                <label class="mb-3 block text-sm font-semibold text-slate-200">Nilai</label>
                                <div class="grid grid-cols-5 gap-2">
                                    @for ($score = 5; $score >= 1; $score--)
                                        <label class="cursor-pointer">
                                            <input
                                                type="radio"
                                                name="score"
                                                value="{{ $score }}"
                                                class="peer sr-only"
                                                @checked((int) old('score', 5) === $score)
                                            >
                                            <span class="flex h-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-lg font-bold text-slate-300 transition duration-200 peer-checked:border-lime-400 peer-checked:bg-lime-400 peer-checked:text-slate-950 hover:-translate-y-0.5 hover:border-lime-400 hover:bg-white/10">
                                                {{ $score }} ★
                                            </span>
                                        </label>
                                    @endfor
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Bawaan 5 bintang. Silakan sesuaikan dengan pengalamanmu.</p>
                            </div>

                            <div>
                                <label for="comment" class="mb-3 block text-sm font-semibold text-slate-200">Komentar</label>
                                <textarea
                                    id="comment"
                                    name="comment"
                                    rows="5"
                                    class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-slate-100 placeholder:text-slate-500 transition focus:border-lime-400 focus:outline-none focus:ring-2 focus:ring-lime-400/20"
                                    placeholder="Tulis sedikit catatan tentang lapangan ini..."
                                >{{ old('comment') }}</textarea>
                            </div>

                            <button type="submit" class="w-full rounded-2xl bg-lime-400 px-6 py-4 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 transition-transform hover:-translate-y-0.5 hover:shadow-lg hover:shadow-lime-400/20">
                                Kirim Ulasan
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
