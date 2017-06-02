<?php

namespace Controllers\Couriers;

use \Utility\SimpleXMLElementWrapper;
use \DateTime;
use \Controllers\AWBBatch;

/**
*
*/
class Gati extends Base
{
    protected static $baseUrl = 'http://119.235.57.47:9080';
    protected static $trackUrl = 'http://www.gati.com/webservices/ECOMDKTTRACK.jsp';
    protected static $name = 'Gati';
    private static $stateCodes = [
        "Andhra Pradesh"=>"AP",
        "Arunachal Pradesh"=>"AR",
        "Assam"=>"AS",
        "Bihar"=>"BR",
        "Chhattisgarh"=>"CG",
        "Goa"=>"GA",
        "Gujarat"=>"GJ",
        "Himachal Pradesh"=>"HP",
        "Haryana"=>"HR",
        "Jharkhand"=>"JH",
        "Jammu and Kashmir"=>"JK",
        "Karnataka"=>"KA",
        "Kerala"=>"KL",
        "Maharashtra"=>"MH",
        "Meghalaya"=>"ML",
        "Manipur"=>"MN",
        "Madhya Pradesh"=>"MP",
        "Mizoram"=>"MZ",
        "Nagaland"=>"NL",
        "Odisha"=>"OD",
        "Punjab"=>"PB",
        "Rajasthan"=>"RJ",
        "Sikkim"=>"SK",
        "Tamil Nadu"=>"TN",
        "Tripura"=>"TR",
        "Telangana"=>"TS",
        "Uttarakhand"=>"UK",
        "Uttar Pradesh"=>"UP",
        "West Bengal"=>"WB",
        "Andaman and Nicobar Islands"=>"AN",
        "Chandigarh"=>"CH",
        "Dadra and Nagar Haveli"=>"DN",
        "Daman and Diu"=>"DD",
        "Delhi"=>"DL",
        "Lakshadweep"=>"LD",
        "Puducherry"=>"PY"
    ];
    // $status = [
    //     'BOOKED' => ''
    // pickup  pending
    // pickup done
    // in-transit
    // delivered
    // failed
    //  pending
    //
    //
    // ];
    /**
     * Function to book a shipment on Gati
     *
     * @param mixed $orderInfo Array containing the order information
     * @param string $accountId Seller accout id for which the shipment is to be booked
     * @param string $courierServiceId courier service id to be used to book
     * @param array $serviceType service type to be used
     * @param string $awb AWB number to be assigned
     *
     * @throws Exception in case the order information is invalid/ incomplete
     * @throws Exception in case the pincodes are not serviceable
     * @throws Exception in case the Shipment Booking call fails from the Courier API side
     *
     * @return string $awb AWB number for the booked shipment
     */
    protected static function bookShipment($orderInfo, $accountId, $courierServiceId, $serviceType)
    {
        $headers = array('Content-Type: text/xml');
        $courierServiceAccount = static::getCourierServcieAccount($accountId, $courierServiceId);
        $credentials = $courierServiceAccount->getCredentials();
        $custVendRow = $courierServiceAccount->getExtraParams(/*$orderInfo['pickup_address']['pincode']*/'1', 'cust_vend_code');
        if ($custVendRow) {
            $credentials['cust_vend_code'] = $custVendRow['value'];
        } else {
            $lastValue = $courierServiceAccount->getExtraParamsLastValue('cust_vend_code');
            $registerResponse = (new Gati)->registerWarehouse($orderInfo, $credentials['code'], ++$lastValue);
            if (!$courierServiceAccount->saveExtraParams('cust_vend_code', $lastValue, $orderInfo['pickup_address']['pincode'])) {
                throw new \Exception("Save new cust vend code failed");
            }
            $credentials['cust_vend_code'] = $lastValue;
        }
        $awbDetail = $courierServiceAccount->getAWB();
        $awb = $awbDetail['awb'];
        $courierServiceAccountId = $courierServiceAccount->getId();
        $payloadResp = (new Gati)->prepareSchedulePickupPayload($orderInfo, $credentials, $awb, $serviceType);
        if ($payloadResp['success']) {
            $payload=$payloadResp['payload'];
        } else {
            throw new \Exception("Payload not generated successfully", 1);
        }
        $apiCallRawResponse =  \Utility\WCurl::post(static::$baseUrl.'/BT2GATI/BT2Gatipickup.jsp', '', $payload, $headers);
        $xml = self::object2array(simplexml_load_string($apiCallRawResponse['body']));
        if ($xml["result"]=="successful") {
            if ($xml['reqcnt'] > 0) {
                return array('success'=> true, 'data' => ['awb' => $awb, 'details' => 'success', 'courier_service_account_id' => $courierServiceAccountId]);
            } else {
                $error = "";
                if (isset($xml["details"]['res']["errmsg"]['err'])) {
                    if (is_array($xml["details"]['res']["errmsg"]['err'])) {
                        foreach ($xml["details"]['res']["errmsg"]['err'] as $err) {
                            $error = $error .  $err . ", ";
                        }
                    } else {
                        $error = $xml["details"]['res']["errmsg"]['err'];
                    }
                }
                if (stripos($error, 'INTERNAL ERROR java.lang.NumberFormatException') !== false || stripos($error, 'Docket was already uploaded') !== false) {
                    $awbBatch = new AWBBatch([$awbDetail['awbBatchId']]);
                    $awbBatch->logAWBEvent('failed', $awb);
                    $awbBatch->updateTableForFailedAwb();
                }
                throw new \Exception($error);
            }
        } else {
            $error ="";
            if (isset($xml['errmsg'])) {
                $error = $xml['errmsg'];
            }
            throw new \Exception($error);
        }
    }

