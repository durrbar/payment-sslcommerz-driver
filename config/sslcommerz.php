<?php

$apiDomainSSLCZ = env('SSLCOMMERZ_SANDBOX') ? "https://sandbox.sslcommerz.com" : "https://securepay.sslcommerz.com";

return [
    'driver' => \Durrbar\PaymentSSLCommerzDriver\SSLCommerzDriver::class,
    'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
    'apiCredentials' => [
        'store_id' => env("SSLCOMMERZ_STORE_ID"),
        'store_password' => env("SSLCOMMERZ_STORE_PASSWORD"),
    ],
    'apiUrl' => [
        'make_payment' => "/gwprocess/v4/api.php",
        'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
        'order_validate' => "/validator/api/validationserverAPI.php",
        'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
        'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
    ],
    'apiDomain' => $apiDomainSSLCZ,
    'connect_from_localhost' => env("IS_LOCALHOST", false),
    'success_url' => '/success',
    'failed_url' => '/fail',
    'cancel_url' => '/cancel',
    'ipn_url' => '/ipn',
];
