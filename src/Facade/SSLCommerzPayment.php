<?php

namespace Karim007\SslcommerzLaravel\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static makePayment(array $requestData, $type = 'checkout', $pattern = 'json')
 * @method static orderValidate($post_data, $trx_id = '', $amount = 0, $currency = "BDT")
 */
class SSLCommerzPayment extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sslcommerznotification';
    }
}
