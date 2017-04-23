<?php

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
		// return value based on the DB config && CourierCompany->preallocateAWBAllowed();
	}

}