<?php

namespace Durrbar\PaymentSSLCommerzDriver;

use Illuminate\Support\Facades\Http;
use Modules\Order\Models\Order;
use Modules\Payment\Drivers\BasePaymentDriver;
use Modules\Payment\Drivers\SslCommerz\SslCommerzNotification;

class SSLCommerzDriver extends BasePaymentDriver
{
    private $store_id;
    private $store_password;
    private $sandbox_mode;

    public function __construct()
    {
        // Directly initialize configuration values in the constructor
        $this->store_id = config('payment.providers.sslcommerz.apiCredentials.store_id');
        $this->store_password = config('payment.providers.sslcommerz.apiCredentials.store_password');
        $this->sandbox_mode = config('payment.providers.sslcommerz.sandbox', true);
    }

    public function initiatePayment(mixed $payment): array
    {
        dd($payment);
        // Prepare the complete transaction data
        $post_data = $this->preparePaymentData($payment);

        // Insert or update the order status as "Pending"
        $this->updatePaymentStatus($post_data['tran_id'], 'Pending', $post_data);

        // Instantiate the SSLCommerz notification service
        $sslc = new SSLCommerzNotification();

        // Initiate the payment
        $payment_options = $sslc->makePayment($post_data, 'hosted');

        // Return the payment options or an empty array
        return is_array($payment_options) ? $payment_options : [];
    }

    public function handleSuccess(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            $sslc = new SSLCommerzNotification();
            if ($sslc->orderValidate($data, $data['tran_id'], $data['amount'], $data['currency'])) {
                $this->updatePaymentStatus($order_details['tran_id'], 'Processing', []);
                return [
                    'status' => 'success',
                    'message' => 'Transaction is successfully completed.'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.'
            ];
        });
    }

    public function handleFailure(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            // Add logic for handling failure, such as logging or sending notifications
            $this->updatePaymentStatus($order_details['tran_id'], 'Failed', []);

            return [
                'status' => 'error',
                'message' => 'Transaction failed. Order status updated to Failed.'
            ];
        });
    }

    public function handleCancel(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            // Handle order cancellation, update status to 'Cancelled'
            $this->updatePaymentStatus($data['tran_id'], 'Cancelled', []);

            return [
                'status' => 'error',
                'message' => 'Transaction cancelled. Order status updated to Cancelled.'
            ];
        });
    }

    public function handleIPN(array $data): array
    {
        if (!isset($data['tran_id'])) {
            return ['status' => 'error', 'message' => 'Invalid Data'];
        }

        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            $sslc = new SSLCommerzNotification();
            $validation = $sslc->orderValidate($data, $data['tran_id'], $order_details->amount, $order_details->currency);

            if ($validation) {
                $this->updatePaymentStatus($data['tran_id'], 'Processing', []);
                return [
                    'status' => 'success',
                    'message' => 'Transaction is successfully Completed'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction validation failed'
            ];
        });
    }

    public function verifyPayment(string $transactionId): array
    {
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'tran_id' => $transactionId,
        ];

        // Send the request to SSLCommerz to verify the payment
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
    }

    public function refundPayment(mixed $payment): array
    {
        $payload = [
            'bank_tran_id' => $payment->bank_tran_id,
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'refund_amount' => $payment->amount,
            'refund_remarks' => 'Customer requested'
        ];

        // Send the request to SSLCommerz to process the refund
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
    }

    private function getEndpoint($type = 'payment')
    {
        $baseUrl = $this->sandbox_mode
            ? 'https://sandbox.sslcommerz.com'
            : 'https://secure.sslcommerz.com';

        return $baseUrl . ($type === 'payment' ? '/gwprocess/v4/api.php' : '/validator/api/validationserverAPI.php');
    }

    private function postRequest(string $type, array $data): array
    {
        $url = $this->getEndpoint($type);
        $response = Http::post($url, $data);

        if ($response->failed()) {
            throw new \Exception('Failed to communicate with SSLCommerz API: ' . $response->body());
        }

        return $response->json();
    }

    public static function validateResponse(array $data): bool
    {
        // Logic to validate IPN (you can add more complex validation based on SSLCommerz documentation)
        return isset($data['status']) && $data['status'] === 'VALID';
    }

    private function preparePaymentData(mixed $payment): array
    {
        // Load shipping address if not already loaded
        $payment->loadMissing('shippingAddress');

        $order = $payment->order;
        $customer = $order->customer;

        return [
            'total_amount' => $payment->amount,
            'currency' => $payment->currency,
            'tran_id' => $payment->tran_id,

            // Customer Information
            'cus_name' => $customer->name,
            'cus_email' => $customer->email,
            'cus_add1' => $customer->address_line_1,
            'cus_add2' => $customer->address_line_2,
            'cus_city' => $customer->city,
            'cus_state' => $customer->state,
            'cus_postcode' => $customer->postal_code,
            'cus_country' => $customer->country,
            'cus_phone' => $customer->mobile,
            'cus_fax' => $customer->phone ?? '',

            // Shipping Information
            'ship_name' => $order->shippingAddress->recipient_name ?? $customer->name,
            'ship_add1' => $order->shippingAddress->address_line_1,
            'ship_add2' => $order->shippingAddress->address_line_2,
            'ship_city' => $order->shippingAddress->city,
            'ship_state' => $order->shippingAddress->state,
            'ship_postcode' => $order->shippingAddress->postal_code,
            'ship_country' => $order->shippingAddress->country,

            // Product Information
            'shipping_method' => $order->shipping_method ?? 'Courier',
            'product_name' => $this->getProductNames($order),
            'product_category' => $this->getProductCategories($order),
            'product_profile' => $this->getProductProfile($order),

            // Additional Fields
            'value_a' => $order->id,
            'value_b' => $payment->id,
            'value_c' => config('app.name'),
            'value_d' => $payment->created_at->toIso8601String(),
        ];
    }

    private function getProductNames(Order $order): string
    {
        return $order->items->pluck('name')->implode(', ');
    }

    private function getProductCategories(Order $order): string
    {
        return $order->items->pluck('category.name')->unique()->implode(', ');
    }

    private function getProductProfile(Order $order): string
    {
        return $order->items->every(fn ($item) => $item->is_physical)
            ? 'physical-goods'
            : 'digital-goods';
    }
}
