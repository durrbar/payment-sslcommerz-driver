<?php

return [
    'driver' => \Durrbar\PaymentSslcommerzDriver\SslcommerzDriver::class,
    /**
     * Enable/Disable Sandbox mode
     */
    'sandbox' => env('SSLCOMMERZ_SANDBOX', true),    

    /**
     * The API credentials given from Sslcommerz
     */
    'store' => [
        'id' => env('SSLCOMMERZ_STORE_ID'),
        'password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'currency' => env('SSLCOMMERZ_STORE_CURRENCY', 'BDT'),
    ],

    /**
     * Route names for success/failure/cancel
     */
    'route' => [
        'success' => '/success',
        'failure' => '/fail',
        'cancel' => '/cancel',
        'ipn' => '/ipn',
    ],

    /**
     * Product profile required from Sslc
     * By default it is "general"
     *
     * AVAILABLE PROFILES
     *  general
     *  physical-goods
     *  non-physical-goods
     *  airline-tickets
     *  travel-vertical
     *  telecom-vertical
     */
    'product_profile' => 'general',
];
