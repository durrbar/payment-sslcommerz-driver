<?php

namespace Durrbar\PaymentSSLCommerzDriver\Providers;

use Illuminate\Support\ServiceProvider;

class SSLCommerzServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sslcommerz.php', 'payment.providers.sslcommerz');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/sslcommerz.php' => config_path('sslcommerz.php'),
        ], 'sslcommerz-config');
    }
}
