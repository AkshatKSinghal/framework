<?php

namespace Controllers\Couriers;

use \Utility\SimpleXMLElementWrapper;
use \DateTime;

/**
*
*/
class Gati extends Base
{
    protected static $baseUrl = 'http://119.235.57.47:9080';
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
     * @param string $serviceCode Product service code via which the shipment is to be booked
     * @param array $credentials array credentials
     * @param string $awb AWB number to be assigned
     *
     * @throws Exception in case the order information is invalid/ incomplete
     * @throws Exception in case the pincodes are not serviceable
     * @throws Exception in case the Shipment Booking call fails from the Courier API side
     *
     * @return string $awb AWB number for the booked shipment
     */
    protected static function bookShipment($orderInfo, $serviceCode, $credentials, $awb)
    {
        $headers = array('Content-Type: text/xml');
        $payloadResp = (new Gati)->prepareSchedulePickupPayload($orderInfo, $credentials, $awb);

        if ($payloadResp['success']) {
            $payload=$payloadResp['payload'];
        } else {
            throw new \Exception("Payload not generated successfully", 1);
        }

        $apiCallRawResponse =  \Utility\WCurl::post(static::$baseUrl.'/BT2GATI/BT2Gatipickup.jsp', '', $payload, $headers);
        //XML Parse the response, try to find "success" in it
        $xml = self::object2array(simplexml_load_string($apiCallRawResponse['body']));
        if ($xml["result"]=="successful") {
            if ($xml['reqcnt'] > 0) {
                return array('success'=> true, 'data' => ['awb' => $awb, 'details' => 'success']);
            } else {
                $error = "";
                if (isset($xml["details"]['res']["errmsg"])) {
                    foreach ($xml["details"]['res']["errmsg"] as $err) {
                        $error = $error .  $err . ", ";
                    }
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
     * @return array $response with success and payload in xml string
     */
    private function prepareSchedulePickupPayload($order, $credentials, $awb)
    {
        $today = new DateTime('now');
        $courierAccount = $credentials;
        if (!isset($courierAccount['code'])) {
            throw new \Exception("Customer code not present", 1);
        }
        if (!isset($courierAccount['cust_vend_code'])) {
            throw new \Exception("cust_vend_code code not present", 1);
        }

        // array(
        //     'CourierAccount' => array(
        //         'code' => '54655501',
        //         'cust_vend_code' => 100001
        //         )
        //     );
        $goodsCode = '202';
        $orderQty = 0;
        $orderVal = 0;
        foreach ($order['shipment_details']['orders'] as $orderInd) {
            foreach ($orderInd['items'] as $item) {
                $orderQty += $item['quantity'];
            }
            $orderVal += (int) $orderInd['invoice']['value'];
        }
        // $orderQty = $order['shipment_details']['orders']['items']['quantity'];
        if ($orderQty == 0) {
            throw new \Exception("Order quantity cannot be zero", 1);
        }
        if ($order['shipment_details']['weight'] == 0) {
            throw new \Exception("Weight Cannot be zero", 1);
        }

        $payloadArray = [
            // 'gati' => [
                'pickuprequest' => $today->format("d-m-Y H:i:s"),
                'custcode' => $courierAccount['code'],
                'details' => [
                    'req' => [
                        'DOCKET_NO' => $awb,
                        'GOODS_CODE' => $goodsCode,
                        'DECL_CARGO_VAL' => $orderVal,
                        'ACTUAL_WT' => $order['shipment_details']['weight']/1000,
                        'CHARGED_WT' => $order['shipment_details']['weight']/1000,
                        'SHIPPER_CODE' => $courierAccount['code'],
                        'ORDER_NO' => $order['order_ref'],
                        'RECEIVER_CODE' => 99999,
                        'RECEIVER_NAME' => $order['drop_address']['name'],
                        'RECEIVER_ADD1' => $order['drop_address']['text'],
                        'RECEIVER_ADD2' => isset($order['drop_address']['landmark'])?$order['drop_address']['landmark']: '',
                        'RECEIVER_ADD3' => isset($order['drop_address']['landmark'])?$order['drop_address']['landmark']: '',
                        'RECEIVER_CITY' => $this->getCItyFromPincode($order['drop_address']['pincode']),
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
                        'SELLER_ADD2' => isset($order['pickup_address']['landmark'])?$order['pickup_address']['landmark']: '',
                        'SELLER_ADD3' => isset($order['pickup_address']['landmark'])?$order['pickup_address']['landmark']: '',
                        'SELLER_CITY' => $this->getCItyFromPincode($order['pickup_address']['pincode']),
                        'SELLER_PINCODE' => $order['pickup_address']['pincode'],
                        'SELLER_STATE_CODE' => $this->getStateCode($order['pickup_address']['state']),
                        'SELLER_TINNO' => $order['shipment_details']['tin'],
                        'UOM' => 'CC',
                        'BOOKING_BASIS' => 1,
                        'PROD_SERV_CODE' => 1,
                    ]
                ]
            // ]
        ];

        $payloadXml = SimpleXMLElementWrapper::arrayToXML($payloadArray);
        return array('success'=>true, 'payload'=>$payloadXml);
    }

    public function getCItyFromPincode($pincode)
    {
        return 'goa';
    }

    private static function object2array($object)
    {
        return json_decode(json_encode($object), 1);
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
    public static function trackShipment1($trackingNumberArray)
    {
        //Assume that the status recieved from Bluedart is "Delivered at Home". Replace with code to fetch from web service
        $o = static::options();
        // $trackingNumbers = implode(',', $trackingNumberArray);
        $get_response = static::get('http://www.gati.com/single_dkt_track_int.jsp?dktChoice=docketno&dktNo='.$trackingNumberArray, $o);
       
        $xpath = static::xpath($get_response['body']);
        
        $nodes = $xpath->query("//table/tr[2]/td[5]/a");
        $statusFromCourier='';
        if ($nodes->length) {
            $view_link=explode('=', $nodes->item(0)->getAttribute('onclick'));
            $get_response = static::get('http://www.gati.com/gatitrck.jsp?4='.$view_link['1'].'='.$trackingNumber, $o);
             
            $path=static::xpath($get_response['body']);
        
            $nodes = $path->query("//table[@class='form_table']/tr/td/a/b/text()");
            $status=explode('[', $nodes->item(0)->nodeValue);
            $statusFromCourier=str_replace(']', '', $status['1']);
        } else {
            throw new \Exception("Invalid\Data not found");
            
        }
        if (!empty($statusFromCourier)) {
            return [
                'Courier Name' => static::$name,
                'AWB' => $trackingNumber,
                'status' => $statusFromCourier
            ];
        } else {
            throw new \Exception("Status not recieved");
        }
    }

    public static function trackShipment($trackingNumberArray)
    {

        $headers = array('Content-Type: text/xml');
        // $payloadResp = (new Gati)->prepareSchedulePickupPayload($orderInfo, $credentials, $awb);

        // if ($payloadResp['success']) {
        //     $payload=$payloadResp['payload'];
        // } else {
        //     throw new \Exception("Payload not generated successfully", 1);
        // }
        $payload = '';

        $trackingNumbers = implode(',', $trackingNumberArray);
        $apiCallRawResponse =  \Utility\WCurl::post('http://www.gati.com/webservices/ECOMDKTTRACK.jsp?p1=' .$trackingNumbers . '&p2=123546BA90234561', '', $payload, $headers);
        print_r($apiCallRawResponse);
        die;
        //XML Parse the response, try to find "success" in it
        $xml = self::object2array(simplexml_load_string($apiCallRawResponse['body']));
        if ($xml["result"]=="successful") {
            if ($xml['reqcnt'] > 0) {
                return array('success'=> true, 'data' => ['awb' => $awb, 'details' => 'success']);
            } else {
                $error = "";
                if (isset($xml["details"]['res']["errmsg"])) {
                    foreach ($xml["details"]['res']["errmsg"] as $err) {
                        $error = $error .  $err . ", ";
                    }
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
}
