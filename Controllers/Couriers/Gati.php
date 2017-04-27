<?php

/**
* 
*/
class Gati extends Base
{
	
	/**
	 * Function to book a shipment on Gati
	 * 
	 * @param mixed $orderInfo Array containing the order information
	 * @param string $serviceCode Product service code via which the shipment is to be booked
	 * 
	 * @throws Exception in case the order information is invalid/ incomplete
	 * @throws Exception in case the pincodes are not serviceable
	 * @throws Exception in case the Shipment Booking call fails from the Courier API side
	 * 
	 * @return string $awb AWB number for the booked shipment
	 */
	protected static function bookShipment($orderInfo, $serviceCode)
	{
		#TODO get AWB number
		#TODO Call the Gati API
	}

	/**
	 * Function ot track a shipment on Gati
	 * 
	 * @param string $awb AWB Number to be tracked
	 * 
	 * @throws Exception in case the AWB number is rejected by Gati
	 * @throws Exception in case the Gati API throws an unknown error
	 * 
	 * @return mixed $trackingInfo Tracking information
	 */
	protected static function trackShipment()
	{

	}
}