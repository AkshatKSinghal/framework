<?php

namespace Model;

use \DB\DB as DB;

/**
* Class to manage Courier Service Accounts
*/
class CourierServiceAccount extends CourierService
{
    protected static $tableName = 'courier_service_accounts';
    protected static $searchableFields = ['courier_service_id', 'account_id', 'awb_batch_mode'];
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
            'account_id' => $this->getAccountId(),
            'courier_service_id' => $this->getCourierServiceId()
            );
        $accountId = DB::searchOne($this->tableName(), $searchFiters, ['id']);
        if (empty($accountId)) {
            throw new \Exception("Admin account not found for courier. Please contact admin.");
        }
        return new CourierServiceAccount($accountId);
    }

    public function getAWBBatch()
    {
        $awbBatchId = '';
        try {
            $awbBatchId = $this->get('awbBatchId');
        } catch (\Exception $e) {
            #TODO Do it via Model::find()
            $query = "SELECT awb_batches.id FROM awb_batches_courier_services INNER JOIN awb_batches".
            " ON awb_batches.id = awb_batches_courier_services.awb_batch_id".
            " AND awb_batches_courier_services.courier_service_account_id = " . $this->getId().
            " WHERE awb_batches.status = 'PROCESSED'".
            " AND awb_batches.available_count > 0 ORDER BY awb_batches.available_count LIMIT 1";
            
            $result = DB::executeQuery($query);
            #TODO Extract the ID #Done
            $data = $result->fetch_assoc();
            if (empty($data)) {
                throw new \Exception("No AWB batch found for the account id");
            } else {
                $awbBatchId = $data['id'];
            }
            $this->setAwbBatchId($awbBatchId);
            $this->save(false, ['awbBatchId']);
        }
        return $awbBatchId;
    }

    public function mapAWBBatches($awbBatchId, $operation)
    {
        switch ($operation) {
            case 'set':
                $query[] = "DELETE FROM awb_batches_courier_services"
                ." WHERE courier_service_account_id = " . $this->getId();
            case 'add':
                $query[] = "INSERT INTO awb_batches_courier_services"
                ." (courier_service_account_id, awb_batch_id) VALUES ( '" .$this->getId()."', '". $awbBatchId . "')";
                break;
            case 'remove':
                $query[] = "DELETE FROM awb_batches_courier_services"
                ." WHERE courier_service_account_id = " . $this->getId()
                ." AND batch_id IN (" . implode(", ", $ids) . ")";
                break;
            default:
                throw new \Exception("Invalid operation in mapAWBBatches: " . $operation);
                break;
        }
        foreach ($query as $q) {
            DB::executeQuery($q);
        }
    }
}
