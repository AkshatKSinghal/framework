<?php

namespace Model;

use \DB\DB as DB

/**
* Class to manage Courier Service Accounts
*/
class CourierServiceAccount extends CourierService
{
	const ADMIN_ACCOUNT_ID = 0;
	public function getCourierCompany()
	{
		$courierServiceClass = get_parent_class();
		$courierService = new $courierServiceClass($this->getCourierServiceId());
		return $courierService->getCourierCompanyId();
	}

	public function getAdminAccount()
	{
		#TODO Do it via Model::find()
		$searchFiters = array(
			'account_id' => 0,
			'courier_service_id' => $this->getCourierServiceId()
			);
		$accountId = DB::searchOne($this->tableName(), $searchFiters, ['id']);
		if (empty($accountId)) {
			throw new Exception("Admin account not found for courier. Please contact admin.");
		}
		return new CourierServiceAccount($accountId);
	}

	public function getAWBBatch()
	{
		try {
			$batches = $this->get('AWBBatchId');
		} catch (\Exception $e) {
			#TODO Do it via Model::find()
			$query = "SELECT awb_batches.id FROM awb_batches_courier_service_accounts INNER JOIN awb_batches".
			" ON awb_batches.id = awb_batches_courier_service_accounts.awb_batch_id".
			" AND awb_batches_courier_service_accounts.account_id = " . $this->getAccountId().
			" WHERE awb_batches.status = 'PROCESSED'".
			" AND awb_batches.available_count > 0 ORDER BY awb_batches.available_count LIMIT 1";
			$result = DB::executeQuery($query);
			#TODO Extract the ID
			$this->setAWBBatch($awbBatchId);
			$this->save(false, 'AWBBatchId');
		} finally {
			return $awbBatchId;
		}
	}
}