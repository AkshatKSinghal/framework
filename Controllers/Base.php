<?php

/**
* Base Controller for BTPost
*/
class Base
{
	private var $model;

	function __construct($request)
	{
		// Create Model if there is ID in the request
	}

	/**
	 * Function to save the data in the model object into the DB
	 * 
	 * @param bool $validate Flag to disable validation while saving the data
	 * @param array $fields List of parameters to be selectively updated into DB
	 * 
	 * @throws Exception if the validation fails
	 * @throws Exception if the model has not been instantiated yet
	 * 
	 * @return string id primary identifier of the record
	 */ 
	public function save($validate = true, $fields = null)
	{

	}
}