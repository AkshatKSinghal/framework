<?php
	$fp = fopen(__DIR__ . "/frombtapp/new_out.txt", "r");
    $awbArray = [];
	$cnt = 0;
    $couriers = [];
    $couriersArr = [
        '2' =>  'Afl',
        '3' =>  'Aramex',
        '5' =>  'Blue Dart ',
        '30' =>  'Delhivery ',
        '9' =>  'Dtdc  ',
        '10' =>  'Elbee ',
        '12' =>  'Fedex ',
        '13' =>  'First Flight ',
        '15' =>  'Gati  ',
        '35' =>  'GoJavas',
        '17' =>  'India Post',
        '18' =>  'Maruti',
        '20' =>  'Over Night Express ',
        '22' =>  'Pafex ',
        '34' =>  'Red Express  ',
        '24' =>  'Tmt',
        '27' =>  'United Courier  ',
        '28' =>  'Vichare',
        '29' =>  'Xps'
    ];

    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if ($cnt == 0) {
        	$cnt++;
        	continue;
        }
        // print_r($line);
        if ($line == '') {
        	continue;
        }
        $lineArray = preg_split("/[\t]/", $line);
        if ($lineArray[4] == '1') {
            continue;
            $used[] = $lineArray[1];
        }
        if ($lineArray[0] == 0 || $lineArray[3] == 0) {
            continue;
        }
        if ($lineArray[2] == '1') {
        	$arrayIndex = $lineArray[0] . '_cod_' . $lineArray[3];
        } else {
           	$arrayIndex = $lineArray[0] . '_prepaid_' . $lineArray[3];
        }
    	if (!isset($awbArray[$arrayIndex])) {
    		$awbArray[$arrayIndex] = [];
    	}
    	$awbArray[$arrayIndex][] = $lineArray[1];
        if (!in_array($lineArray[0], $couriers)) {
            $couriers[] = $lineArray[0];
        } 
    	$cnt++;
    }
    // file_put_contents(__DIR__ . '/frombtapp/'  . "used.txt", $used, FILE_APPEND);

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set("auto_detect_line_endings", true);

    $createdCouriers = [];
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/vendor/malkusch/php-autoloader/autoloader.php';
    require __DIR__ . "/constants.php";
    try {
        // upload awb;
        echo '****start*******';
        $bokk = new BTPost([]);

        foreach ($couriers as $courier) {
            $courierId = $bokk->createCourierCompany($couriersArr[$courier], substr($couriersArr[$courier], 0, 5), '', '');
            $serv1 = $bokk->createCourierService($courierId, [], '', [], 'ACTIVE', 'STANDARD', 'cod');
            $serv2 = $bokk->createCourierService($courierId, [], '', [], 'ACTIVE', 'STANDARD', 'prepaid');
            $createdCouriers[$courier] = [
                'cod' => $serv1,
                'prepaid' => $serv2,
                'courier' => $courierId
            ];
        }
        foreach ($awbArray as $key => $value) {
            $a = implode("\n", $value);

            file_put_contents(__DIR__ . '/frombtapp/' . $key . ".txt", $a, FILE_APPEND);
            $keyArray = explode('_', $key);
            $dataArray = [
                'account_id' => $keyArray[2],
                'service_id' => $createdCouriers[$keyArray[0]][$keyArray[1]]
            ];
            $csaccountId = $bokk->createCourierServiceAccount($dataArray);
            
            $batchExecute = new \Controllers\AWBBatch([]);
            $awbId = $batchExecute->createBatch(__DIR__ . '/frombtapp/'. $key . '.txt', $createdCouriers[$keyArray[0]]['courier'], $keyArray[2]);
            $csa = new \Controllers\CourierServiceAccount([$csaccountId]);
            $csa->mapAWBBatch($awbId, 'add');
            // die;
        }

        echo '****uploaded*******';
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
?>