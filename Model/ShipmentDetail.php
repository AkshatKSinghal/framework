<?php

namespace Model;

/**
* CRUD for Couriers
*/
class ShipmentDetail extends Base
{
    protected static $tableName = 'shipment_details';
	// protected $uniqueKeys = ['name', 'short_code'];

	public function validate()
	{
	}
}