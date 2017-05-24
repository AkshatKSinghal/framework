 <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set("auto_detect_line_endings", true);

    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/vendor/malkusch/php-autoloader/autoloader.php';
    require __DIR__ . "/config.php";
    try {
        // upload awb;
        echo '****start*******';
        $batchExecute = new \Controllers\AWBBatch([]);
        $awbId = $batchExecute->createBatch(__DIR__ . '/upload.txt', 1, 1);
        var_dump($awbId);

        foreach ([10,13] as $courierServiceAccountId) {
            $csa = new \Controllers\CourierServiceAccount([$courierServiceAccountId]);
            $csa->mapAWBBatch($awbId, 'add');
        }
        echo '****uploaded*******';        
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
