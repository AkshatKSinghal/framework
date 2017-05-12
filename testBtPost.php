 <?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set("auto_detect_line_endings", true);

    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/vendor/malkusch/php-autoloader/autoloader.php';
    require __DIR__ . "/config.php";
    try {
        // upload awb;
        // $batchExecute = new \Controllers\AWBBatch([]);
        // $awbId = $batchExecute->createBatch(TMP . '/btpost.txt', 1, 1);

        // // $batchExecute = new \Controllers\AWBBatch([]);
        // $batchExecute->mapWithCourier([
        //     'courierServiceAccuntId' => 2,
        //     'awbBatchId' => $awbId
        // ]);

        //file, courierCompanyId, AccountId
        // $ship = new \Controllers\CourierServiceAccount([]);
        // var_dump($ship->create([
        //     'account_id' => '12',
        //     'courier_service_id' => '15',
        //     'awb_batch_mode' => 'ADMIN',
        //     'credentials' => [
        //         'code' => '54655501',
        //         'cust_vend_code'=>'100001',
        //     ],
        //     'pincodes' => '7',
        //     'status' => 'ACTIVE',
        // ]));die;

        // //file, courierCompanyId, AccountId
        // // $ship = new \Controllers\CourierServiceAccount([]);
        // // var_dump($ship->create([
        // //     'account_id' => '12',
        // //     'courier_service_id' => '15',
        // //     'awb_batch_mode' => 'ADMIN',
        // //     'credentials' => [
        // //         'code' => '54655501',
        // //         'cust_vend_code'=>'100001',
        // //     ],
        // //     'pincodes' => '7',
        // //     'status' => 'ACTIVE',
        // // ]));die;


        // book a shipment
        // $ship = new \Controllers\ShipmentDetail([]);
        // var_dump($ship->bookShipment([
        //     'order_ref' => '500000013',
        //     'account_id' => '12',
        //     'pickup_address' => [
        //         'name' => 'Pickup contact person name',
        //         'text' => '#301, Some Road Name, City Name',
        //         'landmark' => 'landmark text (optional)',
        //         'time' => 'epoch timestamp',
        //         'phone' => '9876543210',
        //         'pincode' => '110052',
        //         'email_id' => 'email id to be notified with updates',
        //         'state'=> 'Goa',
        //         'country'=> 'India'
        //     ],
        //     'drop_address' => [
        //         'name' => 'Drop contact person name',
        //         'pincode' => '500021',
        //         'text' => '#301, Some Road Name, City Name',
        //         'phone' => '9876543210',
        //         'landmark' => 'landmark text (optional)',
        //         'state'=> 'Goa',
        //         'country'=> 'India'
        //     ],
        //     'shipment_details' => [
        //         'orders' => [
        //             [
        //                 'items' => [
        //                     [
        //                         'price'=> '1200.23',
        //                         'sku_id' => 'A152AFD',
        //                         'quantity' => '2',
        //                         'description' => 'item description (optional)'
        //                     ], [
        //                         'price'=> 'asdasd',
        //                         'sku_id' => 'A152AFD',
        //                         'quantity' => '2',
        //                         'description' => 'item description (optional)'
        //                     ]
        //                 ],
        //                 'invoice' => [
        //                     'ref_id' => '2017-18/ABC123',
        //                     'value' => '400.26',
        //                     'date' => '2017-04-03'
        //                 ]
        //             ]
        //         ],
        //         'length' => '20',
        //         'breadth' => '30',
        //         'height' => '24',
        //         'weight' => '350',
        //         'tin' => '02513642510',
        //         'type' => 'forward',
        //         'reason' => 'reverse pickup reason'
        //     ],
        //     'cod_value' => '120',
        //     'courier_service_id' => '15',
        //     'awb' => '10',
        // ]));

        //Courier COmpany create
        // $company = new \Controllers\CourierCompany([]);
        // $companyId = $company->create([
        //     'name' => '$name',
        //     'short_code' => '$asda',
        //     'comments' => '$comments',
        //     'logo_url' => '$logoURL',
        //     'status' => 'ACTIVE',
        // ]);

        // track shipment
        $ship = new \Controllers\ShipmentDetail([]);
        var_dump($ship->trackShipmentByRef([
            'account_id' => '12',
            'order_ref' => '500000013',
            'courier_service_id' => '15'
        ]));

        //Map unmap courier services
        // $batch = new \Controllers\AWBBatch([$batchId]);
        // $batch->mapUnmapCourierService($operation, $courierServiceArray, $accountId);
        echo '******end******';
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
