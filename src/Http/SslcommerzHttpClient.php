<?php

namespace Durrbar\PaymentSSLCommerzDriver\Http;

use Durrbar\PaymentSSLCommerzDriver\Config\SslcommerzConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SslcommerzHttpClient
{
    protected $config;

    public function __construct(SslcommerzConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Instance of the HTTP client
     */
    public function client(): PendingRequest
    {
        return Http::withoutVerifying()
            ->baseUrl($this->config->getBaseUrl())
            ->timeout(60);
    }
}
