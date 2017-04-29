<?php

/**
* 
*/
class CourierCompany
{
	protected var $name;
	protected var $shortCode;
	protected var $status;
	protected var $info;

	protected var $courier;

	/**
	 * Function for handling the calls to the courier classes
	 * 
	 * @param string $functionName Name of the function being called
	 * @param array $arguments Array of arguments with which the function has been invoked
	 * 
	 * @throws Exception in case the function is not supported by the courier class
	 * @throws Exception thrown by the function called finally
	 * 
	 * @return mixed $response Response from the called function
	 */
	public function __call($functionName, $arguments)
	{
		#TODO Get the proper class name
		if (!method_exists($courierClassName, $functionName)) {
			throw new Exception("Undefined method $functionName");
		}
		#TODO return call_user_func_array(array($courierClassName, $functionName), $arguments);
	}

	/*
	*
	*/
	public function	validAWBBatch($filePath)
	{
		return $filePath;
	}


	public function validateAWBFile($validFile, $invalidFile)
	{
		return ['valid' => 12, 'invalid' => 23];
	}

	/**
	 * Function to return if the Courier Company allows AWB pre-allocation or not
	 * 
	 * @return bool $allowed based on the settings saved for the courier company
	 */
	public function preallocateAWBAllowed()
	{
		// return value based on the DB config
	}


	/**
	 * Function to track shipment on the Courier Service
	 * 
	 * @param string $awb AWB number to be tracked
	 * 
	 * @throws Exception in case the AWB number is not recognised by the Courier
	 * @throws Exception in case the Tracking for Courier Service is not available
	 * @throws Exception in case the Courier API gives unknown response/ error
	 * 
	 * @return mixed $trackingInfo Tracking details of the shipment
	 */
	protected function trackShipment($awb)
	{
		$response = $courierCompanyClass::trackShipment($awb);
		#TODO Standardise response
		return $response;
	}
}