<?php

namespace App\Providers;

use App\Listeners\MergeGuestCartOnLogin;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\Currency\CurrencyConverter;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\ExchangeRateService;
use App\Services\Currency\GeoCurrencyDetector;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsService;
use App\Support\Money;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(CurrencyService::class);
        $this->app->singleton(ExchangeRateService::class);
        $this->app->singleton(CurrencyConverter::class);
        $this->app->singleton(GeoCurrencyDetector::class);

        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        /*
         | Listeners are registered explicitly rather than through discovery, so
         | the wiring is greppable and no directory scan happens per request.
         */
        Event::listen(Login::class, MergeGuestCartOnLogin::class);

        Money::configure(
            currency: config('shop.currency.code'),
            decimals: config('shop.currency.decimals'),
            symbols: config('shop.currency.symbols'),
        );

        /*
         | Fail loudly in development when a relationship is used without being
         | eager loaded, so N+1 queries surface during tests rather than in
         | production traffic.
         */
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
