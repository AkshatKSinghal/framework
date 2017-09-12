<?php

/**
* Class representing the User object
* User is the object initiating any request
* Required for verifying the access/ alteration rights to any resource
*/
class User extends Object
{
	
	function __construct($data)
	{
		if (empty($data['username'])) {
			throw new Exception("User Missing");
		}
		if (!empty($data['token'])) {
			$this->authorize($data['username'], $data['token']);
		} else {
			$this->token = $this->authenticate($data['username'], $data['password']);
		}
	}

	/**
	 * Function to authenticate the user
	 * @param string $username User Name
	 * @param string $password Plain Text Password
	 * 
	 * @throws Exception In case the $username $password combination is incorrect
	 * 
	 * @return string Token
	 */
	private function authenticate($username, $password)
	{

	}

	/**
	 * Function to validate the user token and check access for given API
	 * @param string $username User Name
	 * @param string $token Token Secret
	 * 
	 * @throws Exception In case the token is invalid
	 * 
	 * @return void
	 */
	private function authorize($username, $token)
	{

	}
}