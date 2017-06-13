<?php

namespace Controllers;

use \Model\CourierCompany as CourierCompanyModel;
use \Controllers\Base as BaseController;
use \Utility\FileManager as FileManager;

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
            throw new \Exception("Undefined method $functionName");
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
        // $file = $request['logo_url'];
        // if ($request['filename'] !== null) {
        //     if (!FileManager::validate($file, 'img')) {
        //         throw new \Exception("File not valid");
        //     }            
        // }
        $checkedData = $this->checkFields($request);
        $modelId = $this->setIndividualFields($checkedData);

        // $courierObject = new CourierCompany([$modelId]);

        if ($modelId) {
            // if ($request['filename'] !== null) {
            //     $type = FileManager::getExtensionFromName($request['filename']);
            //     $filePath = $this->getFilePath('logo_url', $modelId . $type);
            //     shell_exec("aws s3 cp $file $filePath ");
            //     $courierObject->model->setLogoUrl($modelId . $type);
            //     $courierObject->model->save();
            // }
            return $modelId;
        } else {
            throw new \Exception("not updated");
        }
    }
    
    /**
     * Function to get the file path to be uploaded
     * @return string filepath
     */
    private function getFilePath($type, $filename)
    {
        return "s3://btpost/img/$type/" . $filename;
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
    public static function getOrCreate($params, $create = true)
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
            if (!$create) {
                throw new \Exception("Courier service not found");
            }
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
        throw new \Exception("Courier service not found");
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
     * Function to get all the details of the model
     * @return mixed array contaning the fields
     */
    public function getDetails()
    {
        $courier['id'] = $this->model->getId();
        $courier['name'] = $this->model->getName();
        $courier['short_code'] = $this->model->getShortCode();
        $courier['comments'] = $this->model->getComments();
        $courier['logo_url'] = $this->model->getLogoUrl();
        $courier['status'] = $this->model->getStatus();
        return $courier;
    }

    /**
     * Function to get all the courier and services with admin account
     * @uses getCourierServices
     * @return mixed
     */
    public function getAdminCouriers($status)
    {
        $couriers = $this->model->getByParam(['status' => $status]);       
        $returnCouriers = [];
        switch ($status) {
            case 'active':
                $nextStatus = 'inactive';
                break;
            
            case 'inactive':
                $nextStatus = 'active';
                break;
        }

        $nextCount = $this->model->getByParam(['status' => $nextStatus]);

        $cnt = 0;
        foreach ($couriers as $courier) {
            $courierObject = new CourierCompany([$courier['id']]);
            $courierData = $courierObject->getDetails();
            // $courierData['id'] = $courier['id'];
            // $courierData['name'] = $courier['name'];
            // $courierData['short_code'] = $courier['short_code'];
            // $file = $this->getFilePath('logo_url', $courier['logo_url']);
            // $filepath = btpTMP . '/local/logo_url/' . $courier['logo_url'];
            // shell_exec("aws s3 cp $file $filepath");

            // $courierData['logo_url'] = $courier['logo_url'];
            // $courierData['comments'] = $courier['comments'];
            $awbDataAndServices = $courierObject->getCourierServicesAndAwb();
            $courierData['services'] = $awbDataAndServices['services'];
            $courierData['awb_data'] = $awbDataAndServices['awb_data'];
            $returnCouriers['couriers'][] = $courierData;
            $cnt++;
        }
        $returnCouriers['count'][$status] = $cnt;
        $returnCouriers['count'][$nextStatus] = count($nextCount);
        return $returnCouriers;
    }

    /**
     * Function to get the courier services for a particular courier company
     * @param string $courierId
     * @return mixed $returnServices
     */
    public function getCourierServicesAndAwb()
    {
        $courierId = $this->model->getId();
        $courierServices = (new CourierService([]))->getByCourierId($courierId);
        $returnServices = [];
        $combineAWB = [];
        foreach ($courierServices as $service) {
            $serviceObject = new CourierService([$service['id']]);
            $servicesData = $serviceObject->getDetails();
            // $servicesData['id'] = $service['id'];
            // $servicesData['service_type'] = $serviceObject->getServiceType();
            // $servicesData['code'] = $serviceObject->getCode();
            // $servicesData['status'] = $serviceObject->getStatus();
            // $servicesData['order_type'] = $serviceObject->getOrderType();
            // $servicesData['credentials_required_json'] = $serviceObject->getCredentialsRequiredJson();
            $courierAccount = $serviceObject->getAdminAccount();
            $codCount = $nonCodCount = $awbCount = 0;
            if ($courierAccount) {
                $awbData = [];
                // try {
                $courierAccountObject = new CourierServiceAccount([$courierAccount['id']]);
                $awbBatches = $courierAccountObject->getAllAWBBatches();
                foreach ($awbBatches as $awbBatch) {
                    if (isset($servicesData['awb_data'][$awbBatch])) {
                        $servicesData['awb_data'][$awbBatch]['service_types'].push($servicesData['service_type']);
                    } else {
                        $awbBatchObject = new AWBBatch([$awbBatch]);
                        $awbData['id'] = $awbBatch;
                        $awbData['service_types'] = [$servicesData['service_type']];
                        $awbData['timestamp'] = time();
                        $awbData['status'] = $awbBatchObject->getStatus();
                        $awbData['count'] = $awbBatchObject->getAvailableCount();
                        switch ($servicesData['order_type']) {
                            case 'cod':
                                $codCount += $awbData['count'];
                                break;

                            case 'prepaid':
                                $nonCodCount += $awbData['count'];
                                break;
                        }
                        $awbCount += $awbData['count'];
                    }
                    $combineAWB[$awbBatch] = $awbData;
                }
                // } catch (\Exception $e) {
                //     $servicesData['awb'] = 0;
                // }
            }
            $servicesData['awb'] = $awbCount;
            $servicesData['cod_count'] = $codCount;
            $servicesData['non_count'] = $nonCodCount;
            $returnServices[] = $servicesData;
        }
        return ['services' => $returnServices, 'awb_data' => $combineAWB];
    }

    /**
     * save edit data for courier company
     * @uses setIndividualFields
     * @param mixed $data array contaning the fields and values
     * @return bool if saved or not
     */
    public function saveData($data)
    {
        if (!isset($data['comments'])) {
            $data['comments'] = '';
        }
        if (!isset($data['logo_url'])) {
            $data['logo_url'] = '';
        }

        $courierSaved = $this->setIndividualFields($data, false);
        if ($courierSaved) {
            $courierServices = (new CourierService([]))->getByCourierId($data['id']);
            foreach ($courierServices as $service) {
                $serviceObject = new CourierService([$service['id']]);
                $serviceObject->setCredentialsRequiredJson($service['credentials_required_json']);
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
        $servicesInDb = $this->getCourierServicesAndAwb()['services'];

        $credentialsRequired = $servicesInDb[0]['credentials_required_json'];
        foreach ($servicesInDb as $index => $service) {
            $idArray = array_column($servicesData, 'id');
            $indexInData = array_search($service['id'], $idArray);
            if ($indexInData === false) {
                debug($service);
                $serviceObject = new CourierService([$service['id']]);
                $serviceObject->setStatus('inactive');
                $serviceObject->save();
            } else {
                $servicesFromData = $servicesData[$indexInData];
                $serviceObject = new CourierService([$service['id']]);
                $service['pincodes'] = '';
                $service['courier_company_id'] = $this->model->getId();
                $service['class_name'] = $serviceObject->getClassName();
                $service['settings'] = [];
                $service['code'] = $servicesFromData['code'];
                $service['order_type'] = $servicesFromData['order_type'];
                $service['service_type'] = $servicesFromData['service_type'];
                $serviceObject->setIndividualFields($service, false);
                array_splice($servicesData, $indexInData, 1);
            }
        }
        foreach ($servicesData as $service) {
            $serviceObject = new CourierService([]);
            // $newService = $service;
            $newService['courier_company_id'] = $this->model->getId();
            $newService['pincodes'] = '';
            $newService['status'] = 'ACTIVE';
            $newService['service_type'] = $service['service_type'];
            $newService['order_type'] = $service['order_type'];
            $newService['class_name'] = $service['service_type'];
            $newService['code'] = $service['code'];
            $newService['settings'] = [];
            $newService['credentials_required_json'] = $credentialsRequired;
            $serviceObject->create($newService);
        }
    }
}
