 <?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set("auto_detect_line_endings", true);

    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/vendor/malkusch/php-autoloader/autoloader.php';
    // require __DIR__ . "/config.php";
    try {
        // $batchExecute = new \Controllers\AWBBatch([]);
        // $batchExecute->createBatch(TMP . '/btpost.txt', 1, 1);
        //file, courierCompanyId, AccountId
        $ship = new \Controllers\ShipmentDetail([]);
        echo $ship->bookShipment([
            'order_ref' => 'AB6792BH2',
            'account_id' => '12342',
            'pickup_address' => [
                'name' => 'Pickup contact person name',
                'text' => '#301, Some Road Name, City Name',
                'landmark' => 'landmark text (optional)',
                'time' => 'epoch timestamp',
                'phone' => '9876543210',
                'pincode' => '145236',
                'email_id' => 'email id to be notified with updates',
                'state'=> 'Goa',
                'country'=> 'India'
            ],
            'drop_address' => [
                'name' => 'Drop contact person name',
                'pincode' => '145236',
                'text' => '#301, Some Road Name, City Name',
                'phone' => '9876543210',
                'landmark' => 'landmark text (optional)',
                'state'=> 'Goa',
                'country'=> 'India'
            ],
            'shipment_details' => [
                'orders' => [
                    'items' => [
                        [
                            'price'=> '1200.23',
                            'sku_id' => 'A152AFD',
                            'quantity' => '2',
                            'description' => 'item description (optional)'
                        ], [
                            'price'=> 'asdasd',
                            'sku_id' => 'A152AFD',
                            'quantity' => '2',
                            'description' => 'item description (optional)'
                        ]
                    ],
                    'invoice' => [
                        'ref_id' => '2017-18/ABC123',
                        'value' => '400.26',
                        'date' => '2017-04-03'
                    ]
                ],
                'length' => '20',
                'breadth' => '30',
                'height' => '24',
                'weight' => '350',
                'tin' => '02513642510',
                'type' => 'forward',
                'reason' => 'reverse pickup reason'
            ],
            'cod_value' => '120',
            'courier_service_id' => '12'
        ]);
        // $model = \Model\AWBBatch::dbFields();
        // echo 'ending';
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