    /**
     * Function to generate a string containing the payload in xml
     * @param array $order containing pick and drop details for the package
     * @param array $credentials credentials for courier service
     * @param string $awb awb to be used
     * @param string $serviceType service type (air/surface)
     * @return array $response with success and payload in xml string
     */
    private function prepareSchedulePickupPayload($order, $credentials, $awb, $serviceType)
    {
        $today = new DateTime('now');
        $courierAccount = $credentials;
        if (!isset($courierAccount['code'])) {
            throw new \Exception("Customer code not present", 1);
        }
        if (!isset($courierAccount['cust_vend_code'])) {
            throw new \Exception("cust_vend_code code not present", 1);
        }

        switch ($serviceType) {
            case 'air':
                $serviceCode = 2;
                break;
            
            case 'surface':
                $serviceCode = 1;
                break;

            #TODO remove the default case. only there for the time being for testing pupose
            default:
                $serviceCode = 2;
                break;
        }

        $goodsCode = '202';
        $orderQty = 0;
        $orderVal = 0;
        foreach ($order['shipment_details']['orders'] as $orderInd) {
            foreach ($orderInd['items'] as $item) {
                $orderQty += $item['quantity'];
            }
            $orderVal += (int) $orderInd['invoice']['value'];
        }
        if ($orderQty == 0) {
            throw new \Exception("Order quantity cannot be zero", 1);
        }
        if ($order['shipment_details']['weight'] == 0) {
            throw new \Exception("Weight Cannot be zero", 1);
        }

        $payloadArray = [
                'pickuprequest' => $today->format("d-m-Y H:i:s"),
                'custcode' => $courierAccount['code'],
                'details' => [
                    'req' => [
                        'DOCKET_NO' => $awb ,
                        'GOODS_CODE' => $goodsCode,
                        'DECL_CARGO_VAL' => $orderVal,
                        'ACTUAL_WT' => $order['shipment_details']['weight']/1000,
                        'CHARGED_WT' => $order['shipment_details']['weight']/1000,
                        'SHIPPER_CODE' => $courierAccount['code'],
                        'ORDER_NO' => $order['order_ref'],
                        'RECEIVER_CODE' => 99999,
                        'RECEIVER_NAME' => $order['drop_address']['name'],
                        'RECEIVER_ADD1' => $order['drop_address']['text'],
                        'RECEIVER_ADD2' => (isset($order['drop_address']['landmark']) && $order['drop_address']['landmark'] != '')?$order['drop_address']['landmark']: '-',
                        'RECEIVER_ADD3' => (isset($order['drop_address']['landmark']) && $order['drop_address']['landmark'] != '')?$order['drop_address']['landmark']: '-',
                        // 'RECEIVER_CITY' => $this->getCItyFromPincode($order['drop_address']['pincode']),
                        'RECEIVER_CITY' => isset($order['drop_address']['city']) ? isset($order['drop_address']['city']) : '-' ,
                        'RECEIVER_STATE' => $order['drop_address']['state'],
                        'RECEIVER_PHONE_NO' => $order['drop_address']['phone'],
                        'RECEIVER_EMAIL' => $order['pickup_address']['email_id'],
                        'RECEIVER_PINCODE' => $order['drop_address']['pincode'],
                        'NO_OF_PKGS' => 1,
                        'PKGDETAILS' => [
                            'PKG_INFO' => [
                                'PKG_NO' => $awb,
                                'PKG_LN' => $order['shipment_details']['length']/1000,
                                'PKG_BR' => $order['shipment_details']['breadth']/1000,
                                'PKG_HT' => $order['shipment_details']['height']/1000,
                                'PKG_WT' => $order['shipment_details']['weight']/1000,
                            ],
                        ],
                        'FROM_PKG_NO' => $awb,
                        'TO_PKG_NO' => $awb,
                        'RECEIVER_MOBILE_NO' => $order['drop_address']['phone'],
                        'CUSTVEND_CODE' => $courierAccount['cust_vend_code'],
                        'ORDER_QUANTITY' => $orderQty,
                        'SELLER_NAME' => $order['pickup_address']['name'],
                        'SELLER_ADD1' => $order['pickup_address']['text'],
                        'SELLER_ADD2' => (isset($order['pickup_address']['landmark']) && $order['pickup_address']['landmark'] != '')?$order['pickup_address']['landmark']: '-',
                        'SELLER_ADD3' => (isset($order['pickup_address']['landmark']) && $order['pickup_address']['landmark'] != '')?$order['pickup_address']['landmark']: '-',
                        // 'SELLER_CITY' => $this->getCItyFromPincode($order['pickup_address']['pincode']),
                        'SELLER_CITY' => isset($order['pickup_address']['city']) ? $order['pickup_address']['city'] : '-' ,
                        'SELLER_PINCODE' => $order['pickup_address']['pincode'],
                        'SELLER_STATE_CODE' => $this->getStateCode($order['pickup_address']['state']),
                        'SELLER_TINNO' => $order['shipment_details']['tin'],
                        'UOM' => 'CC',
                        'BOOKING_BASIS' => 1,
                        'PROD_SERV_CODE' => $serviceCode,
                    ]
                ]
        ];

        $payloadXml = SimpleXMLElementWrapper::arrayToXML($payloadArray);
        return array('success'=>true, 'payload'=>$payloadXml);
    }


