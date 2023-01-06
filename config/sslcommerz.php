<?php

return [
    'sandbox' => env("SSLCOMMERZ_SANDBOX", false), // For Sandbox, use "true", For Live, use "false"
    'middleware' => 'web',//you can change this middleware according to you
    'store_id' => env("SSLCOMMERZ_STORE_ID"),
    'store_password' => env("SSLCOMMERZ__STORE_PASSWORD"),
    'success_url' => '/sslcommerz/success',
    'failed_url' => '/sslcommerz/fail',
    'cancel_url' => '/sslcommerz/cancel',
    'ipn_url' => '/sslcommerz/ipn',
    'return_response' => 'html', //html or json html means blade return json means json data return
];
