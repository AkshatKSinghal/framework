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
        // $courier = new \Controllers\CourierService();
        // echo $courier->create([
        //     'courierCompanyId' => '15',
        //     'serviceType' => 'air',
        //     'orderType' => 'prepaid',
        //     'credentialsRequiredJson' => '{asd:{asd:as}}',
        //     'pincodes' => '111111,11111121,121212',
        //     'status' => 'ACTIVE',
        //     'settings' => 'asdsadadsdasdasdlaskdhkas'
        // ]);
        $model = \Model\AWBBatch::dbFields();
        echo 'ending';
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
