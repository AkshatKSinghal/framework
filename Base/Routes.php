<?php

namespace Base;

/**
* Class to manage Routes. Defines the standard routing system
*/
class Routes
{
	/**
	 * Function to execute the request received by the app
	 * @param string $request Request URL
	 * @param array $params Params Received in the Request
	 * @param \Base\User $user User Executing the Request
	 * 
	 * @throws Exception In case the Route is missing
	 * 
	 * @return mixed Response of the request processed
	 */
	public static function dispatch($request, $params, $user = null)
	{

	}	
	
}