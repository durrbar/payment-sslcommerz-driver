<?php

namespace Durrbar\PaymentSslcommerzDriver\Payment;

use Durrbar\PaymentSslcommerzDriver\Config\SslcommerzConfig;
use Durrbar\PaymentSslcommerzDriver\Data\RefundResponse;
use Durrbar\PaymentSslcommerzDriver\Data\RefundStatus;
use Durrbar\PaymentSslcommerzDriver\Http\SslcommerzHttpClient;

class SslcommerzRefund
{
    protected SslcommerzConfig $config;

    protected SslcommerzHttpClient $httpClient;

    /**
     * Create a new instance of Sslcommerz.
     */
    public function __construct(SslcommerzConfig $config, SslcommerzHttpClient $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    /**
     * Refund a payment through Sslcommerz.
     */
    public function refundPayment(string $bankTransactionId, int|float $amount, string $reason): RefundResponse
    {
        $response = $this->httpClient->client()->get('/validator/api/merchantTransIDvalidationAPI.php', [
            'store_id' => $this->config->getStoreId(),
            'store_passwd' => $this->config->getStorePassword(),
            'bank_tran_id' => $bankTransactionId,
            'refund_amount' => $amount,
            'refund_remarks' => $reason,
            'format' => 'json',
        ])->json();

        return new RefundResponse($response);
    }

    /**
     * Check the refund status through Sslcommerz.
     */
    public function checkRefundStatus(string $refundRefId): RefundStatus
    {
        $response = $this->httpClient->client()->get('/validator/api/merchantTransIDvalidationAPI.php', [
            'store_id' => $this->config->getStoreId(),
            'store_passwd' => $this->config->getStorePassword(),
            'refund_ref_id' => $refundRefId,
        ])->json();

        return new RefundStatus($response);
    }
}
