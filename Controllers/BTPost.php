<?php

/**
 * Controller for all external communications of the BTPost System
 * 
 */

class BTPost
{
	private var $accountID;

	function __construct($accountID)
	{
		$this->accountID = $accountID;
	}


	/**
	 * Function to upload AWB batch into a courier company
	 * 
	 * @param string $filePath Path of file on the disk
	 * 
	 * @return mixed $response AWB Batch Number if file is valid
	 * Error message in case the file has errors
	 * Error message in case Courier Company ID is invalid
	 */
	public function uploadAWB($filePath, $courierCompanyID)
	{
		// Check courier company ID, return error if invalid
	}


	/**
	 * Function to perform basic validations on the file
	 * i.e. Duplicates within the file, Invalid Characters, Empty lines etc
	 * 
	 * @param string $filePath Path of the file on disk
	 * 
	 * @throws Exception in case the file contains duplicates or invalid characters
	 * 
	 * @return void
	 */
	private function basicValidateFile($filePath)
	{
		#TODO Check for duplicates, throw exception in case duplicates within file are found
	}


	#TODO Put complete list of required parameters
	/**
	 * Function to create a new courier company
	 * 
	 * @param string $name Name of the courier company
	 * @param 
	 * 
	 * @return mixed $response Courier company information in case the creation is successful
	 * Error message in case the operation is unsuccessful
	 */
	public function createCourierCompany($name, $shortCode, $comments, $logoURL)
	{

	}

	public function getAccountId()
	{
		return $this->accountID;
	}


	/**
	 * Function to track shipment via BTPost Reference ID
	 * 
	 * @param string $refId BTPost Reference ID
	 * 
	 * @return mixed $response Tracking information as returned from trackShipmentViaAWB
	 * @see trackShipmentViaAWB
	 */
	public function trackShipment($refId)
	{
		#TODO check if the reference number belongs to the account
		#TODO get AWB and Courier Company ID
		return self::trackShipmentViaAWB($awb, $courierCompanyID);
	}

	/**
	 * API to track the AWB for given courier company
	 * 
	 * @param string $awb AWB to be tracked
	 * @param string $courierCompanyID ID of the courier company
	 * 
	 * @return mixed $reponse Tracking information for the AWB number 
	 */
	private function trackShipmentViaAWB($awb, $courierCompanyID)
	{
		#TODO Create courierCompany instance
		return $courierCompany->trackShipment($awb);
	}
}