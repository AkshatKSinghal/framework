<?php

namespace Model;

/**
* CRUD for Couriers
*/
class CourierServiceAccount extends Base
{
    protected static $tableName = 'courier_service_accounts';
	// protected $uniqueKeys = ['name', 'short_code'];

	public function validate()
	{

	}
}