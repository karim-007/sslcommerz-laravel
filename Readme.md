# SSLCommerz Payment Gateway for PHP/Laravel Framework

[![Downloads](https://img.shields.io/packagist/dt/karim007/sslcommerz-laravel)](https://packagist.org/packages/karim007/sslcommerz-laravel)
[![Starts](https://img.shields.io/packagist/stars/karim007/sslcommerz-laravel)](https://packagist.org/packages/karim007/sslcommerz-laravel)

## Features

This is a php/laravel wrapper package for [SSLCommerz](https://sslcommerz.com)

## Requirements

- PHP >=7.4
- Laravel >= 6
- ext-curl '*'


## Installation

```bash
composer require karim007/sslcommerz-laravel
```

### vendor publish (config)

```bash
#it will publish config file in your config folders
php artisan vendor:publish --provider="Karim007\SslcommerzLaravel\SslcommerzLaravelServiceProvider" --tag="config"
```

After publish config file setup your credential. you can see this in your config directory sslcommerz.php file
```
'sandbox' => env("SSLCOMMERZ_SANDBOX", false), // For Sandbox, use "true", For Live, use "false"
'middleware' => 'web',//you can change this middleware according to you
'store_id' => env("SSLCOMMERZ_STORE_ID"),
'store_password' => env("SSLCOMMERZ__STORE_PASSWORD"),
'success_url' => '/sslcommerz/success',
'failed_url' => '/sslcommerz/fail',
'cancel_url' => '/sslcommerz/cancel',
'ipn_url' => '/sslcommerz/ipn',
'return_response' => 'html', //html or json html means blade return json means json data return

```

### Set .env configuration

```
SSLCOMMERZ_SANDBOX=true #For Sandbox, use "true", For Live, use "false"
SSLCOMMERZ_STORE_ID=
SSLCOMMERZ__STORE_PASSWORD=
```
For development purposes, you can obtain sandbox 'Store ID' and 'Store Password'
by registering at https://developer.sslcommerz.com/registration/


###  publish SslCommerzPaymentController.php controller

```bash
#it will publish controllers file in your app\Http\Controllers folders

php artisan vendor:publish --provider="Karim007\SslcommerzLaravel\SslcommerzLaravelServiceProvider" --tag="controllers"
```

### this is your routes list
```php
Route::group(['middleware'=>[config('sslcommerz.middleware','web')]], function () {
    Route::get('/sslcommerz/example1', [SslCommerzPaymentController::class, 'exampleEasyCheckout']);
    Route::get('/sslcommerz/example2', [SslCommerzPaymentController::class, 'exampleHostedCheckout']);

    Route::post('/sslcommerz/pay', [SslCommerzPaymentController::class, 'index']);
    Route::post('/sslcommerz/pay-via-ajax', [SslCommerzPaymentController::class, 'payViaAjax']);

    Route::post('/sslcommerz/success', [SslCommerzPaymentController::class, 'success']);
    Route::post('/sslcommerz/fail', [SslCommerzPaymentController::class, 'fail']);
    Route::post('/sslcommerz/cancel', [SslCommerzPaymentController::class, 'cancel']);

    Route::post('/sslcommerz/ipn', [SslCommerzPaymentController::class, 'ipn']);
});
```
### Add exceptions for VerifyCsrfToken middleware accordingly.
```php
protected $except = [
    '/sslcommerz/*',
];
```

### For payment ( All functionality already append in SslCommerzPaymentController)
```php
$post_data = array();
$post_data['total_amount'] = '10'; # You cant not pay less than 10
$post_data['currency'] = "BDT";
$post_data['tran_id'] = uniqid(); // tran_id must be unique
//for hosted payment
$payment_options = SSLCommerzPayment::makePayment($post_data, 'hosted');
#or
//for api/popup payment
$payment_options = SSLCommerzPayment::makePayment($post_data, 'checkout', 'json');
```
###  for other info append(optional)

```php
    # CUSTOMER INFORMATION
    $customer = array();
    $customer['cus_name'] = 'Customer Name';
    $customer['cus_email'] = 'customer@mail.com';
    $customer['cus_add1'] = 'Customer Address';
    $customer['cus_add2'] = "";
    $customer['cus_city'] = "";
    $customer['cus_state'] = "";
    $customer['cus_postcode'] = "";
    $customer['cus_country'] = "Bangladesh";
    $customer['cus_phone'] = '8801XXXXXXXXX';
    $customer['cus_fax'] = "";
    SSLCommerzPayment::setCustomerInfo($customer);
    
    # SHIPMENT INFORMATION
    $shipping = array();
    $shipping['ship_name'] = "Store Test";
    $shipping['ship_add1'] = "Dhaka";
    $shipping['ship_add2'] = "Dhaka";
    $shipping['ship_city'] = "Dhaka";
    $shipping['ship_state'] = "Dhaka";
    $shipping['ship_postcode'] = "1000";
    $shipping['ship_phone'] = "";
    $shipping['ship_country'] = "Bangladesh";
    $shipping['shipping_method'] = "NO";
    
    SSLCommerzPayment::setShipmentInfo($shipping);
    
    # Product info
    $productInfo = array();
    $productInfo['product_name'] = "Computer";
    $productInfo['product_category'] = "Goods";
    $productInfo['product_profile'] = "physical-goods";
    SSLCommerzPayment::setProductInfo($productInfo);
    
    # OPTIONAL PARAMETERS
    $addition = array();
    $addition['value_a'] = "ref001";
    $addition['value_b'] = "ref002";
    $addition['value_c'] = "ref003";
    $addition['value_d'] = "ref004";
    SSLCommerzPayment::setAdditionalInfo($addition);
    
    //then you can call
    SSLCommerzPayment::makePayment($post_data, 'hosted');
    //or
    SSLCommerzPayment::makePayment($post_data, 'checkout', 'json');

```

###  if you want you can publish orders migration

```bash
#if you already have orders or this related table then skip it
#it will publish order migrations file in your database\migrations folders
php artisan vendor:publish --provider="Karim007\SslcommerzLaravel\SslcommerzLaravelServiceProvider" --tag="migrations"

php artisan migrate
```

###  you can also publish views(optionals)

```bash
#it will publish order migrations file in your resource\views\sslcommerz folders
php artisan vendor:publish --provider="Karim007\SslcommerzLaravel\SslcommerzLaravelServiceProvider" --tag="views"
```

Contributions to the SSLCommerz Payment Gateway package  you are welcome. Please note the following guidelines before submitting your pull
request.

- Follow [PSR-4](http://www.php-fig.org/psr/psr-4/) coding standards.
- Read SSLCommerz API documentations first. Please contact with SSLCommerz for their api documentation and sandbox access.

## License

This repository is licensed under the [MIT License](http://opensource.org/licenses/MIT).

Copyright 2023 [md abdul karim](https://github.com/karim-007). We are not affiliated with SSLCommerz and don't give any guarantee. 
