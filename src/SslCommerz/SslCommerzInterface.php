<?php
namespace Karim007\SslcommerzLaravel\SslCommerz;

interface SslCommerzInterface
{
    public function makePayment(array $data);

    public function refundPayment($bankTranId, $refundAmount, $refundRemarks = "Customer return ticket");
    public function refundStatus($refundRefId);
    public function orderValidate($requestData, $trxID, $amount, $currency);

    public function setCustomerInfo(array $data);

    public function setShipmentInfo(array $data);

    public function setAdditionalInfo(array $data);
    public function setBin(string $bin);
    public function enableEMI(int $installment, int $max_installment, bool $restrict_emi_only = false);
    public function setAirlineTicketProfile(array $info);
    public function setTravelVerticalProfile(array $info);
    public function setTelecomVerticleProfile(array $info);
    public function setCarts(array $info);
    public function returnSuccess($transId,$message,$url='/');
    public function returnFail($transId,$message,$url='/');

    public function callToApi($data, $header = [], $setLocalhost = false);
}
