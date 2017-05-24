<?php

namespace Controllers;

use \Model\CourierService as CourierServiceModel;

/**
* Controller for Courier Services
*/
class CourierService extends CourierCompany
{
    protected $fields = [
        'courier_company_id' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'service_type' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'order_type' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'credentials_required_json' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'pincodes' => [
            'mandatory' => false,
            'data' => [],
            'multiple' => false
        ],
        'status' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'settings' => [
            'mandatory' => true,
            'data' => [
                'awb_allocation_mode' => [
                    'data' => [],
                    'mandatory' => true,
                    'multiple' => false,
                ]
            ],
            'multiple' => false
        ],
    ];
    // protected $model;
    /**
     * Function to return if the Courier Service and Courier Company
     * allows AWB pre-allocation or not
     *
     * @return bool $allowed based on the settings saved for the
     * courier service and courier company
     */

    public function preallocateAWBAllowed()
    {
        return $this->model->getSettingsKey('AWBUpload');
    }


    /**
     * Function to handle bookShipment, schedulePickup and tracking requests
     *
     * @throws Exception in case the required service is not known
     * @throws Exception in case the required service is not supported/ integrated
     * for the courier
     *
     * @return mixed $response Response as per the function called
     */
    public function __call($function, $arguments)
    {
        if (!method_exists($this, $function)) {
            throw new \Exception("Invalid Operation: ". $function);
        }

        #TODO Determine the courier company class
        $this->checkService($courierCompanyClass, $function);
        return $this->$function($arguments[0]);
    }

    /**
     * Function to book shipment for the order via the given courier service
     *
     * @param mixed $orderInfo Array containing order(shipment) information
     *
     * @throws Exception in case the order information is invalid/ incomplete
     * @throws Exception in case the Tracking for Courier Service is not available
     * @throws Exception in case the Courier API gives unknown response/ error
     *
     * @return string $awb AWB number allocated by the courier service
     */
    protected function bookShipment($orderInfo)
    {
        $this->checkService($courierCompanyClass, "bookShipment");
    }


    /**
     * Function to check if the service is supported by the courier and is enabled
     *
     * @param string $service Service being requested i.e.
     * bookShipment, schedulePickup, tracking etc
     *
     * @throws Exception in case the service is not supported/ integrated
     * for the courier
     *
     * @return void
     */
    public function checkService($service)
    {
        #TODO get $courierCompanyClass,
        if (!class_exists($courierCompanyClass)
            || !method_exists($courierCompanyClass, $service)) {
            throw new Exception("$service service not available for the courier");
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
            'courier_company_id' => 'courier_company_id',
            'service_type' => 'service_type',
            'order_type' => 'order_type',
            'credentials_required_json' => 'credentials_required_json',
            'pincodes' => 'pincodes',
            'status' => 'status',
            'settings' => 'settings'
        ];

        foreach ($mapArray as $dbField => $mergeFields) {
            $resultFields = [];
            $insertData = $data[$dbField];
            if ($dbField == 'credentials_required_json' || $dbField == 'settings') {
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

    public function getById($id)
    {
        $service = new CourierService($id);
        return $service;
    }
    /**
     * Function to get the service type of the courier service
     * @return string servicType
     */
    public function getServiceType()
    {
        return $this->model->getServiceType();
    }

    public function getCourierCompanyShortCode()
    {
        $courierCompany = $this->getCourierCompany();
        return $courierCompany->getShortCode();
    }

    public function getCourierCompanyName()
    {
        $courierCompany = $this->getCourierCompany();
        return $courierCompany->getName();
    }

    public function getCourierCompany()
    {
        return new CourierCompany([$this->model->getCourierCompanyId()]);
    }

    /**
     * Function to map the inserting fields with the incoming and setting additional fields 
     * @param mixed $params
     * @return mixed database fields to be inserted
     */
    public function mapInsertFields($params)
    {
        $insertData = [
            'courier_company_id' => $params['courier_company_id'],
            'service_type' => $params['service_type'],
            'order_type' => $params['order_type'],
            'credentials_required_json' => '',
            'pincodes' => '',
            'settings' => json_encode(['awb_allocation_mode' => 'pre']),
            'status' => 'ACTIVE'
        ];
        return $insertData;
    }

    /**
     * Function to get the class name of the courier service
     * @return string classname
     */
    public function getClassName()
    {
        return $this->model->getClassName();
    }
}
