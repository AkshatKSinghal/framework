<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	require '/home/browntape/Projects/btpost' . '/autoloader/autoloader.php';

	// require_once('/home/browntape/Projects/btpost/Controllers/AWBBatch.php');
	try {
		$batchExecute = new \Controllers\AWBBatch([]);
		$batchExecute->createBatch('/home/browntape/Desktop/btpost.txt', 1, 1);
		//file, courierCompanyId, AccountId
		echo 'ending';		
	}catch (Exception $e) {
		echo '<br>';
		echo '<pre>';
		print_r ($e);
	}
