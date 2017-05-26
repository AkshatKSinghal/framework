<?php
namespace Controllers\Couriers;

use \DateTime;

/**
*
*/
class Postmen extends Base
{
    protected static $baseUrl = 'https://sandbox-api.postmen.com/v3/';
    // private $supportedCariers = [
    //     'bluedart_dart_apex','bluedart_domestic_priority','bluedart_surface','aramex_deferred_express','aramex_domestic','aramex_ecommerce','aramex_economy_express','aramex_ground_express','aramex_priority_express', 'delhivery_standard', 'dtdc_b2c','dtdc_ecd','dtdc_eci','dtdc_ed','dtdc_edp','dtdc_en','dtdc_ied','dtdc_imd','dtdc_imp','fedex_2_day', 'fedex_2_day_am', 'fedex_2_day_am_one_rate', 'fedex_2_day_one_rate', 'fedex_distance_deferred', 'fedex_europe_first_international_priority', 'fedex_express_saver', 'fedex_express_saver_one_rate', 'fedex_first_overnight', 'fedex_first_overnight_one_rate', 'fedex_ground', 'fedex_ground_home_delivery', 'fedex_international_economy', 'fedex_international_first', 'fedex_international_priority', 'fedex_next_day_afternoon', 'fedex_next_day_early_morning', 'fedex_next_day_end_of_day', 'fedex_next_day_mid_morning', 'fedex_priority_overnight', 'fedex_priority_overnight_one_rate', 'fedex_same_day', 'fedex_same_day_city', 'fedex_standard_overnight', 'fedex_standard_overnight_one_rate'
    //     // 'bluedart' => ['bluedart_dart_apex','bluedart_domestic_priority','bluedart_surface'],
    //     // 'aramex' => ['aramex_deferred_express','aramex_domestic','aramex_ecommerce','aramex_economy_express','aramex_ground_express','aramex_priority_express'],
    //     // 'delhivery' => ['delhivery_standard'],
    //     // 'dtdc' => ['dtdc_b2c','dtdc_ecd','dtdc_eci','dtdc_ed','dtdc_edp','dtdc_en','dtdc_ied','dtdc_imd','dtdc_imp'],
    //     // 'fedex' => ['fedex_2_day', 'fedex_2_day_am', 'fedex_2_day_am_one_rate', 'fedex_2_day_one_rate', 'fedex_distance_deferred', 'fedex_europe_first_international_priority', 'fedex_express_saver', 'fedex_express_saver_one_rate', 'fedex_first_overnight', 'fedex_first_overnight_one_rate', 'fedex_ground', 'fedex_ground_home_delivery', 'fedex_international_economy', 'fedex_international_first', 'fedex_international_priority', 'fedex_next_day_afternoon', 'fedex_next_day_early_morning', 'fedex_next_day_end_of_day', 'fedex_next_day_mid_morning', 'fedex_priority_overnight', 'fedex_priority_overnight_one_rate', 'fedex_same_day', 'fedex_same_day_city', 'fedex_standard_overnight', 'fedex_standard_overnight_one_rate'],
    // ];
    /**
     * To check if the given courier type is supported and the it gives the service asked for
     * @param string $courier Name of the courier to be used
     * @param string $serviceType name of the service to be used
     * @return bool
     */
    public function checkCourierAndService($serviceType)
    {
        // if (array_key_exists($courier, $this->supportedCariers)) {
        if (in_array($serviceType, $this->supportedCariers)) {
            return true;
        }
        // }
        return false;
    }

    public static function bookShipment($orderInfo, $accountId, $courierServiceId, $serviceType)
    {
        $url = static::$baseUrl . 'labels';
        $method = 'POST';
		$courierServiceAccount = static::getCourierServcieAccount($accountId, $courierServiceId);

        $credentials = $courierServiceAccount->getCredentials();
        $courierServiceAccountId = $courierServiceAccount->getId();
        // if (!(new Postmen())->checkCourierAndService($serviceType)) {
        //     throw new \Exception("Courier supported not supported");
        // }

        $credentials['api_key'] = '25dddfc8-b793-4aa2-b13b-64963bee5820';
        if (!isset($credentials['api_key'])) {
            throw new \Exception("Api key not found");
        }
        $serviceType = 'fedex_express_saver_one_rate';
            $credentials['id'] = '5560a67f-5e2a-4081-b06e-a167acd11153';
        if (!isset($credentials['id'])) {
            // throw new \Exception("Api id not found");
        }
        $headers = array(
            "content-type: application/json",
            "postmen-api-key: " . $credentials['api_key']
        );
        $payload = (new Postmen())->prepareSchedulePickupPayload($orderInfo, $credentials, $serviceType);
        if ($payload['success']) {
            $body = $payload['payload'];
        } else {
            throw new \Exception("Payload not generated successfully", 1);
        }
        // die;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
        
        if ($err) {
            throw new \Exception('cURL error:', $err);
        } else {
            $responseArray = json_decode($response, true);
            if ($responseArray['meta']['code'] == 200) {
                return array('success'=> true, 'data' => ['awb' => $responseArray['data']['tracking_numbers'][0], 'details' => 'success', 'courier_service_account_id' => $courierServiceAccountId, 'courier_ref_id' => $responseArray['data']['id']]);
            } else {
                $errors = '';
                foreach ($responseArray['meta']['details'] as $error) {
                    $errors = $errors . ', ' .  $error['info'];
                }
                throw new \Exception($errors);
            }
        }
    }


