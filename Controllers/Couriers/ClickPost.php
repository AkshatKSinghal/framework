<?php

/**
* 
*/
class ClickPost extends Base
{
	private static $services = array(
		'bookShipment' => array(
			),
		'schedulePickup' => array(
			),
		'tracking' => array(
			)
		);
	public static function serviceSupported($courierId, $service)
	{
		if (!isset(self::services[$service]) || !in_array($courierId, self::services[$service])) {
			throw new Exception("Service not supported");
		}
	}

	private static function bookShipment()
	{
		
	}
}