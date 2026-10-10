<?php

namespace App\Providers;

use App\Mail\Transport\ResendApiTransport;
use Illuminate\Support\Facades\Mail;
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
        Mail::extend('resend-api', function (array $config): ResendApiTransport {
            return new ResendApiTransport(config('services.resend.key'));
        });
    }
}
