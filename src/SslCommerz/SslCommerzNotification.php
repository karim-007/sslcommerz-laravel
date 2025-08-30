<?php
namespace Karim007\SslcommerzLaravel\SslCommerz;

use Illuminate\Support\Facades\Http;

class SslCommerzNotification extends AbstractSslCommerz
{
    protected $data = [];
    protected $config = [];

    private $successUrl;
    private $cancelUrl;
    private $failedUrl;
	private $ipnUrl;
    private $error;

    /**
     * @var string $baseUrl
     */
    private $baseUrl;
    /**
     * @var array $paymentUrl
     */
    private $paymentUrl=[];


    /**
     * SslCommerzNotification constructor.
     */
    public function __construct()
    {
        $this->config = config('sslcommerz');

        $this->setStoreId($this->config['store_id']);
        $this->setStorePassword($this->config['store_password']);
        $this->baseUrl();
        ## default info
        $this->defaultInfo();
        return $this;
    }

    /**
     * sslcommerz Base Url
     * if sandbox is true it will be sandbox url otherwise it is host url
     */
    private function baseUrl()
    {
        if ($this->config['sandbox'] == true) {
            $this->baseUrl = 'https://sandbox.sslcommerz.com';
        } else {
            $this->baseUrl = 'https://securepay.sslcommerz.com';
        }
        $this->paymentUrl();
        return $this;
    }

    private function paymentUrl()
    {
        $this->paymentUrl = [
            'make_payment' => $this->baseUrl."/gwprocess/v4/api.php",
            'transaction_status' => $this->baseUrl."/validator/api/merchantTransIDvalidationAPI.php",
            'order_validate' => $this->baseUrl."/validator/api/validationserverAPI.php",
            'refund_payment' => $this->baseUrl."/validator/api/merchantTransIDvalidationAPI.php",
            'refund_status' => $this->baseUrl."/validator/api/merchantTransIDvalidationAPI.php",
        ];
        return $this;
    }

    public function orderValidate($post_data, $trx_id = '', $amount = 0, $currency = "BDT")
    {
        if ($post_data == '' && $trx_id == '' && !is_array($post_data)) {
            $this->error = "Please provide valid transaction ID and post request data";
            return $this->error;
        }

        return $this->validate($trx_id, $amount, $currency, $post_data);

    }


