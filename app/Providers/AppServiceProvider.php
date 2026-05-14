<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\BadmintonField;
use App\Policies\BadmintonFieldPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(BadmintonField::class, BadmintonFieldPolicy::class);
    }
}
