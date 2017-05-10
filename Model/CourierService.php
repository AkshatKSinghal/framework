<?php

namespace Model;

// use \Model\Courier
/**
* CRUD for Courier Service
*/
class CourierService extends CourierCompany
{
    protected static $tableName = 'courier_services';
    protected static $searchableFields = ['courier_company_id'];

    public function validate()
    {
    }

    public function getSettingsKey($key)
    {
        $settings = $this->getSettings();
        // json decode setting to retriev the key
        // return $settings;
        return 'pre';
    }
}
