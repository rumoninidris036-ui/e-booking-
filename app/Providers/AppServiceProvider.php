<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Notifications\WhatsAppNotificationGateway;
use App\Contracts\Payments\MidtransGateway;
use App\Models\BadmintonField;
use App\Models\Booking;
use App\Policies\BadmintonFieldPolicy;
use App\Policies\BookingPolicy;
use App\Services\Notifications\FlowKirimWhatsAppService;
use App\Services\Payments\Midtrans\MidtransSdkGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MidtransGateway::class, MidtransSdkGateway::class);
        $this->app->bind(WhatsAppNotificationGateway::class, FlowKirimWhatsAppService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(BadmintonField::class, BadmintonFieldPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        RateLimiter::for('payment-create', function (Request $request): Limit {
            $userKey = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(5)->by('payment-create:'.$userKey);
        });

        RateLimiter::for('midtrans-webhook', function (Request $request): Limit {
            return Limit::perMinute(120)->by('midtrans-webhook:'.$request->ip());
        });
    }
}
