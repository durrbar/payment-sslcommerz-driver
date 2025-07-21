<?php

namespace Durrbar\PaymentSslcommerzDriver;

use Durrbar\PaymentSslcommerzDriver\Config\SslcommerzConfig;
use Durrbar\PaymentSslcommerzDriver\Http\SslcommerzHttpClient;
use Durrbar\PaymentSslcommerzDriver\Payment\SslcommerzHandler;
use Durrbar\PaymentSslcommerzDriver\Payment\SslcommerzPayment;
use Durrbar\PaymentSslcommerzDriver\Payment\SslcommerzRefund;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Drivers\BasePaymentDriver;
use Modules\Payment\Enums\PaymentStatus;

class SslcommerzDriver extends BasePaymentDriver
{
    protected SslcommerzConfig $config;

    protected SslcommerzHttpClient $httpClient;

    protected SslcommerzPayment $payment;

    protected SslcommerzRefund $refund;

    protected SslcommerzHandler $handler;

    public function __construct()
    {
        $this->config = new SslcommerzConfig();
        $this->httpClient = new SslcommerzHttpClient($this->config);
        $this->payment = new SslcommerzPayment($this->config, $this->httpClient);
        $this->refund = new SslcommerzRefund($this->config, $this->httpClient);
        $this->handler = new SslcommerzHandler($this->config, $this->httpClient, $this);
    }

    public function initiatePayment(mixed $payment): array
    {
        // Initiate the payment
        $payment_options = $this->payment->initiatePayment($payment);

        // Return the payment options or an empty array
        return is_array($payment_options) ? $payment_options : [];
    }

    public function handleSuccess(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], PaymentStatus::PENDING->value, function ($payment) use ($data) {
            $payload = [
                'store_id' => $this->store_id,
                'store_password' => $this->store_password,
            ];

            if ($this->payment->validatePayment($payload, $data['tran_id'], $data['amount'], $data['currency'])) {
                $this->updatePayment($payment, [
                    'status' => PaymentStatus::PROCESSING->value,
                ]);

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

        // return $this->handler->handleSuccess($data);
    }

    public function handleFailure(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], PaymentStatus::PENDING->value, function ($order_details) {
            // Add logic for handling failure, such as logging or sending notifications
            $this->updatePaymentStatus($order_details['tran_id'], PaymentStatus::FAILED->value, []);

            return [
                'status' => 'error',
                'message' => 'Transaction failed. Order status updated to Failed.',
            ];
        });

        return $this->handler->handleFailure($data);
    }

    public function handleCancel(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], PaymentStatus::PENDING->value, function ($order_details) use ($data) {
            // Handle order cancellation, update status to 'Cancelled'
            $this->updatePaymentStatus($data['tran_id'], PaymentStatus::CANCELED->value, []);

            return [
                'status' => 'error',
                'message' => 'Transaction cancelled. Order status updated to Cancelled.',
            ];
        });

        return $this->handler->handleCancel($data);
    }

    public function handleIPN(array $data): array
    {
        if (! isset($data['tran_id'])) {
            return ['status' => 'error', 'message' => 'Invalid Data'];
        }

        return $this->processPaymentStatus($data['tran_id'], PaymentStatus::PENDING->value, function ($order_details) use ($data) {
            $payload = [
                'store_id' => $this->store_id,
                'store_password' => $this->store_password,
            ];

            $validation = $this->payment->validatePayment($payload, $data['tran_id'], $order_details->amount, $order_details->currency);

            if ($validation) {
                $this->updatePaymentStatus($data['tran_id'], PaymentStatus::PROCESSING->value, []);

                return [
                    'status' => 'success',
                    'message' => 'Transaction is successfully Completed',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction validation failed',
            ];
        });

        return $this->handler->handleIPN($data);
    }

    public function verifyPayment(string $transactionId): array
    {
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'tran_id' => $transactionId,
        ];

        // Send the request to Sslcommerz to verify the payment
        $response = $this->postRequest('validate', $payload);

        if ($response['status'] !== 'VALID') {
            throw new \Exception('Payment verification failed');
        }

        return [
            'status' => 'success',
            'tran_id' => $response['tran_id'],
            'amount' => $response['amount'],
            'currency' => $response['currency'],
        ];

        return $this->payment->verifyPayment($payload, $transactionId, $amount, $currency);
    }

    public function refundPayment(mixed $payment): array
    {
        $payload = [
            'bank_tran_id' => $payment->bank_tran_id,
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'refund_amount' => $payment->amount,
            'refund_remarks' => 'Customer requested',
        ];

        // Send the request to Sslcommerz to process the refund
        $response = $this->postRequest('refund', $payload);

        if ($response['status'] !== 'SUCCESS') {
            throw new \Exception('Refund failed');
        }

        $payment->update(['refund_ref_id' => $response['refund_ref_id']]);

        return [
            'status' => 'success',
            'tran_id' => $response['tran_id'],
            'refund_amount' => $response['refund_amount'],
        ];

        return $this->refund->refundPayment($payment);
    }

    private function getEndpoint($type = 'payment')
    {
        $baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://secure.sslcommerz.com';

        return $baseUrl.($type === 'payment' ? '/gwprocess/v4/api.php' : '/validator/api/validationserverAPI.php');
    }

    private function postRequest(string $type, array $data): array
    {
        $url = $this->getEndpoint($type);
        $response = Http::post($url, $data);

        if ($response->failed()) {
            throw new \Exception('Failed to communicate with Sslcommerz API: '.$response->body());
        }

        return $response->json();
    }

    public static function validateResponse(array $data): bool
    {
        // Logic to validate IPN (you can add more complex validation based on Sslcommerz documentation)
        return isset($data['status']) && $data['status'] === 'VALID';
    }
}
