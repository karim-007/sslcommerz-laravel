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
php artisan vendor:publish --provider="Karim007\SslcommerzLaravel\SslcommerzLaravelServiceProvider"
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
```

### Set .env configuration

```
SSLCOMMERZ_SANDBOX=true #For Sandbox, use "true", For Live, use "false"
SSLCOMMERZ_STORE_ID=
SSLCOMMERZ__STORE_PASSWORD=
```
For development purposes, you can obtain sandbox 'Store ID' and 'Store Password'
by registering at https://developer.sslcommerz.com/registration/

###  after publish you also saw the controller SslCommerzPaymentController.php
```
class SslCommerzPaymentController extends Controller
{
    public function exampleEasyCheckout()
    {
        return view('sslcommerz::exampleEasycheckout');
    }

    public function exampleHostedCheckout()
    {
        return view('sslcommerz::exampleHosted');
    }
    public function index(Request $request){}
    public function payViaAjax(Request $request){}
    public function success(Request $request){}
    public function fail(Request $request){}
    public function cancel(Request $request){}
    public function ipn(Request $request){}
}
```

### routes list
```
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
    '/sslcommerz/success',
    '/sslcommerz/cancel',
    '/sslcommerz/fail',
    '/sslcommerz/ipn'
    '/sslcommerz/pay-via-ajax', // only required to run example codes. Please see bellow.
];
```


Contributions to the SSLCommerz Payment Gateway package  you are welcome. Please note the following guidelines before submitting your pull
request.

- Follow [PSR-4](http://www.php-fig.org/psr/psr-4/) coding standards.
- Read SSLCommerz API documentations first. Please contact with SSLCommerz for their api documentation and sandbox access.

## License

This repository is licensed under the [MIT License](http://opensource.org/licenses/MIT).

Copyright 2023 [md abdul karim](https://github.com/karim-007). We are not affiliated with SSLCommerz and don't give any guarantee. 