    # VALIDATE SSLCOMMERZ TRANSACTION
    protected function validate($merchant_trans_id, $merchant_trans_amount, $merchant_trans_currency, $post_data)
    {
        # MERCHANT SYSTEM INFO
        if (!empty($merchant_trans_id) && !empty($merchant_trans_amount)) {

            # CALL THE FUNCTION TO CHECK THE RESULT
            $post_data['store_id'] = $this->getStoreId();
            $post_data['store_pass'] = $this->getStorePassword();

            $val_id = urlencode($post_data['val_id']);
            $store_id = urlencode($this->getStoreId());
            $store_passwd = urlencode($this->getStorePassword());
            $requested_url = ($this->paymentUrl['order_validate'] . "?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");

            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $requested_url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

            if ($this->config['sandbox']) {
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
            } else {
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 2);
            }


            $result = curl_exec($handle);

            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if ($code == 200 && !(curl_errno($handle))) {

                # TO CONVERT AS ARRAY
                # $result = json_decode($result, true);
                # $status = $result['status'];

                # TO CONVERT AS OBJECT
                $result = json_decode($result);
                $this->sslc_data = $result;

                # TRANSACTION INFO
                $status = $result->status;
                $tran_date = $result->tran_date;
                $tran_id = $result->tran_id;
                $val_id = $result->val_id;
                $amount = $result->amount;
                $store_amount = $result->store_amount;
                $bank_tran_id = $result->bank_tran_id;
                $card_type = $result->card_type;
                $currency_type = $result->currency_type;
                $currency_amount = $result->currency_amount;

                # ISSUER INFO
                $card_no = $result->card_no;
                $card_issuer = $result->card_issuer;
                $card_brand = $result->card_brand;
                $card_issuer_country = $result->card_issuer_country;
                $card_issuer_country_code = $result->card_issuer_country_code;

                # API AUTHENTICATION
                $APIConnect = $result->APIConnect;
                $validated_on = $result->validated_on;
                $gw_version = $result->gw_version;

                # GIVE SERVICE
                if ($status == "VALID" || $status == "VALIDATED") {
                    if ($merchant_trans_currency == "BDT") {
                        if (trim($merchant_trans_id) == trim($tran_id) && (abs($merchant_trans_amount - $amount) < 1) && trim($merchant_trans_currency) == trim('BDT')) {
                            return true;
                        } else {
                            # DATA TEMPERED
                            $this->error = "Data has been tempered";
                            return false;
                        }
                    } else {
                        //echo "trim($merchant_trans_id) == trim($tran_id) && ( abs($merchant_trans_amount-$currency_amount) < 1 ) && trim($merchant_trans_currency)==trim($currency_type)";
                        if (trim($merchant_trans_id) == trim($tran_id) && (abs($merchant_trans_amount - $currency_amount) < 1) && trim($merchant_trans_currency) == trim($currency_type)) {
                            return true;
                        } else {
                            # DATA TEMPERED
                            $this->error = "Data has been tempered";
                            return false;
                        }
                    }
                } else {
                    # FAILED TRANSACTION
                    $this->error = "Failed Transaction";
                    return false;
                }
            } else {
                # Failed to connect with SSLCOMMERZ
                $this->error = "Faile to connect with SSLCOMMERZ";
                return false;
            }
        } else {
            # INVALID DATA
            $this->error = "Invalid data";
            return false;
        }
    }

    # FUNCTION TO CHECK HASH VALUE
    protected function SSLCOMMERZ_hash_verify($post_data, $store_passwd = "")
    {
        if (isset($post_data) && isset($post_data['verify_sign']) && isset($post_data['verify_key'])) {
            # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
            $pre_define_key = explode(',', $post_data['verify_key']);

            $new_data = array();
            if (!empty($pre_define_key)) {
                foreach ($pre_define_key as $value) {
//                    if (isset($post_data[$value])) {
                        $new_data[$value] = ($post_data[$value]);
//                    }
                }
            }
            # ADD MD5 OF STORE PASSWORD
            $new_data['store_passwd'] = md5($store_passwd);

            # SORT THE KEY AS BEFORE
            ksort($new_data);

            $hash_string = "";
            foreach ($new_data as $key => $value) {
                $hash_string .= $key . '=' . ($value) . '&';
            }
            $hash_string = rtrim($hash_string, '&');

            if (md5($hash_string) == $post_data['verify_sign']) {

                return true;

            } else {
                $this->error = "Verification signature not matched";
                return false;
            }
        } else {
            $this->error = 'Required data mission. ex: verify_key, verify_sign';
            return false;
        }
    }

    /**
     * @param array $requestData
     * @param string $type
     * @param string $pattern
     * @return false|mixed|string
     */
    public function makePayment(array $requestData, $type = 'checkout', $pattern = 'json')
    {
        if (empty($requestData)) {
            return "Please provide a valid information list about transaction with transaction id, amount, success url, fail url, cancel url, store id and pass at least";
        }

        $header = [];

        $this->setApiUrl($this->paymentUrl['make_payment']);

        // Set the compulsory params
        $this->setRequiredInfo($requestData);

        // Set the authentication information
        $this->setAuthenticationInfo();

        // Now, call the Gateway API
        $response = $this->callToApi($this->data, $header, $this->config['sandbox']);

        $formattedResponse = $this->formatResponse($response, $type, $pattern); // Here we will define the response pattern

        if ($type == 'hosted') {
            if (!empty($formattedResponse['GatewayPageURL'])) {
                $this->redirect($formattedResponse['GatewayPageURL']);
            } else {
                if (strpos($formattedResponse['failedreason'], 'Store Credential') === false) {
                    $message = $formattedResponse['failedreason'];
                } else {
                    $message = "Check the SSLCZ_TESTMODE and SSLCZ_STORE_PASSWORD value in your .env; DO NOT USE MERCHANT PANEL PASSWORD HERE.";
                }

                return $message;
            }
        } else {
            return $formattedResponse;
        }
    }

    public function refundPayment($bankTranId, $refundAmount, $refundRemarks = "Customer return ticket")
    {
        $response = Http::get($this->paymentUrl['refund_payment'], [
            'bank_tran_id' => $bankTranId,
            'refund_trans_id' => uniqid(),
            'refund_amount' => $refundAmount,
            'refund_remarks' => 'Return booking',
            'store_id' => $this->getStoreId(),
            'store_passwd' => $this->getStorePassword(),
            'format' => 'json',
            'v' => 1
        ]);
        return $response->json();
    }

    public function refundStatus($refundRefId)
    {
        $postData = [
            'store_id'     => $this->getStoreId(),
            'store_passwd' => $this->getStorePassword(),
            'refund_ref_id'=> $refundRefId, // received from refund API response
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paymentUrl['refund_status']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->config['sandbox']) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }


    private function setSuccessUrl() {
		$this->successUrl = url('/') . $this->config['success_url'];
        return $this;
    }


    private function setFailedUrl() {
		$this->failedUrl = url('/') . $this->config['failed_url'];
        return $this;
    }

    private function setCancelUrl() {
		$this->cancelUrl = url('/') . $this->config['cancel_url'];
    }

    private function setIPNUrl() {
		$this->ipnUrl = url('/') . $this->config['ipn_url'];
        return $this;
	}

    private function setAuthenticationInfo()
    {
        $this->data['store_id'] = $this->getStoreId();
        $this->data['store_passwd'] = $this->getStorePassword();
        return $this;
    }

    private function setRequiredInfo(array $info)
    {
        $this->data['total_amount'] = $info['total_amount']; // decimal (10,2)	Mandatory - The amount which will process by SSLCommerz. It shall be decimal value (10,2). Example : 55.40. The transaction amount must be from 10.00 BDT to 500000.00 BDT
        $this->data['currency'] = $info['currency']; // string (3)	Mandatory - The currency type must be mentioned. It shall be three characters. Example : BDT, USD, EUR, SGD, INR, MYR, etc. If the transaction currency is not BDT, then it will be converted to BDT based on the current convert rate. Example : 1 USD = 82.22 BDT.
        $this->data['tran_id'] = $info['tran_id']; // string (30)	Mandatory - Unique transaction ID to identify your order in both your end and SSLCommerz
        $this->data['product_category'] = (isset($info['product_category'])) ? $info['product_category'] : 'Our Product'; // string (50)	Mandatory - Mention the product category. It is a open field. Example - clothing,shoes,watches,gift,healthcare, jewellery,top up,toys,baby care,pants,laptop,donation,etc

        return $this;
    }

    private function defaultInfo(){
        // Set the SUCCESS, FAIL, CANCEL Redirect URL before setting the other parameters
        $this->setSuccessUrl();
        $this->setFailedUrl();
        $this->setCancelUrl();
        $this->setIPNUrl();

        $this->data['success_url'] = $this->successUrl; // string (255)	Mandatory - It is the callback URL of your website where user will redirect after successful payment (Length: 255)
        $this->data['fail_url'] = $this->failedUrl; // string (255)	Mandatory - It is the callback URL of your website where user will redirect after any failure occure during payment (Length: 255)
        $this->data['cancel_url'] = $this->cancelUrl; // string (255)	Mandatory - It is the callback URL of your website where user will redirect if user canceled the transaction (Length: 255)

        /*
         * IPN is very important feature to integrate with your site(s).
         * Some transaction could be pending or customer lost his/her session, in such cases back-end IPN plays a very important role to update your backend office.
         *
         * Type: string (255)
         * Important! Not mandatory, however better to use to avoid missing any payment notification - It is the Instant Payment Notification (IPN) URL of your website where SSLCOMMERZ will send the transaction's status (Length: 255).
         * The data will be communicated as SSLCOMMERZ Server to your Server. So, customer session will not work.
		*/
        $this->data['ipn_url'] = $this->ipnUrl;

        /*
         * Type: string (30)
         * Do not Use! If you do not customize the gateway list - You can control to display the gateway list at SSLCommerz gateway selection page by providing this parameters.
         * Multi Card:
            brac_visa = BRAC VISA
            dbbl_visa = Dutch Bangla VISA
            city_visa = City Bank Visa
            ebl_visa = EBL Visa
            sbl_visa = Southeast Bank Visa
            brac_master = BRAC MASTER
            dbbl_master = MASTER Dutch-Bangla
            city_master = City Master Card
            ebl_master = EBL Master Card
            sbl_master = Southeast Bank Master Card
            city_amex = City Bank AMEX
            qcash = QCash
            dbbl_nexus = DBBL Nexus
            bankasia = Bank Asia IB
            abbank = AB Bank IB
            ibbl = IBBL IB and Mobile Banking
            mtbl = Mutual Trust Bank IB
            bkash = Bkash Mobile Banking
            dbblmobilebanking = DBBL Mobile Banking
            city = City Touch IB
            upay = Upay
            tapnpay = Tap N Pay Gateway
         * GROUP GATEWAY
            internetbank = For all internet banking
            mobilebank = For all mobile banking
            othercard = For all cards except visa,master and amex
            visacard = For all visa
            mastercard = For All Master card
            amexcard = For Amex Card
         * */
        $this->data['multi_card_name'] = (isset($info['multi_card_name'])) ? $info['multi_card_name'] : null;

        /*
         * Type: string (255)
         * Do not Use! If you do not control on transaction - You can provide the BIN of card to allow the transaction must be completed by this BIN. You can declare by coma ',' separate of these BIN.
         * Example: 371598,371599,376947,376948,376949
         * */
        $this->data['allowed_bin'] = (isset($info['allowed_bin'])) ? $info['allowed_bin'] : null;

        ##   Parameters to Handle EMI Transaction ##
        $this->data['emi_option'] = (isset($info['emi_option'])) ? $info['emi_option'] : null; // integer (1)	Mandatory - This is mandatory if transaction is EMI enabled and Value must be 1/0. Here, 1 means customer will get EMI facility for this transaction
        $this->data['emi_max_inst_option'] = (isset($info['emi_max_inst_option'])) ? $info['emi_max_inst_option'] : null; // integer (2)	Max instalment Option, Here customer will get 3,6, 9 instalment at gateway page
        $this->data['emi_selected_inst'] = (isset($info['emi_selected_inst'])) ? $info['emi_selected_inst'] : null; // integer (2)	Customer has selected from your Site, So no instalment option will be displayed at gateway page
        $this->data['emi_allow_only'] = (isset($info['emi_allow_only'])) ? $info['emi_allow_only'] : 0;

        # CUSTOMER INFORMATION
        $this->data['cus_name'] = 'Customer Name';
        $this->data['cus_email'] = 'customer@mail.com';
        $this->data['cus_add1'] = 'Customer Address';
        $this->data['cus_add2'] = "";
        $this->data['cus_city'] = "";
        $this->data['cus_state'] = "";
        $this->data['cus_postcode'] = "";
        $this->data['cus_country'] = "Bangladesh";
        $this->data['cus_phone'] = '8801XXXXXXXXX';
        $this->data['cus_fax'] = "";

        # SHIPMENT INFORMATION
        $this->data['ship_name'] = "Store Test";
        $this->data['ship_add1'] = "Dhaka";
        $this->data['ship_add2'] = "Dhaka";
        $this->data['ship_city'] = "Dhaka";
        $this->data['ship_state'] = "Dhaka";
        $this->data['ship_postcode'] = "1000";
        $this->data['ship_phone'] = "";
        $this->data['ship_country'] = "Bangladesh";

        $this->data['shipping_method'] = "NO";
        $this->data['product_name'] = "Computer";
        $this->data['product_category'] = "Goods";
        $this->data['product_profile'] = "physical-goods";

        # OPTIONAL PARAMETERS
        $this->data['value_a'] = "ref001";
        $this->data['value_b'] = "ref002";
        $this->data['value_c'] = "ref003";
        $this->data['value_d'] = "ref004";
        return $this;
    }

    public function setCustomerInfo(array $info)
    {
        $this->data['cus_name'] = (isset($info['name'])) ? $info['name'] : 'Ab Karim'; // string (50)	Mandatory - Your customer name to address the customer in payment receipt email
        $this->data['cus_email'] = (isset($info['email'])) ? $info['email'] : 'customer@email.com'; // string (50)	Mandatory - Valid email address of your customer to send payment receipt from SSLCommerz end
        $this->data['cus_add1'] = (isset($info['address_1'])) ? $info['address_1'] : 'Dhaka'; // string (50)	Mandatory - Address of your customer. Not mandatory but useful if provided
        $this->data['cus_add2'] = (isset($info['address_2'])) ? $info['address_2'] : ''; // string (50)	Address line 2 of your customer. Not mandatory but useful if provided
        $this->data['cus_city'] = (isset($info['city'])) ? $info['city'] : 'Dhaka'; // string (50)	Mandatory - City of your customer. Not mandatory but useful if provided
        $this->data['cus_state'] = (isset($info['state'])) ? $info['state'] : null; // string (50)	State of your customer. Not mandatory but useful if provided
        $this->data['cus_postcode'] = (isset($info['postcode'])) ? $info['postcode'] : null; // string (30)	Mandatory - Postcode of your customer. Not mandatory but useful if provided
        $this->data['cus_country'] = (isset($info['country'])) ? $info['country'] : 'Bangladesh'; // string (50)	Mandatory - Country of your customer. Not mandatory but useful if provided
        $this->data['cus_phone'] = (isset($info['phone'])) ? $info['phone'] : '015XXXXXXXX'; // string (20)	Mandatory - The phone/mobile number of your customer to contact if any issue arises
        $this->data['cus_fax'] = (isset($info['fax'])) ? $info['fax'] : null; // string (20)	Fax number of your customer. Not mandatory but useful if provided

        return $this;
    }

    public function setShipmentInfo(array $info)
    {

        $this->data['shipping_method'] = (isset($info['shipping_method'])) ? $info['shipping_method'] : 'Yes'; // string (50)	Mandatory - Shipping method of the order. Example: YES or NO or Courier
        $this->data['num_of_item'] = isset($info['num_of_item']) ? $info['num_of_item'] : 1; // integer (1)	Mandatory - No of product will be shipped. Example: 1 or 2 or etc
        $this->data['ship_name'] = (isset($info['ship_name'])) ? $info['ship_name'] : ''; // string (50)	Mandatory, if shipping_method is YES - Shipping Address of your order. Not mandatory but useful if provided
        $this->data['ship_add1'] = (isset($info['ship_add1'])) ? $info['ship_add1'] : '';; // string (50)	Mandatory, if shipping_method is YES - Additional Shipping Address of your order. Not mandatory but useful if provided
        $this->data['ship_add2'] = (isset($info['ship_add2'])) ? $info['ship_add2'] : null; // string (50)	Additional Shipping Address of your order. Not mandatory but useful if provided
        $this->data['ship_city'] = (isset($info['ship_city'])) ? $info['ship_city'] : 'Dhaka'; // string (50)	Mandatory, if shipping_method is YES - Shipping city of your order. Not mandatory but useful if provided
        $this->data['ship_state'] = (isset($info['ship_state'])) ? $info['ship_state'] : null; // string (50)	Shipping state of your order. Not mandatory but useful if provided
        $this->data['ship_postcode'] = (isset($info['ship_postcode'])) ? $info['ship_postcode'] : null; // string (50)	Mandatory, if shipping_method is YES - Shipping postcode of your order. Not mandatory but useful if provided
        $this->data['ship_country'] = (isset($info['ship_country'])) ? $info['ship_country'] : null; // string (50)	Mandatory, if shipping_method is YES - Shipping country of your order. Not mandatory but useful if provided

        return $this;
    }

    public function setAdditionalInfo(array $info)
    {
        $this->data['value_a'] = (isset($info['value_a'])) ? $info['value_a'] : null; // value_a [ string (255)	- Extra parameter to pass your meta data if it is needed. Not mandatory]
        $this->data['value_b'] = (isset($info['value_b'])) ? $info['value_b'] : null; // value_b [ string (255)	- Extra parameter to pass your meta data if it is needed. Not mandatory]
        $this->data['value_c'] = (isset($info['value_c'])) ? $info['value_c'] : null; // value_c [ string (255)	- Extra parameter to pass your meta data if it is needed. Not mandatory]
        $this->data['value_d'] = (isset($info['value_d'])) ? $info['value_d'] : null; // value_d [ string (255)	- Extra parameter to pass your meta data if it is needed. Not mandatory]

        return $this;
    }

    public function setBin(string $bin)
    {
        $this->data['allowed_bin'] = $bin;
        return $this;
    }

    public function enableEMI(int $installment, int $max_installment, bool $restrict_emi_only = false)
    {

        $this->data['emi_option'] = 1; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['emi_selected_inst'] = $installment; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['emi_max_inst_option'] = $max_installment; // string (150)	Mandatory, if product_profile is telecom-vertical - Provide the mobile number which will be recharged. Example: 8801700000000 or 8801700000000,8801900000000
        $this->data['emi_allow_only'] = $restrict_emi_only ? 1 : 0; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh

        return $this;
    }

    public function setAirlineTicketProfile(array $info)
    {
        $this->data['product_profile'] = 'airline-tickets'; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['hours_till_departure'] = (isset($info['hours_till_departure'])) ? $info['hours_till_departure'] : ''; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['flight_type'] = (isset($info['flight_type'])) ? $info['flight_type'] : null; // string (150)	Mandatory, if product_profile is telecom-vertical - Provide the mobile number which will be recharged. Example: 8801700000000 or 8801700000000,8801900000000
        $this->data['pnr'] = (isset($info['pnr'])) ? $info['pnr'] : null; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh
        $this->data['journey_from_to'] = (isset($info['journey_from_to'])) ? $info['journey_from_to'] : 'Dhaka'; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh
        $this->data['third_party_booking'] = (isset($info['third_party_booking'])) ? $info['third_party_booking'] : ''; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh

        return $this;
    }

    public function setTravelVerticalProfile(array $info)
    {

        $this->data['product_profile'] = 'travel-vertical'; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['hotel_name'] = (isset($info['hotel_name'])) ? $info['hotel_name'] : ''; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['length_of_stay'] = (isset($info['length_of_stay'])) ? $info['length_of_stay'] : 1; // string (150)	Mandatory, if product_profile is telecom-vertical - Provide the mobile number which will be recharged. Example: 8801700000000 or 8801700000000,8801900000000
        $this->data['check_in_time'] = (isset($info['check_in_time'])) ? $info['check_in_time'] : null; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh
        $this->data['hotel_city'] = (isset($info['hotel_city'])) ? $info['hotel_city'] : 'Dhaka'; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh

        return $this;
    }

    public function setTelecomVerticleProfile(array $info)
    {
        $this->data['product_profile'] = 'telecom-vertical'; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['product_type'] = (isset($info['product_type'])) ? $info['product_type'] : null; // string (30)	Mandatory, if product_profile is telecom-vertical - For mobile or any recharge, this information is necessary. Example: Prepaid or Postpaid
        $this->data['topup_number'] = (isset($info['topup_number'])) ? $info['topup_number'] : null; // string (150)	Mandatory, if product_profile is telecom-vertical - Provide the mobile number which will be recharged. Example: 8801700000000 or 8801700000000,8801900000000
        $this->data['country_topup'] = (isset($info['country_topup'])) ? $info['country_topup'] : null; // string (30)	Mandatory, if product_profile is telecom-vertical - Provide the country name in where the service is given. Example: Bangladesh

        return $this;
    }

    public function setCarts(array $info)
    {
        $this->data['cart'] = (isset($info['cart'])) ? $info['cart'] : null;
        $this->data['product_amount'] = (isset($info['product_amount'])) ? $info['product_amount'] : 0; // decimal (10,2)	Product price which will be displayed in your merchant panel and will help you to reconcile the transaction. It shall be decimal value (10,2). Example : 50.40
        $this->data['vat'] = (isset($info['vat'])) ? $info['vat'] : null; // decimal (10,2)	The VAT included on the product price which will be displayed in your merchant panel and will help you to reconcile the transaction. It shall be decimal value (10,2). Example : 4.00
        $this->data['discount_amount'] = (isset($info['discount_amount'])) ? $info['discount_amount'] : 0; // decimal (10,2)	Discount given on the invoice which will be displayed in your merchant panel and will help you to reconcile the transaction. It shall be decimal value (10,2). Example : 2.00
        $this->data['convenience_fee'] = (isset($info['convenience_fee'])) ? $info['convenience_fee'] : 0; // decimal (10,2)	Any convenience fee imposed on the invoice which will be displayed in your merchant panel and will help you to reconcile the transaction. It shall be decimal value (10,2). Example : 3.00
        return $this;
    }

    public function returnSuccess($transId,$message,$url='/'){
        if ($this->config['return_response'] == 'html'){
            return view('sslcommerz::success',compact('transId','message','url'));
        }
        return response()->json(['status'=>'success','transaction_id'=>$transId,'message'=>$message,'return_url'=>$url],200);
    }
    public function returnFail($transId,$message,$url='/'){
        if ($this->config['return_response'] == 'html'){
            return view('sslcommerz::failed',compact('transId','message','url'));
        }
        return response()->json(['status'=>'error','transaction_id'=>$transId,'message'=>$message,'return_url'=>$url],404);
    }
}