    /**
     * Function to generate a string containing the payload in xml
     * @param array $order containing pick and drop details for the package
     * @param array $credentials credentials for courier service
     * @return array $response with success and payload in xml string
     */
    private function prepareSchedulePickupPayload($order, $credentials, $serviceType)
    {
        $orderType = 'signature';
        $orderQty = 0;
        $orderVal = 0;
        // $serviceType = 'aramex_ecommerce';

        foreach ($order['shipment_details']['orders'] as $orderInd) {
            foreach ($orderInd['items'] as $item) {
                $newItem = [];

                $orderQty += $item['quantity'];
                $newItem['description'] = $item['description'];
                $newItem['quantity'] = (int) $item['quantity'];
                $newItem['sku'] = $item['sku_id'];
                $newItem['price'] = [
                    'amount' =>  (float) $item['price'],
                    'currency' => 'EUR'
                ];

                $newItem['weight'] = [
                    'value' => (float) $order['shipment_details']['weight'] /10,
                    'unit' => 'g'
                ];
                $itemsArray[] = $newItem;
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
            "async"=> false,
            "return_shipment"=> false,
            "paper_size"=> "default",
            "service_type"=> $serviceType,
            "is_document"=> false,
            "billing"=> [
                "paid_by"=> "shipper"
            ],
            "customs"=> [
                // "billing"=> [
                //     "paid_by"=> "recipient"
                // ],
                "purpose"=> "gift"
            ],
            "service_options"=> [[
            //checnge this depending on cod or prepaid
                "type"=> $orderType,
                'enabled' => true
                // "cod_value"=> [
                //     "currency"=> "INR",
                //     "amount"=> (float) $order['cod_value']
                // ]
            ]],
            "shipper_account"=> [
                "id"=> $credentials['id']
            ],
            "references"=> [],
            "shipment"=> [
                "parcels"=> [[
                    "description"=> "Combined parcel",
                    "box_type"=> "custom",
                    "weight"=> [
                        "value"=> (float) $order['shipment_details']['weight'],
                        "unit"=> "g"
                    ],
                    "dimension"=> [

                        "width"=> (float) $order['shipment_details']['breadth'],
                        "height"=> (float) $order['shipment_details']['height'],
                        "depth"=> (float) $order['shipment_details']['length'],
                        "unit"=> "cm"
                    ],
                    "items"=> $itemsArray,
                ]],
                "ship_from"=> [
                    "contact_name"=> $order['pickup_address']['name'],
                    "company_name"=>  $order['pickup_address']['text'],
                    "email"=> "jameson@yahoo.com",
                    "phone"=> "12345678910",
                    "street1"=> (isset($order['pickup_address']['landmark']) && $order['pickup_address']['landmark'] != '')?$order['pickup_address']['landmark']: '-',
                    "city"=> $this->getCItyFromPincode($order['pickup_address']['pincode']),
                    "state"=> $order['pickup_address']['state'],
                    "postal_code"=>  $order['pickup_address']['pincode'],
                    "country"=> $order['pickup_address']['country'],
                    "type"=> "business",
                    "tax_id" =>  $order['shipment_details']['tin']
                ],
                "ship_to"=> [
                    "contact_name"=> $order['drop_address']['name'],
                    "phone"=> $order['drop_address']['phone'],
                    "email"=> 'archit.a@gmail.com',
                    "street1"=> $order['drop_address']['text'],
                    "city"=> $this->getCItyFromPincode($order['drop_address']['pincode']),
                    "postal_code"=> $order['drop_address']['pincode'],
                    "state"=> $order['drop_address']['state'],
                    "country"=> $order['drop_address']['country'],
                    "tax_id" =>  $order['shipment_details']['tin'],
                    "type"=> "business"
                ]
            ]
        ];
        $payloadJson = json_encode($payloadArray);
        return array('success'=>true, 'payload'=>$payloadJson);
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
        $trackingNumbers = (implode(',', $trackingNumberArray));
        // $trackingNumbers = '24d6c32a-337b-423c-adf0-076459d9abb8';
        // $trackingNumbers = '794696172810';
        $url = static::$baseUrl . 'labels/'. $trackingNumbers;
        $method = 'GET';
        $headers = array(
            "content-type: application/json",
            "postmen-api-key: 25dddfc8-b793-4aa2-b13b-64963bee5820"
        );
    
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ));
    
