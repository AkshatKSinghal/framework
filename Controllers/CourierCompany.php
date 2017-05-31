<?php

namespace Controllers;

use \Model\CourierCompany as CourierCompanyModel;
use \Controllers\Base as BaseController;

/**
*
*/
class CourierCompany extends BaseController
{
    protected $fields = [
        'name' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'short_code' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'comments' => [
            'mandatory' => false,
            'data' => [],
            'multiple' => false
        ],
        'logo_url' => [
            'mandatory' => false,
            'data' => [],
            'multiple' => false
        ],
        // 'tracking_charge' => [
        //     'mandatory' => false,
        //     'data' => [],
        //     'multiple' => false
        // ],
        // 'booking_charge' => [
        //     'mandatory' => false,
        //     'data' => [],
        //     'multiple' => false
        // ],
        'status' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ]
    ];
    /**
     * Function for handling the calls to the courier classes
     *
     * @param string $functionName Name of the function being called
     * @param array $arguments Array of arguments with which the function has been invoked
     *
     * @throws Exception in case the function is not supported by the courier class
     * @throws Exception thrown by the function called finally
     *
     * @return mixed $response Response from the called function
     */
    public function __call($functionName, $arguments)
    {
        #TODO Get the proper class name
        if (!method_exists($courierClassName, $functionName)) {
            throw new Exception("Undefined method $functionName");
        }
        #TODO return call_user_func_array(array($courierClassName, $functionName), $arguments);
    }

    /*
    *
    */
    public function validAWBBatch($filePath)
    {
        return $filePath;
    }


    public function validateAWBFile($validFile, $invalidFile)
    {
        return ['valid' => 12, 'invalid' => 23];
    }


    /**
     * Function to track shipment on the Courier Service
     *
     * @param string $awb AWB number to be tracked
     *
     * @throws Exception in case the AWB number is not recognised by the Courier
     * @throws Exception in case the Tracking for Courier Service is not available
     * @throws Exception in case the Courier API gives unknown response/ error
     *
     * @return mixed $trackingInfo Tracking details of the shipment
     */
    protected function trackShipment($awb)
    {
        $response = $courierCompanyClass::trackShipment($awb);
        #TODO Standardise response
        return $response;
    }

    /**
     * Function to create a new object of the class using all the inputs from the request param
     * @param mixed $request
     * @return string id of the new object created
     */
    public function create($request)
    {
        $checkedData = $this->checkFields($request);
        $modelId = $this->setIndividualFields($checkedData);
        return $modelId;
    }
    
    /**
     * Function to set the fields to be inserted into the db from the incoming data and save the model
     * @param mixed $data
     * @return string $id id of the model created
     */
    protected function setIndividualFields($data, $new = true)
    {
        $modelClass = $this->getModelClass();
        if ($new) {
            $model = new $modelClass();        
        } else {
            $model = new $modelClass($this->model->getId());
        }
        $mapArray = [
            'name' => 'name',
            'short_code' => 'short_code',
            'comments' => 'comments',
            'logo_url' => 'logo_url',
            // 'tracking_charge' => 'tracking_charge',
            // 'booking_charge' => 'booking_charge',
            'status' => 'status'
        ];

        foreach ($mapArray as $dbField => $mergeFields) {
            $resultFields = [];
            $insertData = $data[$dbField];
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
        $courier = new CourierCompanyModel($id);
        return $courier;
    }

    public function getShortCode()
    {
        return $this->model->getShortCode();
    }

    public function getName()
    {
        return $this->model->getName();
    }

    /**
     * Function to get or create a new instance based on the params
     * @param mixed $params contaning key value pairs of the fields to be inserted
     * @return string id of the instance of the class called upon.
     */
    public static function getOrCreate($params)
    {
        $model = static::getModelClass();
        $modelObj = new $model;
        $class = get_called_class();
        if ($class == 'CourierServiceAccount') {
            $credentials = $params['credentials'];
            unset($params['credentials']);
        }
        $controllerObject = $modelObj->getByParam($params);
        if (empty($controllerObject)) {
            if ($class == 'CourierServiceAccount') {
                $params['credentials'] = $credentials;
            }
            $insertData = (new $class([]))->mapInsertFields($params);
            return (new $class([]))->setIndividualFields($insertData);
        } else {
            $class = get_called_class();
            // return new $class([$controllerObject[0]['id']]);
            return $controllerObject[0]['id'];
        }
    }

    /**
     * Function to map the inserting fields with the incoming and setting additional fields
     * @param mixed $params
     * @return mixed database fields to be inserted
     */
    public function mapInsertFields($params)
    {
        $insertData = [
            'name' => $params['name'],
            'short_code' => substr($params['name'], 0, 6),
            'comments' => '',
            'status' => 'ACTIVE',
            'logo_url' => 'ACTIVE'
        ];
        return $insertData;
    }

    /**
     * Function to get all the courier and services with admin account
     * @uses getCourierServices
     * @return mixed
     */
    public function getAdminCouriers()
    {
        $couriers = $this->model->getAll();
        $returnCouriers = [];
        foreach ($couriers as $courier) {
            $courierData['name'] = $courier['name'];
            $courierData['short_code'] = $courier['short_code'];
            $courierData['logo_url'] = $courier['logo_url'];
            $courierData['services'] = $this->getCourierServices();
            $returnCouriers[] = $courierData;
        }
        return $returnCouriers;
    }

    /**
     * Function to get the courier services for a particular courier company
     * @param string $courierId
     * @return mixed $returnServices
     */
    public function getCourierServices()
    {
        $courierId = $this->model->getId();
        $courierServices = (new CourierService([]))->getByCourierId($courierId);
        $returnServices = [];
        foreach ($courierServices as $service) {
            $serviceObject = new CourierService([$service['id']]);
            $servicesData['service_type'] = $serviceObject->getServiceType();
            $servicesData['order_type'] = $serviceObject->getOrderType();
            $servicesData['credentials_required_json'] = $serviceObject->getCredentialsRequiredJson();
            $courierAccount = $serviceObject->getAdminAccount();
            if ($courierAccount) {
                try {
                    $awbBatch = $courierAccount->getAWBBatch();
                    $servicesData['awb'] = $awbBatch->getAvailableCount();
                } catch (\Exception $e) {
                    $servicesData['awb'] = 0;
                }
            }
            $returnServices[] = $servicesData;
        }
        return $returnServices;
    }

    /**
     * save edit data for courier company
     * @uses setIndividualFields
     * @param mixed $data array contaning the fields and values
     * @return bool if saved or not
     */
    public function saveData($data)
    {
        $courierSaved = $this->setIndividualFields($data, false);
        if ($courierSaved) {
            $courierServices = (new CourierService([]))->getByCourierId($data['id']);
            foreach ($courierServices as $service) {
                $serviceObject = new CourierSservice([$service['id']]);
                $serviceObject->setCredentialsRequiredJson(json_encode($data['credentials_required']));
            } 
        }
    }

    /**
     * Function to update the services from ui
     * @param mixed $servicesData
     * @return bool true false
     */
    public function updateServices($servicesData)
    {
        $servicesInDb = $this->getCourierServices();
        foreach ($servicesInDb as $index => $service) {
            if (array_key_exists($service['id'], $servicesData)) {
                $serviceObject = new CourierService([$service['id']]);
                $service['courier_company_id'] = $this->model->getId();
                $serviceObject->setIndividualFields($service);
                unset($servicesData[$service['id']]);
            }
        }

        foreach ($servicesData as $service) {
            $serviceObject = new CourierService([]);
            $newService = $service;
            $newService['courier_company_id'] = $this->model->getId();
            $newService['pincodes'] = $this->model->getId();
            $newService['status'] = 'ACTIVE';
            $newService['class_name'] = $newService['service_type'];
            $serviceObject->create($newService);
        }
    }
}
