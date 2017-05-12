<?php

namespace Model;

/**
* CRUD for Couriers
*/
class ShipmentDetail extends Base
{
    protected static $tableName = 'shipment_details';
    // protected $uniqueKeys = ['name', 'short_code'];
    protected static $searchableFields = ['order_ref', 'courier_service_account_id'];

    public function validate()
    {
    }
}
