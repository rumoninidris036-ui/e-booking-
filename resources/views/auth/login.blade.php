<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SmashCourt') }} | Masuk</title>

        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            background: '#111316',
                            'surface-container': '#1e2023',
                            'surface-container-low': '#1a1c1f',
                            'surface-container-high': '#282a2d',
                            'surface-container-lowest': '#0c0e11',
                            'surface-variant': '#333538',
                            'on-background': '#e2e2e6',
                            'on-surface': '#e2e2e6',
                            'on-surface-variant': '#c2c6d8',
                            'secondary-fixed': '#c3f400',
                            'secondary-fixed-dim': '#abd600',
                            outline: '#8c90a1',
                            'outline-variant': '#424656',
                            'primary-container': '#0066ff',
                        },
                        borderRadius: {
                            DEFAULT: '0.25rem',
                            lg: '0.5rem',
                            xl: '0.75rem',
                            full: '9999px',
                        },
                        spacing: {
                            base: '8px',
                            'margin-mobile': '20px',
                            'margin-desktop': '40px',
                        },
                        fontFamily: {
                            'label-bold': ['Inter', 'sans-serif'],
                            'headline-lg': ['Montserrat', 'sans-serif'],
                            'headline-xl-mobile': ['Montserrat', 'sans-serif'],
                            'body-md': ['Inter', 'sans-serif'],
                        },
                        fontSize: {
                            'label-bold': ['14px', { lineHeight: '1', letterSpacing: '0.05em', fontWeight: '600' }],
                            'headline-lg': ['32px', { lineHeight: '1.2', fontWeight: '700' }],
                            'headline-xl-mobile': ['32px', { lineHeight: '1.2', fontWeight: '800' }],
                            'body-md': ['16px', { lineHeight: '1.5', fontWeight: '400' }],
                        },
                    },
                },
            };
        </script>

        <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
        </style>
    </head>
    <body class="overflow-x-hidden bg-background font-body-md text-on-background">
        <header class="fixed top-0 z-50 flex h-16 w-full items-center justify-between border-b border-white/10 bg-background/80 px-[20px] backdrop-blur-md md:px-[40px]">
            <a href="{{ url('/') }}" class="font-headline-lg text-2xl font-extrabold uppercase tracking-tighter text-secondary-fixed">SmashCourt</a>
            <nav class="hidden items-center gap-8 md:flex">
                <a class="font-label-bold text-label-bold text-on-surface-variant transition-colors hover:text-secondary-fixed" href="{{ url('/#arenas') }}">Lapangan</a>
                <a class="font-label-bold text-label-bold text-on-surface-variant transition-colors hover:text-secondary-fixed" href="{{ url('/#benefits') }}">Keunggulan</a>
                <a class="font-label-bold text-label-bold text-on-surface-variant transition-colors hover:text-secondary-fixed" href="{{ url('/#cta') }}">Booking</a>
            </nav>
            <div class="flex items-center gap-4">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="rounded-full bg-secondary-fixed px-6 py-2 font-label-bold text-label-bold uppercase text-black transition-transform active:scale-95">
                        Daftar
                    </a>
                @endif
            </div>
        </header>

        <main class="relative flex min-h-screen items-center justify-center overflow-hidden pt-16">
            <div class="pointer-events-none absolute inset-0 z-0">
                <div class="absolute right-[-10%] top-[-10%] h-[80%] w-[60%] rounded-full bg-primary-container/10 blur-[120px]"></div>
                <div class="absolute bottom-[-10%] left-[-10%] h-[70%] w-[50%] rounded-full bg-secondary-fixed/5 blur-[100px]"></div>
                <div class="absolute inset-0 opacity-20 mix-blend-overlay">
                    <img
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuCSK4YYKs4i82iEv91bUvM0UEgE64X7cKQ7ZbT4RnlykP9WC-DzLT_pL3VAXvqX5Ja0CgHYNSxSLVfvXLhYtTaRqDQHoswNp52V4wc77ZJ8fXLCJZXVhbBZ94kxDhiftdScYshqLVW6pXcwlK8wdeQKnQZqtTa95RQ6CBHniqBStx2y_aZb7x5EqMVjGLc0dS8_QIWHe65sTKN7GLQ5ZqHvfVIDAFRX3_MPsC49-nCnzxrk5asxHovqJ3Y_ehSaxpiSPzNDFZDExE1G"
                        alt=""
                        class="h-full w-full object-cover"
                    >
                </div>
            </div>

            <div class="relative z-10 my-12 w-full max-w-[480px] px-[20px]">
                <div class="group relative overflow-hidden rounded-xl border border-white/5 bg-surface-container p-8 shadow-2xl backdrop-blur-sm md:p-10">
                    <div class="absolute -right-16 -top-16 h-32 w-32 bg-secondary-fixed/10 blur-3xl transition-all duration-500 group-hover:bg-secondary-fixed/20"></div>

                    <div class="mb-10 text-center">
                        <h1 class="mb-2 font-headline-lg text-headline-lg tracking-tight md:text-headline-xl-mobile">Selamat datang kembali</h1>
                        <p class="text-on-surface-variant">Masuk dulu, lalu lanjut pilih lapangan yang kamu butuhkan.</p>
                    </div>

                    <x-auth-session-status class="mb-6 rounded-lg border border-secondary-fixed/20 bg-secondary-fixed/10 px-4 py-3 text-sm text-secondary-fixed" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        <div class="space-y-2">
                            <label for="email" class="ml-1 block font-label-bold text-label-bold uppercase text-on-surface-variant">Email</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-outline">mail</span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="nama@email.com"
                                    class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-4 pl-12 pr-4 text-on-surface outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-secondary-fixed"
                                >
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-300" />
                        </div>

                        <div class="space-y-2">
                            <label for="password" class="ml-1 block font-label-bold text-label-bold uppercase text-on-surface-variant">Kata Sandi</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-outline">lock</span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-lg border border-outline-variant bg-surface-container-low py-4 pl-12 pr-4 text-on-surface outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-secondary-fixed"
                                >
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-300" />
                        </div>

                        <div class="flex items-center justify-between font-label-bold text-label-bold">
                            <label for="remember_me" class="group flex cursor-pointer items-center gap-2">
                                <input id="remember_me" name="remember" type="checkbox" class="rounded border-outline-variant bg-surface-container-low text-secondary-fixed focus:ring-secondary-fixed focus:ring-offset-background">
                                <span class="text-on-surface-variant transition-colors group-hover:text-on-surface">Ingat saya</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-secondary-fixed transition-colors hover:text-secondary-fixed-dim">
                                    Lupa kata sandi?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-secondary-fixed py-4 font-label-bold text-label-bold uppercase tracking-widest text-black shadow-[0_4px_0_0_#abd600] transition-all active:translate-y-[2px] active:shadow-none">
                            Masuk ke Dashboard
                            <span class="material-symbols-outlined">bolt</span>
                        </button>
                    </form>

                    <div class="relative my-8 flex items-center justify-center">
                        <div class="absolute w-full border-t border-outline-variant"></div>
                        <span class="relative bg-surface-container px-4 font-label-bold text-label-bold uppercase text-outline">Atau lanjut dengan</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" data-dev-toast="Google login sedang dikembangkan." class="flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-surface-container-low py-3 font-label-bold text-label-bold transition-colors hover:bg-surface-container-high">
                            <img alt="Google" class="h-5 w-5" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBQsyiWnKhq5D783i_mqwMVUg-RPqI2YmIVCleXFun6c8cNAMPLZYkwvJiuJsRzDR7Xrb2kz-UvoU_gdmAwPNv_jWnEnG3pV3USHXdjU5wBrza0j3oj6PbWT7sD5NEg68eBdj8T7_agQm3OTmVvPfcOecYiUM0tIumoC_UAcq1qJQVS0dEL9B3HWnQXr68KN3lCaMW--fo2N_SpZTxVtcK5bvCXqChHHRVNd7E58o5QPkDJk3ejiYbe4-N08F48tYHhEuBZiC8W0Sig">
                            Google
                        </button>
                        <button type="button" data-dev-toast="Apple login sedang dikembangkan." class="flex items-center justify-center gap-2 rounded-lg border border-outline-variant bg-surface-container-low py-3 font-label-bold text-label-bold transition-colors hover:bg-surface-container-high">
                            <img alt="Apple" class="h-5 w-5 invert" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBy4JeZJwdO7yJUwOacH0aySqduQJ5fqC6R54TwWZDb5cr8v9enKpuemF7oB5E5wTa9mS3LWUgd1czgO6Ta7c2FRn5X52TEYwlDoL6A7up3b01fmNbN1deGD9PyYgJuScKAwpXPwLag8Vr6yd3FNCH5KRQbtJGHExEcM-zfG1rctNFozz_cprlIbommDGomt7twUVS8K1b9xDda4LFLdqZBa_5ZCKO4SvJ2sW6gDNoXm9EBO3HwraixMkYbeyEXbQpRq2Cph9uPiPNo">
                            Apple
                        </button>
                    </div>

                    <div class="mt-10 text-center">
                        <p class="text-on-surface-variant">
                            Belum punya akun?
                            <a href="{{ route('register') }}" class="ml-2 font-label-bold text-label-bold uppercase text-secondary-fixed decoration-2 underline-offset-4 hover:underline">
                                Daftar
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <div id="dev-toast" class="pointer-events-none fixed bottom-6 right-6 z-[60] hidden rounded-lg border border-secondary-fixed/30 bg-surface-container px-4 py-3 text-sm text-on-surface shadow-xl"></div>

        <footer class="flex w-full flex-col items-center justify-between gap-4 bg-surface-container-lowest px-[20px] py-8 md:flex-row md:px-[40px]">
            <div class="font-headline-lg text-2xl text-secondary-fixed">SmashCourt</div>
            <div class="text-body-md text-outline">© {{ now()->year }} SmashCourt. Booking lapangan jadi lebih mudah.</div>
            <div class="flex gap-6">
                <a class="text-body-md text-outline transition-colors hover:text-secondary-fixed-dim" href="{{ url('/') }}">Ketentuan</a>
                <a class="text-body-md text-outline transition-colors hover:text-secondary-fixed-dim" href="{{ url('/') }}">Privasi</a>
                <a class="text-body-md text-outline transition-colors hover:text-secondary-fixed-dim" href="{{ url('/') }}">Bantuan</a>
            </div>
        </footer>

        <script>
            const toast = document.getElementById('dev-toast');
            let toastTimer;

            document.querySelectorAll('[data-dev-toast]').forEach((button) => {
                button.addEventListener('click', () => {
                    toast.textContent = button.dataset.devToast;
                    toast.classList.remove('hidden');

                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => {
                        toast.classList.add('hidden');
                    }, 2400);
                });
            });
        </script>
    </body>
</html>
