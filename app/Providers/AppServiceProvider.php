<?php

namespace App\Providers;

use App\Mail\Transport\ResendApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
        // config('app.env'), nevis env(): env() atgriež null, ja serverī palaists `php artisan config:cache`.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            // Drošības tīkls: produkcijā nekad nerādīt kļūdu detaļas (kodu, .env vērtības),
            // pat ja serverī aizmirsts iestatīt APP_DEBUG=false.
            config(['app.debug' => false]);
        }

        Mail::extend('resend-api', function (array $config): ResendApiTransport {
            return new ResendApiTransport(config('services.resend.key'));
        });
    }
}
