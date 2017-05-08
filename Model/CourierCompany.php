<?php

namespace Model;

/**
* CRUD for Couriers
*/
class CourierCompany extends Base
{
    protected static $tableName = 'courier_companies';
    // protected $uniqueKeys = ['name', 'short_code'];

    public function validate()
    {
        if (strlen($this->data['shortCode']) != 6) {
            throw new \Exception("Short Code must be of 6 Characters");
        }
    }
}
