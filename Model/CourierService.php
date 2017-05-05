<?php

namespace Model;

// use \Model\Courier
/**
* CRUD for Courier Service
*/
class CourierService extends CourierCompany
{
    protected static $tableName = 'courier_services';

	public function validate()
	{
		print_r($this->data);
		if (!in_array($this->data['serviceType'], ['surface', 'air', 'express'])) {
			throw new \Exception("Service Type can only be Surface air or express");						
		}
		if (!in_array($this->data['orderType'], ['prepaid', 'cod'])) {
			throw new \Exception("Order type can only be prepaid or cod");						
		}
	}
}
