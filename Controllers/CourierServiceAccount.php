<?php

/**
* Controller for Courier Service Accounts
*/
class CourierServiceAccount extends CourierService
{
    const ADMIN = 'ADMIN';
    const USER = 'USER';

    protected $mandatoryFields = ['accountId', 'courierServiceId'];
    protected $optionalFields = ['credentials', 'pincodes', 'status'];

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
        $awbBatch = $this->getAWBBatch();
        return $awbBatch->getAWB();
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
        switch ($model) {
            case self::ADMIN:
                $courierServiceAccount = $this->model->getAdminAccount();
                break;
            case self::USER:
                $courierServiceAccount = $this->model;
                break;
            default:
                throw new Exception("Unknown AWB Batch Mode set");
                break;
        }
        return new AWBBatch($courierServiceAccount->getAWBBatch());
    }


    public function create($request)
    {
        $mandatoryData = $this->checkFields($request, 'mandatory');
        $optionalData = $this->checkFields($request, 'optional');
        $checkedData = array_merge($mandatoryData, $optionalData);
        $modelId = $this->setIndividualFields($checkedData);
        return $modelId;
    }
    
    protected function setIndividualFields($data)
    {
        $modelClass = $this->getModelClass();
        $model = new $modelClass();
        foreach ($data as $key => $value) {
            $functionName = 'set'.$key;
            $model->$functionName($value);
        }
        $model->validate();
        return $model->save();
    }
}
