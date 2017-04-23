<?php

/**
* Class to interface with MySQL
*/
class DB extends MySql
{
	
	private static var $singleton = null;
	private static var $transaction

	private function getInstance()
	{
		if ($singleton == null) {
			//Initiate DB singleton here
		}
		return $singleton;
	}

	/**
	 * Function to save model object into DB
	 * 
	 * @param object $object Model Object to be saved
	 * @param array $fields List of fields to be saved
	 * 
	 * @throws MySqlException in case of duplicate record
	 * @throws MySqlException in case of foreign key constraint failure 
	 * in child row
	 * @throws MySqlException in case of foreign key constraint failure
	 * in parent row
	 * @return string $id Primary ID of the record
	 */
	public function saveObject($object, $fields)
	{
		if ($object->isNew()) {
			// $query = INSERT QUERY
		} else {
			// $query = UPDATE QUERY
		}
		return $this->executeQuery($query);
	}

	/**
	 * Function to get values from DB based on the search criteria
	 * 
	 * @param string $tableName Name of the primary table to be queried on
	 * @param mixed $queryParams Filter parameters
	 * @param array $fieldList List of fields to be retrieved from the DB
	 * @param int $limit Limit on the number of records to be fetched 
	 * [optional, default 100]
	 * @param int $page Page number, for offset purpose [optional; default 1]
	 * 
	 * @throws MySqlException in case of $queryParams or $fieldList contains 
	 * tables not directly or indirectly connected to the primary table
	 * @throws MySqlException in case $page is less than 1
	 * 
	 * @return array $result Array of results
	 */
	public function search($table, $queryParams, $fieldList = [], $limit = 100, $page = 1)
	{

	}

	/**
	 * Function to get object from DB based on primary key
	 * 
	 * @param string $model Name of the model
	 * @param string $id Primary Key identifier
	 * @param array $fields List of fields to be retrieved from the DB
	 * 
	 * @throws MySqlException in case of $queryParams or $fieldList contains 
	 * tables not directly or indirectly connected to the primary table
	 * 
	 * @return mixed $result
	 */
	public function get($model, $id, $fields)
	{

	}

	/**
	 * Function to execute query and return the result
	 * 
	 * @param string $query Query to be executed
	 * 
	 * @throws MySqlException thrown by MySql
	 * 
	 * @return mixed $result result of the query executed
	 */
	public function executeQuery($query)
	{
		return $singleton::run($query);
	}
}