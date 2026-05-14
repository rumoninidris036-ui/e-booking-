<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-sm font-medium uppercase tracking-wide text-emerald-600">{{ __('Authenticated') }}</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h3>
                        <p class="mt-3 text-sm text-slate-600">
                            {{ __('You are signed in as :role and ready to continue Sprint 1 verification.', ['role' => $roleLabel]) }}
                        </p>
                    </div>
                </div>

                <div class="bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <p class="text-sm uppercase tracking-wide text-slate-300">{{ __('Current Role') }}</p>
                        <p class="mt-3 text-3xl font-semibold capitalize">{{ $roleLabel }}</p>
                        <p class="mt-3 text-sm text-slate-300">{{ __('Role middleware is active for admin and owner routes.') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Sprint 1 Status') }}</h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-sm font-medium text-slate-500">{{ __('Authentication') }}</p>
                            <p class="mt-2 text-sm text-slate-700">{{ __('Register, login, logout, forgot password, and auth middleware are scaffolded with Laravel Breeze.') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-sm font-medium text-slate-500">{{ __('Role Management') }}</p>
                            <p class="mt-2 text-sm text-slate-700">{{ __('Roles admin, owner, and customer are seeded and enforced via Spatie middleware aliases.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
