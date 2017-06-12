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
                    'mandatory' => false,
                    'multiple' => false,
                ]
            ],
            'multiple' => false
        ],
        'code' => [
            'mandatory' => true,
            'data' => [],
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
    public function checkService($courierCompanyClass, $service)
    {
        #TODO get $courierCompanyClass,
        if (!class_exists($courierCompanyClass)
            || !method_exists($courierCompanyClass, $service)) {
            throw new \Exception("$service service not available for the courier");
        }
    }

    public function create($request)
    {
        $checkedData = $this->checkFields($request);
        $checkedData['class_name'] = isset($checkedData['class_name']) ? $checkedData['class_name'] : '';
        $modelId = $this->setIndividualFields($checkedData);
        return $modelId;
    }
    
    protected function setIndividualFields($data, $new = true)
    {
        $modelClass = $this->getModelClass();
        if ($new) {
            $model = new $modelClass();        
        } else {
            $model = new $modelClass($this->model->getId());
        }
        $mapArray = [
            'courier_company_id' => 'courier_company_id',
            'service_type' => 'service_type',
            'order_type' => 'order_type',
            'credentials_required_json' => 'credentials_required_json',
            'pincodes' => 'pincodes',
            'status' => 'status',
            'settings' => 'settings',
            'class_name' => 'class_name',
            'code' => 'code'
        ];
        $data['class_name'] = '';
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

    /**
     * Function to get the service code used by courier
     * @return string code
     */
    public function getCode()
    {
        return $this->model->getCode();
    }

    /**
     * Function to get the status used by courier
     * @return string code
     */
    public function getStatus()
    {
        return $this->model->getStatus();
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
     * fUNCTION TO get the ordertype of the CourierSservice object
     * @return type
     */
    public function getOrderType()
    {
        return $this->model->getOrderType();
    }

    /**
     * fUNCTION TO get the credentials required of the CourierSservice object
     * @return type
     */
    public function getCredentialsRequiredJson()
    {
        return json_decode($this->model->getCredentialsRequiredJson());
    }

    /**
     * fUNCTION TO set the credentials required of the CourierSservice object
     * @return type
     */
    public function setCredentialsRequiredJson($credentials)
    {
        $this->model->setCredentialsRequiredJson($credentials);
        $this->model->save();
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

    /**
     * Function to get services associated to the courier id supplied
     * @param string $courierId 
     * @return mixed services
     */
    public function getByCourierId($courierId)
    {
        $model = static::getModelClass();
        $modelObj = new $model;
        $services = $modelObj->getByParam(['courier_company_id' => $courierId, 'status' => 'active']);
        return $services;
    }

    /**
     * Function get the admin account associated with the courier service
     * @return mixed courierServiceAccount or false if not found
     */
    public function getAdminAccount()
    {
        $courierAccount = CourierServiceAccount::getByParams([
            'account_id' => 0,
            'courier_service_id' => $this->model->getId()
        ]);
        return $courierAccount;
    }

    /**
     * Function to set status of the service to active or inactive
     * @param type $status 
     * @return type
     */
    public function setStatus($status)
    {
        $this->model->setStatus($status);
    }
}
