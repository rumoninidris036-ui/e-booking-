@props(['disabled' => false])

<div x-data="{ visible: false }" class="relative">
    <input
        @disabled($disabled)
        :type="visible ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'w-full rounded-md border-gray-300 pr-12 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
    >
    <button
        type="button"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 transition hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset"
        @click="visible = ! visible"
        :aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
        :title="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
    >
        <svg x-show="! visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z" />
            <circle cx="12" cy="12" r="2.75" />
        </svg>
        <svg x-cloak x-show="visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.6 5.4A10.8 10.8 0 0 1 12 5.25c6.25 0 9.75 6.75 9.75 6.75a16.7 16.7 0 0 1-3.09 3.76M6.2 6.2A16.6 16.6 0 0 0 2.25 12S5.75 18.75 12 18.75c1.2 0 2.3-.25 3.3-.68" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
        </svg>
    </button>
</div>
