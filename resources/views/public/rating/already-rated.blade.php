<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'SMASHCOURT') }} | Rating Already Submitted</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100">
        <main class="mx-auto flex min-h-screen max-w-4xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full rounded-3xl border border-white/10 bg-slate-900 p-8 shadow-2xl sm:p-10">
                <div class="mx-auto max-w-2xl text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-400/15 text-2xl text-amber-300">!</div>
                    <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Rating already submitted</h1>
                    <p class="mt-4 text-sm leading-7 text-slate-400 sm:text-base">
                        Link signed ini sudah pernah dipakai untuk booking {{ $booking->booking_code }}. Satu booking hanya bisa mengirim satu rating.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-left">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Court</p>
                            <p class="mt-2 font-semibold text-white">{{ $field->name }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-left">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Score</p>
                            <p class="mt-2 font-semibold text-white">{{ $rating->score }}/5</p>
                        </div>
                    </div>

                    @if ($rating->comment)
                        <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-left text-sm leading-6 text-slate-300">
                            {{ $rating->comment }}
                        </div>
                    @endif

                    <a href="{{ route('public.fields.show', ['slug' => $field->slug]) }}" class="mt-8 inline-flex items-center justify-center rounded-2xl border border-white/10 px-6 py-4 text-sm font-bold uppercase tracking-[0.2em] text-white transition-colors hover:bg-white/5">
                        Back To Court Detail
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>
