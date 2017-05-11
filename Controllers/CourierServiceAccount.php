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
            $key = str_replace('_', '', ucwords($dbField, '_'));
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
        return new CourierServiceAccount($courierServiceAccount[0]['id']);
    }

    public function mapAWBBatch($awbBatchId)
    {
        $this->model->mapAWBBatches($awbBatchId, 'add');
    }

    public function getCredentials()
    {
        $credentials = $this->model->getCredentials();
        return json_decode($credentials, true);
    }

    public function getCourierCompanyShortCode()
    {
        $courierService = new CourierService([$this->model->getCourierServiceId()]);
        return $courierService->getCourierCompanyShortCode();
    }

    public function getShipmentFromOrderRef($orderRef)
    {
        $ship = \Controllers\ShipmentDetail::getFromOrderRefCourierServiceAccount($orderRef, $this->model->id);
    }
}
