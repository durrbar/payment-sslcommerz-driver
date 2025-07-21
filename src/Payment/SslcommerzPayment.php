<?php

namespace Durrbar\PaymentSslcommerzDriver\Payment;

use Durrbar\PaymentSslcommerzDriver\Config\SslcommerzConfig;
use Durrbar\PaymentSslcommerzDriver\Data\PaymentResponse;
use Durrbar\PaymentSslcommerzDriver\Http\SslcommerzHttpClient;
use Exception;
use Modules\Order\Models\Order;

class SslcommerzPayment
{
    /**
     * Configuration object for Sslcommerz integration.
     */
    protected SslcommerzConfig $config;

    /**
     * HTTP client for making API requests to Sslcommerz.
     */
    protected SslcommerzHttpClient $httpClient;

    /**
     * Payload data to be sent with the API request.
     */
    protected array $data = [];

    /**
     * Constructor to initialize the Sslcommerz payment integration.
     *
     * @param  SslcommerzConfig  $config  Configuration object containing store credentials and callback URLs.
     * @param  SslcommerzHttpClient  $httpClient  HTTP client for making API requests.
     */
    public function __construct(SslcommerzConfig $config, SslcommerzHttpClient $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    /**
     * Initiates a payment request to Sslcommerz.
     *
     * This method prepares the necessary payload data, sends the request to the Sslcommerz API,
     * and returns a `PaymentResponse` object containing the API response.
     *
     * @param  mixed  $payment  Payment object containing order, customer, and shipping details.
     * @return PaymentResponse Response object containing the result of the payment request.
     *
     * @throws Exception If the API response is invalid or empty.
     */
    public function initiatePayment(mixed $payment): PaymentResponse
    {
        try {
            // Prepare the required parameters for the payment request.
            $this->setParams($payment);

            // Initialize authentication data (store ID and password).
            $this->setAuthenticationInfo();

            // Send the payment request to the Sslcommerz API.
            $response = $this->httpClient->client()
                ->asForm()
                ->post('/gwprocess/v4/api.php', $this->data)
                ->json();

            // Validate the API response.
            if (empty($response)) {
                throw new Exception('Empty response from Sslcommerz API.');
            }

            return new PaymentResponse($response);
        } catch (Exception $e) {
            // Rethrow the exception with a descriptive error message.
            throw new Exception('Payment failed: '.$e->getMessage());
        }
    }

    /**
     * Validates a payment using the Sslcommerz validation API.
     *
     * This method checks whether the payment transaction is valid by verifying the transaction ID,
     * amount, and currency against the response from the Sslcommerz validation API.
     *
     * @param  array  $payload  Validation payload received from Sslcommerz.
     * @param  string  $transactionId  The transaction ID to validate.
     * @param  float  $amount  The transaction amount to validate.
     * @param  string  $currency  The transaction currency (default: 'BDT').
     * @return bool True if the payment is valid; otherwise, false.
     *
     * @throws Exception If validation fails due to missing or invalid data.
     */
    public function validatePayment(array $payload, string $transactionId, float $amount, string $currency = 'BDT'): bool
    {
        // Ensure the validation ID is present in the payload.
        if (empty($payload['val_id'])) {
            throw new Exception('Validation ID is missing.');
        }

        // Call the Sslcommerz validation API to verify the transaction.
        $response = $this->httpClient->client()->get('/validator/api/validationserverAPI.php', [
            'val_id' => $payload['val_id'],
            'store_id' => $this->config->getStoreId(),
            'store_passwd' => $this->config->getStorePassword(),
            'format' => 'json',
        ])->json();

        // Validate the API response.
        if (empty($response)) {
            throw new Exception('Empty response from Sslcommerz validation API.');
        }

        // Check for required fields and transaction status.
        if (! isset($response['status'], $response['tran_id'], $response['amount']) || $response['status'] === 'INVALID_TRANSACTION') {
            throw new Exception('Invalid transaction status.');
        }

        // Verify the transaction ID.
        if (trim($transactionId) !== trim($response['tran_id'])) {
            throw new Exception('Transaction ID mismatch.');
        }

        // Validate the transaction amount based on the currency.
        if ($currency === 'BDT') {
            return abs($amount - $response['amount']) < 1;
        }

        return trim($currency) === trim($response['currency_type']) && abs($amount - $response['currency_amount']) < 1;
    }

    /**
     * Verifies the hash signature from an Sslcommerz response.
     *
     * This method ensures the integrity of the response by comparing the computed hash
     * with the provided hash (`verify_sign`) from Sslcommerz.
     *
     * @param  array  $data  Response data containing the hash signature and verification key.
     * @return bool True if the hash verification is successful; otherwise, false.
     *
     * @throws Exception If the hash verification fails or required data is missing.
     */
    public function verifyHash(array $data): bool
    {
        // Ensure the hash signature and verification key are present.
        if (empty($data['verify_sign']) || empty($data['verify_key'])) {
            throw new Exception('Verification data is incomplete.');
        }

        // Extract predefined keys from the verification key.
        $preDefinedKeys = explode(',', $data['verify_key']);
        $dataToHash = ['store_passwd' => md5($this->config->getStorePassword())];

        // Add predefined keys and their values to the data to be hashed.
        foreach ($preDefinedKeys as $key) {
            $dataToHash[$key] = $data[$key] ?? '';
        }

        // Sort the data alphabetically by key.
        ksort($dataToHash);

        // Build the hash string by concatenating key-value pairs.
        $hashString = '';
        foreach ($dataToHash as $key => $value) {
            $hashString .= "$key=$value&";
        }
        $hashString = rtrim($hashString, '&');

        // Compare the computed hash with the provided hash signature.
        if (md5($hashString) !== $data['verify_sign']) {
            throw new Exception('Hash verification failed.');
        }

        return true;
    }

    /**
     * Prepares all required parameters for the payment request.
     *
     * This method combines order, customer, shipping, and product information into the payload data.
     *
     * @param  mixed  $payment  Payment object containing order, customer, and shipping details.
     */
    private function setParams(mixed $payment): void
    {
        // Extract order and customer details from the payment object.
        $order = $payment->order;
        $customer = $order->customer;

        // Set required information such as callback URLs.
        $this->setRequiredInfo();

        // Merge customer information into the payload data.
        $this->mergeData($this->setCustomerInfo($customer));

        // Merge shipping information into the payload data.
        $this->mergeData($this->setShipmentInfo($order, $customer));

        // Merge product information into the payload data.
        $this->mergeData($this->setProductInfo($payment, $order));
    }

    /**
     * Sets the authentication information (store ID and password) for the API request.
     */
    private function setAuthenticationInfo(): void
    {
        $this->data['store_id'] = $this->config->getStoreId();
        $this->data['store_passwd'] = $this->config->getStorePassword();
    }

    /**
     * Sets the required callback URLs for the payment request.
     *
     * These URLs include success, failure, cancel, and IPN (Instant Payment Notification) URLs.
     */
    private function setRequiredInfo(): void
    {
        $this->data['success_url'] = $this->config->getSuccessUrl();
        $this->data['fail_url'] = $this->config->getFailedUrl();
        $this->data['cancel_url'] = $this->config->getCancelUrl();
        $this->data['ipn_url'] = $this->config->getIpnUrl();
        $this->data['multi_card_name'] = null; // Optional: Specify payment gateways.
    }

    /**
     * Sets the customer details for the payment request.
     *
     * @param  mixed  $customer  Customer object containing name, email, address, and contact details.
     * @return array Array of customer-related data to be included in the payload.
     */
    private function setCustomerInfo(mixed $customer): array
    {
        return [
            'cus_name' => $customer->name,
            'cus_email' => $customer->email,
            'cus_add1' => $customer->address_line_1,
            'cus_add2' => $customer->address_line_2,
            'cus_city' => $customer->city,
            'cus_state' => $customer->state,
            'cus_postcode' => $customer->postal_code,
            'cus_country' => $customer->country,
            'cus_phone' => $customer->phone,
            'cus_fax' => $customer->phone ?? '',
        ];
    }

    /**
     * Sets the shipping information for the payment request.
     *
     * @param  mixed  $order  Order object containing shipping details.
     * @param  mixed  $customer  Customer object (used as a fallback for missing shipping details).
     * @return array Array of shipping-related data to be included in the payload.
     */
    private function setShipmentInfo(mixed $order, mixed $customer): array
    {
        return [
            'shipping_method' => $order->shipping_method ?? 'Courier',
            'num_of_item' => $order->items_count,
            'ship_name' => $order->shippingAddress->recipient_name ?? $customer->name,
            'ship_add1' => $order->shippingAddress->address_line_1,
            'ship_city' => $order->shippingAddress->city,
            'ship_state' => $order->shippingAddress->state,
            'ship_postcode' => $order->shippingAddress->postal_code,
            'ship_country' => $order->shippingAddress->country,
        ];
    }

    /**
     * Sets the product details for the payment request.
     *
     * @param  mixed  $payment  Payment object containing total amount, currency, and transaction ID.
     * @param  mixed  $order  Order object containing product details.
     * @return array Array of product-related data to be included in the payload.
     */
    private function setProductInfo(mixed $payment, mixed $order): array
    {
        return [
            'total_amount' => $payment->amount,
            'currency' => $payment->currency,
            'tran_id' => $payment->tran_id,
            'product_name' => $this->getProductNames($order),
            'product_category' => $this->getProductCategories($order),
            'product_profile' => $this->setProductProfile($order), // Set the product profile
        ];
    }

    /**
     * Retrieves the names of all products in the order.
     *
     * @param  Order  $order  Order object containing product details.
     * @return string Comma-separated list of product names.
     */
    private function getProductNames(Order $order): string
    {
        return $order->items->pluck('name')->implode(', ');
    }

    /**
     * Retrieves the categories of all products in the order.
     *
     * @param  Order  $order  Order object containing product details.
     * @return string Comma-separated list of unique product categories.
     */
    private function getProductCategories(Order $order): string
    {
        return $order->items->pluck('category.name')->unique()->implode(', ');
    }

    /**
     * Determines the product profile based on the type of items in the order.
     *
     * Possible values:
     *     - 'general': Default value for unspecified categories.
     *     - 'physical-goods': All items are physical products.
     *     - 'non-physical-goods': At least one item is a digital or virtual product.
     *     - 'airline-tickets': For transactions involving airline ticket purchases.
     *     - 'travel-vertical': For transactions related to travel services.
     *     - 'telecom-vertical': For transactions related to telecom services.
     *
     * @param  Order  $order  Order object containing product details.
     * @return string Product profile ('physical-goods', 'non-physical-goods', etc.).
     */
    private function setProductProfile(Order $order): string
    {
        // Determine the product profile based on the items in the order.
        if ($order->items->contains('category.name', 'Airline Tickets')) {
            return 'airline-tickets';
        } elseif ($order->items->contains('category.name', 'Travel Services')) {
            return 'travel-vertical';
        } elseif ($order->items->contains('category.name', 'Telecom Services')) {
            return 'telecom-vertical';
        } elseif ($order->items->every(fn ($item) => $item->is_physical)) {
            return 'physical-goods';
        } else {
            return 'non-physical-goods';
        }
    }

    /**
     * Merges additional data into the payload.
     *
     * @param  array  $additionalData  Data to be merged into the existing payload.
     */
    private function mergeData(array $additionalData): void
    {
        $this->data = array_merge($this->data, $additionalData);
    }
}
