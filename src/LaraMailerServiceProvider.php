<?php

namespace LaraMailer\Sdk;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class LaraMailerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laramailer.php', 'laramailer');

        $this->app->singleton(Client::class, function () {
            return new Client(
                apiToken: (string) config('laramailer.token'),
                domain: (string) config('laramailer.endpoint'),
                timeout: (int) config('laramailer.timeout', 15),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laramailer.php' => config_path('laramailer.php'),
        ], 'laramailer-config');

        $app = $this->app;

        Mail::extend('laramailer', function () use ($app) {
            return new LaraMailerTransport(
                client: $app->make(Client::class),
                accountId: (string) config('laramailer.account_id'),
                trackingEnabled: (bool) config('laramailer.tracking_enabled', true),
            );
        });
    }
}
