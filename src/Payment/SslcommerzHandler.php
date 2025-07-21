<?php

namespace Durrbar\PaymentSslcommerzDriver\Payment;

use Durrbar\PaymentSslcommerzDriver\Config\SslcommerzConfig;
use Durrbar\PaymentSslcommerzDriver\Http\SslcommerzHttpClient;
use Modules\Payment\Drivers\BasePaymentDriver;

class SslcommerzHandler
{
    protected $config;

    protected $httpClient;

    protected $driver;

    public function __construct(SslcommerzConfig $config, SslcommerzHttpClient $httpClient, BasePaymentDriver $driver)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->driver = $driver; // Access to BasePaymentDriver methods
    }

    public function handleIPN(array $data): array
    {
        return $this->driver->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            // Verify the transaction before updating status
            if (true) {
                // Update the order status to 'Complete' if transaction is valid
                $this->driver->updatePaymentStatus($order_details['tran_id'], 'Complete', []);

                return [
                    'status' => 'success',
                    'message' => 'Transaction successfully processed via IPN. Order status updated to Complete.',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.',
            ];
        });
    }

    public function handleSuccess(array $data): array
    {
        return $this->driver->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            if (true) {
                $this->driver->updatePaymentStatus($order_details['tran_id'], 'Processing', []);

                return [
                    'status' => 'success',
                    'message' => 'Transaction is successfully completed.',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.',
            ];
        });
    }

    public function handleFailure(array $data): array
    {
        return $this->driver->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            // Add logic for handling failure, such as logging or sending notifications
            $this->driver->updatePaymentStatus($order_details['tran_id'], 'Failed', []);

            return [
                'status' => 'error',
                'message' => 'Transaction failed. Order status updated to Failed.',
            ];
        });
    }

    public function handleCancel(array $data): array
    {
        return $this->driver->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            // Handle order cancellation, update status to 'Cancelled'
            $this->driver->updatePaymentStatus($data['tran_id'], 'Cancelled', []);

            return [
                'status' => 'error',
                'message' => 'Transaction cancelled. Order status updated to Cancelled.',
            ];
        });
    }
}
