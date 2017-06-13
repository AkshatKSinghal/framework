<?php

namespace Controllers;

use \Controllers\AWBBatch;

/**
* Controller for Courier Service Accounts
*/
class CourierServiceAccount extends CourierService
{
    const ADMIN = 'ADMIN';
    const USER = 'USER';
    protected $fields = [
        'account_id' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'courier_service_id' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'awb_batch_mode' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'credentials' => [
            'mandatory' => false,
            'data' => [],
            'multiple' => false
        ],
        'pincodes' => [
            'mandatory' => false,
            'data' => [],
            'multiple' => false
        ],
        'status' => [
            'mandatory' => false,
            'data' => [],
            'multiple' => false
        ],
    ];
    /**
     * Function to get AWB for shipment booking
     *
     * @return string $awb AWB for the courier service
     *
     * @throws Exception if courier service does not support pre-allocation of AWB
     * @throws Exception if no AWB batches are available
     * @throws Exception if no AWBs are available in the batch
     *
     * @return string $awb AWB Number
     */
    public function getAWB()
    {
        try {
            $awbBatch = $this->getAWBBatch();
            return ['awb' => $awbBatch->getAWB(), 'awbBatchId' => $awbBatch->model->getId()];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        // return $awbBatch->getAWB();
    }


    /**
     * Function get return the AWB Batch to be used for getting the AWB
     *
     * The function would determine if the Courier Service Account is set
     * to use global AWB Batches or account specific Batches. Based on the
     * defined batch type, oldest batch with available AWBs would be returned
     *
     * @throws Exception if there is no AWB Batch available
     * with available count > 0
     * @throws Exception in case the AWB Batch Mode set is invalid
     *
     * @return AWBBatch $awbBatch AWBBatch Controller object
     */

    private function getAWBBatch()
    {
        $mode = $this->model->getAwbBatchMode();
        switch ($mode) {
            case self::ADMIN:
                $courierServiceAccount = $this->model->getAdminAccount();
                break;
            case self::USER:
                $courierServiceAccount = $this->model;
                break;
            default:
                throw new \Exception("Unknown AWB Batch Mode set");
                break;
        }
        try {
            $batchId = $courierServiceAccount->getAWBBatch();
            return new AWBBatch([$batchId]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public function create($request)
    {
        $checkedData = $this->checkFields($request);
        $modelId = $this->setIndividualFields($checkedData);
        return $modelId;
    }
    
    protected function setIndividualFields($data)
    {
        $modelClass = $this->getModelClass();
        $model = new $modelClass();
        $mapArray = [
            'account_id' => 'account_id',
            'courier_service_id' => 'courier_service_id',
            'awb_batch_mode' => 'awb_batch_mode',
            'credentials' => 'credentials',
            'pincodes' => 'pincodes',
            'status' => 'status',
        ];

        foreach ($mapArray as $dbField => $mergeFields) {
            $resultFields = [];
            $insertData = $data[$dbField];
            if ($dbField == 'credentials') {
                $insertData = json_encode($insertData);
            }
            $arr = explode('_', $dbField);
            $valueArr = [];
            foreach ($arr as $value) {
                $valueArr[] = ucfirst($value);
            }
            $ucdbField = implode('_', $valueArr);
            $key = (str_replace('_', '', /*ucwords($dbField, '_')*/$ucdbField));
            // $key = str_replace('_', '', ucwords($dbField, '_'));
            $functionName = 'set'.$key;
            $model->$functionName($insertData);
        }
        #TODO move last updated time to save funciton in base model
        $model->validate();
        return $model->save();
    }

    public static function getByAccountAndCourierService($accountId, $courierServiceId)
    {
        // $model = new CourierServiceModel()
        $model = self::getModelClass();
        $modelObj = new $model;

        $courierServiceAccount = $modelObj->getByParam([
            'account_id' => $accountId,
            'courier_service_id' => $courierServiceId
        ]);       
        if (empty($courierServiceAccount)) {
            throw new \Exception("Courier Service Account not found");
            
        }
        return new CourierServiceAccount([$courierServiceAccount[0]['id']]);
    }

    /**
     * Function to get the model by params
     * @param mixed $params associative array containing the key as fields and the value as the value to search
     * @return mixed Instance of CourierServiceAccount
     */
    public static function getByParams($params)
    {
        $model = self::getModelClass();
        $modelObj = new $model;

        $courierServiceAccount = $modelObj->getByParam($params);
        if (empty($courierServiceAccount)) {
            return false;
        } else {
            return new CourierServiceAccount([$courierServiceAccount[0]['id']]);
        }
    }

    public function mapAWBBatch($awbBatchId, $operation)
    {
        $this->model->mapAWBBatches($awbBatchId, $operation);
    }

    public function getCredentials()
    {
        $credentials = $this->model->getCredentials();
        return json_decode($credentials, true);
    }

    public function getCourierCompanyShortCode()
    {
        $courierService = $this->getCourierService();
        return $courierService->getCourierCompanyShortCode();
    }

    public function getCourierCompanyName()
    {
        $courierService = $this->getCourierService();
        return $courierService->getCourierCompanyName();
    }


    public function getCourierClassName()
    {
        $courierService = $this->getCourierService();
        return $courierService->getClassName();
    }

    public function getCourierService()
    {
        return new CourierService([$this->model->getCourierServiceId()]);
    }

    public function getShipmentFromOrderRef($orderRef)
    {
        $ships = \Controllers\ShipmentDetail::getFromOrderRefCourierServiceAccount($orderRef, $this->model->getId());
        return $ships;
    }

    public function getId()
    {
        return $this->model->getId();
    }

    /**
     * Function to map the inserting fields with the incoming and setting additional fields
     * @param mixed $params
     * @return mixed database fields to be inserted
     */
    public function mapInsertFields($params)
    {
        $insertData = [
            'account_id' => $params['account_id'],
            'courier_service_id' => $params['courier_service_id'],
            'awb_batch_mode' => $params['awb_batch_mode'],
            'credentials' => $params['credentials'],
            'pincodes' => '',
            'status' => 'ACTIVE'
        ];
        return $insertData;
    }

    public function setCredentials($credentialArray)
    {
        $this->model->setCredentials(json_encode($credentialArray));
        $this->model->save();
    }

    public function getCouriersByAccountId($accountId)
    {
        $courierAccounts = $this->model->getByParam(['account_id' => $accountId]);
        foreach ($courierAccounts as $courierAccount) {
            $courierService = $courierAccount->getCourierService();
            $courierCompany = $courierService->getCourierCompany();
        }
    }

    /**
     * Function to get extra params with matching column_name and description
     * @param string $description
     * @param string $columnName
     * @return return false/data
     */
    public function getExtraParams($description, $columnName)
    {
        return $this->model->getExtraParams($description, $columnName);
    }

    /**
     * Function to get the last value in account_extra_params with the defined coulmn name for this courierServiceAccount
     * @param string $columnName
     * @return string value column of the data
     */
    public function getExtraParamsLastValue($columnName)
    {
        $value = $this->model->getExtraParamsLastValue($columnName);

        if ($value) {
            return $value;
        } else {
            return false;
        }
    }

    /**
     * Function to save the extra params for the given columns name and account id
     * @param string $value
     * @param string $description
     * @param string $columnName
     * @return return false/true
     */
    public function saveExtraParams($columnName, $value, $description)
    {
        return $this->model->saveExtraParams($columnName, $value, $description);
    }
}
