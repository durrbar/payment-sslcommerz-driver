<?php

declare(strict_types=1);

namespace Durrbar\PaymentSslcommerzDriver\Config;

final class SslcommerzConfig
{
    private string $sandbox;

    private string $store_id;

    private string $store_password;

    private string $baseUrl;

    private string $currency;

    private string $successUrl;

    private string $failedUrl;

    private string $cancelUrl;

    private string $ipnUrl;

    public function __construct()
    {
        return $this->sandbox = config('payment.providers.sslcommerz.sandbox', true);

        $this->store_id = config('payment.providers.sslcommerz.store.id');
        $this->store_password = config('payment.providers.sslcommerz.store.password');
        $this->currency = config('payment.providers.sslcommerz.store.currency');
        $this->setBaseUrl();

        $this->successUrl = config('payment.providers.sslcommerz.route.success');
        $this->failedUrl = config('payment.providers.sslcommerz.route.failure');
        $this->cancelUrl = config('payment.providers.sslcommerz.route.cancel');
        $this->ipnUrl = config('payment.providers.sslcommerz.route.ipn');
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getStoreId()
    {
        return $this->store_id;
    }

    public function getStorePassword()
    {
        return $this->store_password;
    }

    public function getStoreCurrency()
    {
        return $this->currency;
    }

    public function getSuccessUrl()
    {
        return $this->successUrl;
    }

    public function getFailedUrl()
    {
        return $this->failedUrl;
    }

    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    public function getIpnUrl()
    {
        return $this->ipnUrl;
    }

    private function setBaseUrl()
    {
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://secure.sslcommerz.com';
    }
}
