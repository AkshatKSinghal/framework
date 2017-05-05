<?php

namespace Controllers;

use \Model\CourierService as CourierServiceModel;

/**
* Controller for Courier Services
*/
class CourierService extends CourierCompany
{

    /**
     * Function to return if the Courier Service and Courier Company
     * allows AWB pre-allocation or not
     *
     * @return bool $allowed based on the settings saved for the
     * courier service and courier company
     */

    public function preallocateAWBAllowed()
    {
        return $this->model->getSettings('AWBUpload');
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
            throw new Exception("Invalid Operation");
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

    public function create($data)
    {
        $service = new CourierServiceModel();
        $service->setCourierCompanyId($data['courierCompanyId']);
        $service->setOrderType($data['orderType']);
        $service->setServiceType($data['serviceType']);
        $service->setCredentialsRequiredJson($data['credentialsRequiredJson']);
        $service->setPincodes($data['pincodes']);
        $service->setStatus($data['status']);
        $service->setSettings($data['settings']);
        $service->validate();
        return $service->save();
    }
}
