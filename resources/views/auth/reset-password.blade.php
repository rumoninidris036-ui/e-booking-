<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SmashCourt') }} | Atur Ulang Kata Sandi</title>

        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,700;0,800;1,800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            background: '#111316',
                            surface: '#111316',
                            'surface-container': '#1e2023',
                            'surface-container-low': '#1a1c1f',
                            'on-surface': '#e2e2e6',
                            'on-surface-variant': '#c2c6d8',
                            accent: '#c3f400',
                            outline: '#8c90a1',
                            'outline-variant': '#424656',
                        },
                        fontFamily: {
                            body: ['Inter', 'sans-serif'],
                            headline: ['Montserrat', 'sans-serif'],
                        },
                    },
                },
            };
        </script>
        <style>
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        </style>
    </head>
    <body class="min-h-screen overflow-x-hidden bg-background font-body text-on-surface">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/5 bg-surface/95 backdrop-blur-md">
            <div class="mx-auto flex h-[84px] max-w-7xl items-center justify-between px-5 md:px-10">
                <a href="{{ url('/') }}" class="font-headline text-2xl font-black italic tracking-[-0.07em] text-accent">SMASHCOURT</a>
                <a href="{{ route('login') }}" class="rounded-full border border-accent px-6 py-2.5 text-sm font-bold uppercase tracking-[0.04em] text-accent transition hover:bg-accent hover:text-[#283500]">Masuk</a>
            </div>
        </header>

        <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 pb-10 pt-28 md:px-10">
            <div class="pointer-events-none absolute inset-0">
                <img src="{{ asset('img/landing_page.jpeg') }}" alt="" class="h-full w-full object-cover opacity-15">
                <div class="absolute inset-0 bg-gradient-to-r from-background via-background/90 to-background/70"></div>
                <div class="absolute -left-32 bottom-0 h-96 w-96 rounded-full bg-accent/10 blur-3xl"></div>
            </div>

            <section class="relative z-10 w-full max-w-[480px]">
                <div class="overflow-hidden rounded-xl border border-white/10 bg-surface-container/95 p-7 shadow-2xl backdrop-blur-sm sm:p-10">
                    <div class="mb-8">
                        <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl border border-accent/20 bg-accent/10 text-accent">
                            <span class="material-symbols-outlined">lock_reset</span>
                        </span>
                        <p class="mb-2 text-sm font-bold uppercase tracking-[0.16em] text-accent">Atur ulang akses</p>
                        <h1 class="font-headline text-3xl font-extrabold uppercase italic leading-tight text-white sm:text-4xl">Buat kata sandi baru</h1>
                        <p class="mt-4 leading-relaxed text-on-surface-variant">Masukkan kata sandi baru untuk akun <span class="font-semibold text-white">{{ $request->email }}</span>.</p>
                    </div>

                    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div>
                            <label for="email" class="mb-2 ml-1 block text-sm font-bold uppercase tracking-[0.08em] text-on-surface-variant">Email</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-4 pl-12 pr-4 text-on-surface outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/30">
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-300" />
                        </div>

                        <div>
                            <label for="password" class="mb-2 ml-1 block text-sm font-bold uppercase tracking-[0.08em] text-on-surface-variant">Kata sandi baru</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                                <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-4 pl-12 pr-4 text-on-surface outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/30">
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-300" />
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 ml-1 block text-sm font-bold uppercase tracking-[0.08em] text-on-surface-variant">Konfirmasi kata sandi</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Ulangi kata sandi baru" class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-4 pl-12 pr-4 text-on-surface outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/30">
                            </div>
                        </div>

                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-accent py-4 text-sm font-bold uppercase tracking-[0.12em] text-[#283500] shadow-[0_4px_0_#3c4d00] transition hover:-translate-y-px hover:brightness-105 active:translate-y-[2px] active:shadow-[0_2px_0_#3c4d00]">
                            Simpan kata sandi baru
                            <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                        </button>
                    </form>

                    <div class="mt-8 border-t border-white/10 pt-6 text-center text-on-surface-variant">
                        Ingat kata sandi?
                        <a href="{{ route('login') }}" class="ml-1 font-bold text-accent transition hover:text-white">Kembali masuk</a>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