    /**
     * Function to get the state code based on state name
     * @param string $state
     * @return string $stateCOde code for state
     */
    private function getStateCode($state)
    {
        foreach (static::$stateCodes as $stateName => $stateCode) {
            if (trim(strtolower($state))  ==  trim(strtolower($stateName))) {
                return $stateCode;
            }
        }
        return $state;
    }

    /**
     * Function to track the status of the shipment using $awb number.
     * @param string $trackingNumber the $awb number for the shipment to be tracked.
     * @return string $status The current status of the shipment
     */
    public static function trackShipment($trackingNumberArray)
    {
        $headers = array('Content-Type: text/xml');
        $payload = '';
        $trackingNumbers = implode(',', $trackingNumberArray);
        $apiCallRawResponse =  \Utility\WCurl::get($this->trackUrl, 'p1=' .$trackingNumbers . '&p2=85E564FED0FB0518', $payload, $headers);
        $xml = self::object2array(simplexml_load_string($apiCallRawResponse['body']));
        if (count($trackingNumberArray) == 1) {
            $xml['dktinfo'] = [
                $xml['dktinfo']
            ];
        }
        foreach ($xml['dktinfo'] as $info) {
            switch ($info['result']) {
                case 'failed':
                    $retArray[] = [
                        'awb' => $info['dktno'],
                        'result' => 'FAILED',
                        'errmsg' => $info['errmsg']
                    ];
                    break;

                case 'successful':
                    foreach ($info['TRANSIT_DTLS']['ROW'] as $transitDtls) {
                        $dateStr = $transitDtls['INTRANSIT_DATE'] . ' ' .  $transitDtls['INTRANSIT_TIME'];
                        $timestamp = \DateTime::createFromFormat('d-M-Y H:i', $dateStr)->getTimestamp();
                        $dktinfo[] = [
                            'timestamp' => $timestamp,
                            'location' => $transitDtls['INTRANSIT_LOCATION'],
                            'message' => $transitDtls['INTRANSIT_STATUS']
                        ];
                    }
                    $retArray[] = [
                        'awb' => $info['dktno'],
                        'result' => "SUCCESS",
                        'dktinfo' => $dktinfo,
                        'status' => reset($dktinfo)['message']
                    ];
                    break;
            }
        }
        return $retArray;
    }

