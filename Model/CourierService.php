<?php

namespace Model;

// use \Model\Courier
/**
* CRUD for Courier Service
*/
class CourierService extends CourierCompany
{
    protected static $tableName = 'courier_services';
    protected static $searchableFields = ['courier_company_id', 'service_type', 'order_type', 'status'];

    public function validate()
    {
    }

    /**
     * Function to get the setting keyy for a particaulara CourierService
     * @param string $key
     * @return string
     */
    public function getSettingsKey($key)
    {
        $settings = $this->getSettings();
        // json decode setting to retriev the key
        // return $settings;
        return 'pre';
    }
}
