
---

# **Sslcommerz Payment Driver for Laravel**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/durrbar/payment-sslcommerz-driver.svg?style=flat-square)](https://packagist.org/packages/durrbar/payment-sslcommerz-driver)
[![Total Downloads](https://img.shields.io/packagist/dt/durrbar/payment-sslcommerz-driver.svg?style=flat-square)](https://packagist.org/packages/durrbar/payment-sslcommerz-driver)

A Laravel package to integrate the **Sslcommerz Payment Gateway** seamlessly into your application. This package supports tokenized payments, refunds, transaction verification, and handling callbacks (IPN, success, failure, cancel). It is designed to work with the `durrbar/payment-module` for shared payment driver functionality.

---

## **Features**
- **Tokenized Payments**: Supports secure tokenized payment flows.
- **Refunds**: Initiate and check the status of refunds.
- **Transaction Verification**: Verify payment transactions using transaction IDs.
- **Callback Handling**: Handle IPN, success, failure, and cancellation callbacks.
- **Sandbox Support**: Easily switch between sandbox and live environments for testing.
- **Queue Integration**: Automatically retry refund status checks using queued jobs.

---

## **Requirements**
- PHP >= 8.0
- Laravel >= 9.0
- `durrbar/payment-module` (for shared payment driver functionality)

---

## **Installation**

Install the package via Composer:

```bash
composer require durrbar/payment-sslcommerz-driver
```

---

## **Configuration**

Add the following variables to your `.env` file:

```env
SSLCOMMERZ_SANDBOX=true // set false when using live store id
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=your_store_password
```

The configuration will automatically load from `payment.providers.sslcommerz` in your Laravel application.

---

## **Usage**

This package is designed to work seamlessly with the `PaymentService` from the `durrbar/payment-module`. All payment-related operations are handled automatically by the `PaymentService`. Simply configure the package and specify `sslcommerz` as the provider when interacting with the `PaymentService`.

### **How It Works**
1. Install the package and configure the `.env` file with your Sslcommerz credentials.
2. The `PaymentService` dynamically resolves this package as the driver for Sslcommerz payments.
3. All payment-related operations (initiating payments, handling callbacks, refunds, etc.) are handled automatically by the `PaymentService`.

No additional setup or manual integration is required beyond installing the package and adding the configuration.

---

### **Supported Operations**
The following operations are supported and handled automatically by the `PaymentService`:
- **Initiating a Payment**: Payments are initiated using the Sslcommerz API.
- **Handling Callbacks**: IPN, success, failure, and cancellation callbacks are processed automatically.
- **Verifying a Payment**: Payment transactions are verified using transaction IDs.
- **Refunding a Payment**: Refunds can be initiated and their status checked automatically.
- **Checking Refund Status**: The `PaymentService` checks the status of refunds using queued jobs.

---

## **Testing**
To test the package in sandbox mode, set `SSLCOMMERZ_SANDBOX=true` in your `.env` file. Use the sandbox credentials provided by Sslcommerz.

---

## **Contributing**
Contributions are welcome! Please follow these steps:
1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with a detailed description of your changes.

---

## **Security**
If you discover any security-related issues, please email the maintainer instead of using the issue tracker.

---

## **Credits**
- [Your Name](https://github.com/officialkidmax)
- Inspired by [Sslcommerz API Documentation](https://developer.sslcommerz.com/doc/v4/)

---

## **License**
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---
