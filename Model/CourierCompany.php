<?php

namespace Model;

/**
* CRUD for Couriers
*/
class CourierCompany extends Base
{
    protected static $tableName = 'courier_companies';
    // protected $uniqueKeys = ['name', 'short_code'];
    protected static $searchableFields = ['name'];

    public function validate()
    {
    }
}