        $response = json_decode(curl_exec($curl), 1);
        $err = curl_error($curl);
    
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
            $retArray[] = [
                'awb' => $trackingNumbers,
                'result' => 'FAILED',
                'errmsg' => $err
            ];
        } else {
            $retArray[] = [
                'awb' => $trackingNumbers,
                'result' => "SUCCESS",
                'dktinfo' => $trackingNumbers,
                'status' => $response['data']['status']
            ];
        }
        return $retArray;
    }

    /**
     * Function to create a shipper account to get the credentials to be used in postmen
     * @return type
     */
    public function createShipperAccount()
    {
        $url = $this->baseUrl . 'shipper-accounts';
        $method = 'POST';
        $headers = array(
            "content-type: application/json",
            "postmen-api-key: 8fc7966b-679b-4a57-911d-c5a663229c9e"
        );
        $body = '{"slug":"fedex","description":"My Shipper Account","timezone":"Asia/Hong_Kong","credentials":{"account_number":"******","key":"******","password":"******","meter_number":"******"},"address":{"country":"USA","contact_name":"Sir Foo","phone":"2125551234","fax":"+1 206-654-3100","email":"foo@foo.com","company_name":"Foo Store","street1":"255 New town","street2":"Wow Avenue","city":"Beverly Hills","type":"business","postal_code":"90210","state":"CA","street3":"Boring part of town","tax_id":"911-70-1234"}}';
    
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body
        ));
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    /**
     * Function to prepare payload to create shipper account
     * @param type $order 
     * @param type $credentials 
     * @param type $serviceType 
     * @return type
     */
    private function prepareTrackPayload($order, $credentials, $serviceType)
    {
        $orderType = 'cod';
        $orderQty = 0;
        $orderVal = 0;
        // $serviceType = 'aramex_ecommerce';

        foreach ($order['shipment_details']['orders'] as $orderInd) {
            foreach ($orderInd['items'] as $item) {
                $newItem = [];

                $orderQty += $item['quantity'];
                $newItem['description'] = $item['description'];
                $newItem['quantity'] = (int) $item['quantity'];
                $newItem['sku'] = $item['sku_id'];
                $newItem['price'] = [
                    'amount' =>  (float) $item['price'],
                    'currency' => 'INR'
                ];

                $newItem['weight'] = [
                    'value' => (float) $order['shipment_details']['weight'] /10,
                    'unit' => 'g'
                ];
                $itemsArray[] = $newItem;
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
            "async"=> false,
            "return_shipment"=> false,
            "paper_size"=> "default",
            "service_type"=> $serviceType,
            "is_document"=> false,
            "billing"=> [
                "paid_by"=> "shipper"
            ],
            "customs"=> [
                "billing"=> [
                    "paid_by"=> "recipient"
                ],
                "purpose"=> "merchandise"
            ],
            "service_options"=> [[
            //checnge this depending on cod or prepaid
                "type"=> $orderType,
                "cod_value"=> [
                    "currency"=> "INR",
                    "amount"=> (float) $order['cod_value']
                ]
            ]],
            "shipper_account"=> [
                "id"=> $credentials['id']
            ],
            "references"=> [],
            "shipment"=> [
                "parcels"=> [[
                    "description"=> "Combined parcel",
                    "box_type"=> "custom",
                    "weight"=> [
                        "value"=> (float) $order['shipment_details']['weight'],
                        "unit"=> "g"
                    ],
                    "dimension"=> [

                        "width"=> (float) $order['shipment_details']['breadth'],
                        "height"=> (float) $order['shipment_details']['height'],
                        "depth"=> (float) $order['shipment_details']['length'],
                        "unit"=> "cm"
                    ],
                    "items"=> $itemsArray,
                ]],
                "ship_from"=> [
                    "contact_name"=> $order['pickup_address']['name'],
                    "company_name"=>  $order['pickup_address']['text'],
                    "email"=> "jameson@yahoo.com",
                    "phone"=> "12345678910",
                    "street1"=> (isset($order['pickup_address']['landmark']) && $order['pickup_address']['landmark'] != '')?$order['pickup_address']['landmark']: '-',
                    "city"=> $this->getCItyFromPincode($order['pickup_address']['pincode']),
                    "state"=> $order['pickup_address']['state'],
                    "postal_code"=>  $order['pickup_address']['pincode'],
                    "country"=> "USA",
                    "type"=> "business",
                    "tax_id" =>  $order['shipment_details']['tin']
                ],
                "ship_to"=> [
                    "contact_name"=> $order['drop_address']['name'],
                    "phone"=> $order['drop_address']['phone'],
                    "email"=> 'archit.a@gmail.com',
                    "street1"=> $order['drop_address']['text'],
                    "city"=> $this->getCItyFromPincode($order['drop_address']['pincode']),
                    "postal_code"=> $order['drop_address']['pincode'],
                    "state"=> $order['drop_address']['state'],
                    "country"=> "USA",
                    "tax_id" =>  $order['shipment_details']['tin'],
                    "type"=> "business"
                ]
            ]
        ];
        // echo '<pre>';
        // print_r($payloadArray);
        $payloadJson = json_encode($payloadArray);
        return array('success'=>true, 'payload'=>$payloadJson);
    }

}
