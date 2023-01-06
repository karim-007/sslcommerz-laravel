<?php

namespace Karim007\SslcommerzLaravel;

use Illuminate\Support\ServiceProvider;
use Karim007\SslcommerzLaravel\SslCommerz\SslCommerzNotification;

class SslcommerzLaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . "/../config/sslcommerz.php" => config_path("sslcommerz.php")
        ]);
        $this->publishes([
            __DIR__.'/Controllers/SslCommerzPaymentController.php' => app_path('Http/Controllers/SslCommerzPaymentController.php'),
        ]);

        $this->loadRoutesFrom(__DIR__ . "/routes/sslcmz_route.php");
        $this->loadViewsFrom(__DIR__ . '/Views', 'sslcommerz');
    }

    /**
     * Register application services
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . "/../config/sslcommerz.php", "sslcommerz");

        $this->app->bind("sslcommerznotification", function () {
            return new SslCommerzNotification();
        });
    }
}