    /**
     * Function to register new warehouse in gati with the newly generated cust_vend_code
     * @param mixed $order order details
     * @param string $custcode cust code
     * @param string cust vend code
     * @return mixed status and details of api call
     */
    private function registerWarehouse($order, $custcode, $newCustVendCode)
    {
        $headers = array('Content-Type: text/xml');
        $payloadResp = $this->prepareRegisterWarehousePayload($order, $custcode, $newCustVendCode);
        if ($payloadResp['success']) {
            $payload=$payloadResp['payload'];
        } else {
            throw new \Exception("Register Warehouse Payload not generated successfully", 1);
        }
        $apiCallRawResponse =  \Utility\WCurl::post(static::$baseUrl.'/GatiCustVendDtls.jsp', '', $payload, $headers);
        $xml = self::object2array(simplexml_load_string($apiCallRawResponse['body']));
        if ($xml["result"]=="successful") {
            if ($xml['reqcnt'] > 0) {
                return array('success'=> true, 'data' => ['custVendCode' => $xml['details']['res']['custVendorCode']]);
            } else {
                $error = "";
                if (isset($xml["details"]['res']["errmsg"]['err'])) {
                    if (is_array($xml["details"]['res']["errmsg"]['err'])) {
                        foreach ($xml["details"]['res']["errmsg"]['err'] as $err) {
                            $error = $error .  $err . ", ";
                        }
                    } else {
                        $error = $xml["details"]['res']["errmsg"]['err'];
                    }
                }
                throw new \Exception($error . ' while Register new warehouse');
            }
        } else {
            $error ="";
            if (isset($xml['errmsg'])) {
                $error = $xml['errmsg'];
            }
            throw new \Exception($error . ' while Register new warehouse');
        }
        return $retArray;
    }

    /**
     * Function to prepare payload for registere warehousr api
     * @param mxied $order orderdetails array
     * @param string $custcode cust code
     * @param string cust vend code
     * @return mixed generatedd payload array
     */
    private function prepareRegisterWarehousePayload($order, $custcode, $custVendCode)
    {
        $pickupAddress = $order['pickup_address'];
        $payloadArray = [
            // 'gati' => [
                'custCode' => $custcode,
                'details' => [
                    'req' => [
                        'custVendorCode' => $custVendCode ,
                        'custVendorName' => $pickupAddress['name'],
                        'vendorAdd1' => $pickupAddress['text'],
                        'vendorAdd2' => (isset($pickupAddress['landmark']) && $pickupAddress['landmark'] != '')?$pickupAddress['landmark']: '-',
                        'vendorAdd3' => (isset($pickupAddress['landmark']) && $pickupAddress['landmark'] != '')?$pickupAddress['landmark']: '-',
                        'vendorCity' => $this->getCItyFromPincode($pickupAddress['pincode']),
                        'vendorPhoneNo' => $pickupAddress['phone'],
                        'vendorPincode' => isset($pickupAddress['pincode']) ? $pickupAddress['pincode'] : '-',
                        'vendorEmail' => isset($pickupAddress['email']) ? $pickupAddress['email'] : '-',
                        'vendorReceiverFlag' => 'V'
                    ]
                ]
            // ]
        ];
        $payloadXml = SimpleXMLElementWrapper::arrayToXML($payloadArray);
        return array('success'=>true, 'payload'=>$payloadXml);
    }
}
