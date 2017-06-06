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

    /**
     * Function to get courier company from courier service account
     * @return string courier company id
     */
    public function getCourierCompany()
    {
        $courierServiceClass = get_parent_class();
        $courierService = new $courierServiceClass($this->getCourierServiceId());
        return $courierService->getCourierCompanyId();
    }

    /**
     * FUnction to get the admin account related to the courierServiceId
     * @return mixed object of courierServiceAccount
     */
    public function getAdminAccount()
    {
        #TODO Do it via Model::find()
        $searchFiters = array(
            'account_id' => 0,
            'courier_service_id' => $this->getCourierServiceId()
            );
        $accountId = (new DB())->searchOne($this->tableName(), $searchFiters, ['id']);
        if (empty($accountId)) {
            throw new \Exception("Admin account not found for courier. Please contact admin.");
        }
        return new CourierServiceAccount($accountId['id']);
    }

    /**
     * Function to get the awbbatch for the courierServiceAccount
     * @return string awbBatchId
     */
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

    /**
     * Function to map/unmap courierServiceAccountId with awb batch id
     * @param string $awbBatchId awbBatchId to be mapped
     * @param string $operation operation to be performed
     * @return void
     */
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
            if (DB::executeQuery($q) == 0) {
                throw new \Exception("Error Processing Request");
            }
        }
    }

    #TODO make a common function for extra params select insert update
    /**
     * Function to get extra params for the courier service account
     * @return mixed
     */
    public function getExtraParams($description, $columnName)
    {
        $query = "SELECT * FROM account_extra_params"
                ." WHERE courier_service_account_id = " . $this->getId()
                . " AND description = '" . trim($description) . "'"
                . " AND column_name = '" . trim($columnName) . "'";

        $extraParams = DB::executeQuery($query);
        $data = [];
        while ($row = $extraParams->fetch_assoc()) {
            $data[] = $row;
        }
        if (empty($data)) {
            return false;
        }
        return end($data);
    }

    /**
     * Function to get the last value in account_extra_params with the defined coulmn name for this courierServiceAccount
     * @param string $columnName
     * @return mixed data or false
     */
    public function getExtraParamsLastValue($columnName)
    {
        $query = "SELECT MAX(value) FROM account_extra_params"
                ." WHERE courier_service_account_id = " . $this->getId()
                . " AND column_name = '" . trim($columnName) . "'";

        $extraParams = DB::executeQuery($query);
        $data = [];
        while ($row = $extraParams->fetch_assoc()) {
            $data[] = $row;
        }
        if (empty($data)) {
            return false;
        }
        return end(end($data));
    }

    /**
     * Function to save the extra params value for the column name
     * @param string $columnName
     * @param string $value
     * @param string $description
     * @return mixed True or false
     */
    public function saveExtraParams($columnName, $value, $description)
    {
        $query = "INSERT INTO account_extra_params(courier_service_account_id, column_name, value, description)"
                ." values('" . $this->getId() . "', '" . trim($columnName) . "', '" . trim($value) . "', '" . trim($description) . "')";

        $extraParams = DB::executeQuery($query);
        return $extraParams;
    }
}
