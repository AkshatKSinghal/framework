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

		if (!in_array($this->data['status'], ['ACTIVE', 'INACTIVE'])) {
			throw new \Exception("Status type can only be ACTIVE or INACTIVE");						
		}
	}
}