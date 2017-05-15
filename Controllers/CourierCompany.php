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
    protected function setIndividualFields($data)
    {
        $modelClass = $this->getModelClass();
        $model = new $modelClass();
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
            $key = str_replace('_', '', ucwords($dbField, '_'));
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
        $model = self::getModelClass();
        $modelObj = new $model;

        $controllerObject = $modelObj->getByParam($params);
        if (empty($controllerObject)) {
            $insertData = $this->mapInsertFields($params);
            return $this->setIndividualFields($insertData);
        } else {
            $class = get_class($this);
            return new $class($controllerObject[0]['id']);
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
            'status' => 'ACTIVE'
        ];
        return $insertData;
    }
}
