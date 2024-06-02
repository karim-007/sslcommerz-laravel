<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Karim007\SslcommerzLaravel\Facade\SSLCommerzPayment;
use Karim007\SslcommerzLaravel\SslCommerz\SslCommerzNotification;
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

    public function index(Request $request)
    {
        $post_data = array();
        $post_data['total_amount'] = '10'; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = uniqid(); // tran_id must be unique

        $customer = array();
        $customer['name'] = 'Ab Karim';
        $customer['email'] = 'customer@mail.com';
        $customer['address_1'] = 'Dhaka';
        $customer['address_2'] = "";
        $customer['city'] = "";
        $customer['state'] = "";
        $customer['postcode'] = "";
        $customer['country'] = "Bangladesh";
        $customer['phone'] = '8801XXXXXXXXX';
        $customer['fax'] = "";
        // SSLCommerzPayment::setCustomerInfo($customer);

        $s_info = array();
        $s_info['shipping_method'] = 'Yes'; // string (50)	Mandatory - Shipping method of the order. Example: YES or NO or Courier
        $s_info['num_of_item'] = 1; // integer (1)	Mandatory - No of product will be shipped. Example: 1 or 2 or etc
        $s_info['ship_name'] = 'Abc'; // string (50)	Mandatory, if shipping_method is YES - Shipping Address of your order. Not mandatory but useful if provided
        $s_info['ship_add1'] = 'Dhaka';; // string (50)	Mandatory, if shipping_method is YES - Additional Shipping Address of your order. Not mandatory but useful if provided
        $s_info['ship_add2'] = ''; // string (50)	Additional Shipping Address of your order. Not mandatory but useful if provided
        $s_info['ship_city'] = 'Dhaka'; // string (50)	Mandatory, if shipping_method is YES - Shipping city of your order. Not mandatory but useful if provided
        $s_info['ship_state'] = ''; // string (50)	Shipping state of your order. Not mandatory but useful if provided
        $s_info['ship_postcode'] = '1215'; // string (50)	Mandatory, if shipping_method is YES - Shipping postcode of your order. Not mandatory but useful if provided
        $s_info['ship_country'] = 'Bangladesh'; // string (50)	Mandatory, if shipping_method is YES - Shipping country of your order. Not mandatory but useful if provided

        $sslc = new SslCommerzNotification();
        $sslc->setCustomerInfo($customer)->setShipmentInfo($s_info);



        #Before  going to initiate the payment order status need to insert or update as Pending.
        DB::table('orders')
            ->where('transaction_id', $post_data['tran_id'])
            ->updateOrInsert([
                'amount' => $post_data['total_amount'],
                'status' => 'Pending',
                'transaction_id' => $post_data['tran_id'],
                'currency' => $post_data['currency']
            ]);

        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'hosted');
        return $payment_options;

    }

    public function payViaAjax(Request $request)
    {
        $post_data = array();
        $post_data['total_amount'] = '10'; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = uniqid(); // tran_id must be unique

        $customer = array();
        $customer['name'] = 'Ab Karim';
        $customer['email'] = 'customer@mail.com';
        $customer['address_1'] = 'Dhaka';
        $customer['address_2'] = "";
        $customer['city'] = "";
        $customer['state'] = "";
        $customer['postcode'] = "";
        $customer['country'] = "Bangladesh";
        $customer['phone'] = '8801XXXXXXXXX';
        $customer['fax'] = "";
        // SSLCommerzPayment::setCustomerInfo($customer);

        $s_info = array();
        $s_info['shipping_method'] = 'Yes'; // string (50)	Mandatory - Shipping method of the order. Example: YES or NO or Courier
        $s_info['num_of_item'] = 1; // integer (1)	Mandatory - No of product will be shipped. Example: 1 or 2 or etc
        $s_info['ship_name'] = 'Abc'; // string (50)	Mandatory, if shipping_method is YES - Shipping Address of your order. Not mandatory but useful if provided
        $s_info['ship_add1'] = 'Dhaka';; // string (50)	Mandatory, if shipping_method is YES - Additional Shipping Address of your order. Not mandatory but useful if provided
        $s_info['ship_add2'] = ''; // string (50)	Additional Shipping Address of your order. Not mandatory but useful if provided
        $s_info['ship_city'] = 'Dhaka'; // string (50)	Mandatory, if shipping_method is YES - Shipping city of your order. Not mandatory but useful if provided
        $s_info['ship_state'] = ''; // string (50)	Shipping state of your order. Not mandatory but useful if provided
        $s_info['ship_postcode'] = '1215'; // string (50)	Mandatory, if shipping_method is YES - Shipping postcode of your order. Not mandatory but useful if provided
        $s_info['ship_country'] = 'Bangladesh'; // string (50)	Mandatory, if shipping_method is YES - Shipping country of your order. Not mandatory but useful if provided

        $sslc = new SslCommerzNotification();
        $sslc->setCustomerInfo($customer)->setShipmentInfo($s_info);


        #Before  going to initiate the payment order status need to update as Pending.
        DB::table('orders')
            ->where('transaction_id', $post_data['tran_id'])
            ->updateOrInsert([
                'amount' => $post_data['total_amount'],
                'status' => 'Pending',
                'transaction_id' => $post_data['tran_id'],
                'currency' => $post_data['currency']
            ]);

        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'checkout', 'json');
        return $payment_options;

    }

    public function success(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        #Check order status in order tabel against the transaction id or order id.
        $order_detials = $this->findOrder($tran_id);
        if ($order_detials->status == 'Pending') {
            $validation = SSLCommerzPayment::orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation) {
                $this->orderUpdate($tran_id,'Processing');
                return SSLCommerzPayment::returnSuccess($tran_id,"Transaction is successfully Completed",'/');
            }
        } else if ($order_detials->status == 'Processing' || $order_detials->status == 'Complete') {
            return SSLCommerzPayment::returnSuccess($tran_id,"Transaction is successfully Completed",'/');
        }
        #That means something wrong happened. You can redirect customer to your product page.
        return SSLCommerzPayment::returnFail($tran_id,"Invalid Transaction",'/');

    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order_detials = $this->findOrder($tran_id);
        if ($order_detials->status == 'Pending') {
            $this->orderUpdate($tran_id,'Failed');
            return SSLCommerzPayment::returnFail($tran_id,"Transaction is Failed",'/');
        } else if ($order_detials->status == 'Processing' || $order_detials->status == 'Complete') {
            return SSLCommerzPayment::returnSuccess($tran_id,"Transaction is already Successful",'/');
        } else {
            return SSLCommerzPayment::returnFail($tran_id,"Transaction is Invalid",'/');
        }

    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');

        $order_detials = $this->findOrder($tran_id);
        if ($order_detials->status == 'Pending') {
            $this->orderUpdate($tran_id,'Canceled');
            return SSLCommerzPayment::returnFail($tran_id,"Transaction is Cancel",'/');
        } else if ($order_detials->status == 'Processing' || $order_detials->status == 'Complete') {
            return SSLCommerzPayment::returnSuccess($tran_id,"Transaction is already Successful",'/');
        } else {
            return SSLCommerzPayment::returnFail($tran_id,"Transaction is Invalid",'/');
        }
    }

    public function ipn(Request $request)
    {
        #Received all the payement information from the gateway
        if ($request->input('tran_id')) #Check transation id is posted or not.
        {

            $tran_id = $request->input('tran_id');

            #Check order status in order tabel against the transaction id or order id.
            $order_details = $this->findOrder($tran_id);
            if ($order_details->status == 'Pending') {
                //$sslc = new SslCommerzNotification();
                $validation = SSLCommerzPayment::orderValidate($request->all(), $tran_id, $order_details->amount, $order_details->currency);
                if ($validation == TRUE) {
                    $this->orderUpdate($tran_id,'Processing');
                    return SSLCommerzPayment::returnSuccess($tran_id,"Transaction is successfully Completed",'/');
                }
            } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
                return SSLCommerzPayment::returnSuccess($tran_id,"Transaction is already successfully Completed",'/');
            } else {
                #That means something wrong happened. You can redirect customer to your product page.
                return SSLCommerzPayment::returnFail($tran_id,"Invalid Transaction",'/');
            }
        }
        return SSLCommerzPayment::returnFail('',"Invalid Data",'/');
    }

    private function orderUpdate($tran_id,$status){
        DB::table('orders')
            ->where('transaction_id', $tran_id)
            ->update(['status' => $status]);
    }
    private function findOrder($tran_id){
        return DB::table('orders')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'amount')->first();

    }

}
