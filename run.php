 <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set("auto_detect_line_endings", true);

    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/vendor/malkusch/php-autoloader/autoloader.php';
    require __DIR__ . "/constants.php";
    try {
        echo '****start*******';
        // $batchExecute = new \Controllers\AWBBatch([]);
        // $awbId = $batchExecute->createBatch(__DIR__ . '/upload.txt', 1, 1);
        // var_dump($awbId);
        // die;

        $couriers = [
            'Gati' => [
                'standard',
                'non-standard'
            ],
            'Fedex' => [
                'fedex_2_day' ,
                'fedex_2_day_am' ,
                'fedex_2_day_am_one_rate' ,
                'fedex_2_day_one_rate' ,
                'fedex_distance_deferred' ,
                'fedex_europe_first_international_priority' ,
                'fedex_express_saver' ,
                'fedex_express_saver_one_rate' ,
                'fedex_first_overnight' ,
                'fedex_first_overnight_one_rate' ,
                'fedex_ground' ,
                'fedex_ground_home_delivery' ,
                'fedex_international_economy' ,
                'fedex_international_first' ,
                'fedex_international_priority' ,
                'fedex_next_day_afternoon' ,
                'fedex_next_day_early_morning' ,
                'fedex_next_day_end_of_day' ,
                'fedex_next_day_mid_morning' ,
                'fedex_priority_overnight' ,
                'fedex_priority_overnight_one_rate' ,
                'fedex_same_day' ,
                'fedex_same_day_city' ,
                'fedex_standard_overnight' ,
                'fedex_standard_overnight_one_rate'
            ]
        ];

        $credentials = [
            'Gati' => [
                'code' => '54655501',
                'cust_vend_code'=>'100001'
            ],
            'Fedex' => [
                'id' => 'f74497e8-e7d0-419f-ae95-d3f164f8cfd5',
                'api_key' => '25dddfc8-b793-4aa2-b13b-64963bee5820'
            ]
        ];
        //Courier COmpany create'

        foreach ($couriers as $courier => $services) {
            # code...
            $company = new \Controllers\CourierCompany([]);
            $companyId = $company->create([
                'name' => $courier,
                'short_code' => $courier,
                'comments' => 'no comments',
                'logo_url' => '$logoURL',
                'status' => 'ACTIVE',
            ]);

            foreach ($services as $service) {
                $company = new \Controllers\CourierService([]);
                foreach (['cod', 'prepaid'] as $orderType) {
                    $companyServiceId = $company->create([
                        'courier_company_id' => $companyId,
                        'service_type' => $service,
                        'order_type' => $orderType,
                        'credentials_required_json' => '',
                        'pincodes' => '',
                        'status' => 'ACTIVE',
                        'settings' => 'settings'
                    ]);
                    $account = new \Controllers\CourierServiceAccount([]);
                    $account_id =   $account->create([
                        'account_id' => '0',
                        'courier_service_id' => $companyServiceId,
                        'awb_batch_mode' => 'ADMIN',
                        'credentials' => $credentials[$courier],
                        'pincodes' => '7',
                        'status' => 'ACTIVE',
                    ]);
                }
            }
        }
        echo '******end******';
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
