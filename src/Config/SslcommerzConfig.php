<?php

namespace Durrbar\PaymentSSLCommerzDriver\Config;

class SslcommerzConfig
{
    protected string $sandbox;
    protected string $store_id;
    protected string $store_password;
    protected string $baseUrl;
    protected string $currency;

    protected string $successUrl;
    protected string $failedUrl;
    protected string $cancelUrl;
    protected string $ipnUrl;

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

    protected function setBaseUrl()
    {
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://secure.sslcommerz.com';
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
}
