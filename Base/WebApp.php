<?php


namespace \Base;

use \Utility\ObjectManager as ObjectManager;

/**
* Class to build the entry point for Web Application
*/
class WebApp extends Object
{
	
	function __construct($request)
	{
		$userData = [
			'username' => ObjectManager::getValue($request, 'username'),
			'password' => ObjectManager::getValue($request, 'password'),
			'token' => ObjectManager::getValue($request, 'token'),
		];
		$this->user = new \Base\User($userData);

		$requestData = [
			'path' => ObjectManager::getValue($_SERVER, 'REQUEST_URI', ObjectManager::getValue($request, 'path')),
			'data' => ObjectManager::getValue($request, 'data')
		];
	}
}