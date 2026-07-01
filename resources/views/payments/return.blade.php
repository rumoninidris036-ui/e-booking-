<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SmashCourt | Pembaruan Pembayaran</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-[#111316] text-white">
        <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-10">
            <div class="w-full rounded-3xl border border-white/10 bg-[#1a1c1f] p-8 shadow-2xl shadow-black/20">
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-[#c3f400]">Pembaruan Pembayaran</p>
                <h1 class="mt-3 text-3xl font-extrabold uppercase tracking-tight">Status Terkini</h1>
                <p class="mt-4 text-sm leading-7 text-slate-300">
                    Halaman ini mengirim pembaruan status pembayaran ke tab utama SmashCourt. Kamu bisa kembali ke tab sebelumnya untuk melihat hasil terbaru.
                </p>

                <div class="mt-8 rounded-2xl border border-white/10 bg-[#1e2023] px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Status Pembayaran</p>
                    <p class="mt-2 text-2xl font-bold uppercase text-white">{{ $payment->status }}</p>
                </div>

                <a
                    href="{{ $paymentUrl }}"
                    class="mt-6 block w-full rounded-2xl bg-[#c3f400] px-6 py-4 text-center text-sm font-bold uppercase tracking-[0.18em] text-black"
                >
                    Kembali ke Halaman Pembayaran
                </a>
            </div>
        </main>

        <script>
            localStorage.setItem('midtrans-payment-update', JSON.stringify({
                paymentId: {{ $payment->id }},
                orderId: @json($payment->order_id),
                status: @json($payment->status),
                happenedAt: Date.now(),
            }));
        </script>
    </body>
</html>
