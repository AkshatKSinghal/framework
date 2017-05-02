<?php

/**
* CRUD Operations
*/
class Base
{
	protected static $tableName = '';
	protected static $primaryKey = 'id';
	protected static $uniqueKeys = [];
	protected static $searchableFields = [];
	protected static $dbFields = [];
	protected $modifiedFields = [];
	protected $new = true;

	/**
	 * Constructor to create model instance of existing record
	 * OR new record
	 * 
	 * @param string $id Primary Key of the record [optional]
	 * 
	 * @throws ModelException in case the ID is invalid
	 * 
	 */
	function __construct($id = null, $data = null)
	{
		if ($id != null) {
			$this->new = false;
			try {
				$this->data = CacheManager::getModelObject(__CLASS__, $id);	
			} catch (Exception $ex) {
				#TODO Query the DB and put values into $this->data
				CacheManager::setModelObject($this, $this->getPrimaryKey());
			}
		} else if (!empty($data)) {
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}
		}

	}

	/**
	 * Function to instantiate object from an associative array
	 * 
	 * @param mixed $data associative array containing the model data
	 * 
	 * @throws ModelException in case $data does not contain primary key
	 * 
	 * @return 
	 */
	public static function loadData($data)
	{

	}

	/**
	 * Function to convert the model property name into DB field name
	 * 
	 * @param string $input Model Property Name
	 * 
	 * @return string $dbFieldName Converted DB Field Name
	 */
	private function convertToDBField($input)
	{
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}

	/**
	 * Function to convert DB Fields (snake case) to camelCase 
	 * for use in the model
	 * 
	 * @param string $input DB Field Name to be converted
	 * 
	 * @return string $propertyName converted string
	 */
	private function convertToPropertyName($input)
	{
		$propertyName = lcfirst(str_replace('-', '', ucwords($input, '-')));
		return $propertyName;
	}

	/**
	 * Function to save the model into DB
	 * 
	 * @param bool $validate Flag to disable validation
	 * @param array $fields Explicit list of fields to be saved
	 * 
	 * @throws ModelException in case the validation fails
	 * 
	 * @return string $id Primary Key of the record in the DB
	 */
	protected function save($validate = true, $fields = [])
	{
		if ($this->new) {
			$fields = $this->dbFields();
		} else if (empty($fields)) {
			$fields = $this->modifiedFields;
		}

		if ($validate) {
			$this->validate($fields);	
		}
		CacheManager::setModelObject(__CLASS__, $this->getPrimaryKey());
		return MySql::saveObject($this, $fields);
	}

	/**
	 * Function to validate the fields being saved
	 * 
	 * @param array $fields Explicit list of fields to be validated
	 * 
	 * @throws ModelException in case field validation fails
	 * 
	 * @return void
	 */
	protected function validate()
	{
		//Validation code for the DB fields
	}

	/**
	 * Function to return if the object is new (needs to be inserted into DB)
	 * or existing (record exists in DB)
	 * 
	 * @return bool $isNew flag is object is new or existing
	 */
	public function isNew()
	{
		return $this->new;
	}

	/**
	 * Function to get Primary Key field for the model
	 * 
	 * @return string $key
	 */
	public static function getPrimaryKeyName()
	{
		return self::$primaryKey;
	}

	/**
	 * Function to get DB fields for the model
	 * 
	 * @return array $fields List of DB Fields in the table 
	 * corresponding to the model
	 */
	protected static function dbFields()
	{
		if (!isset($this->dbFields)) {
			try {
				$fields = CacheManager::getModelSchema(__CLASS__);
			} catch (Exception $e) {
				$fields = '#TODO Get from DB';
				CacheManager::setModelSchema(__CLASS__, $fields);
			} finally {
				$this->dbFields = $fields;
			}	
		}
		return $this->dbFields;
	}

	/**
	 * Function to get the primary key of the object
	 * 
	 * @throws Exception in case the primary key does not exist (new object)
	 * 
	 * @return string $value Primary key of the object
	 */
	public function getPrimaryKey()
	{
		return $this->get($this->getPrimaryKeyName());
	}

	/**
	 * 
	 * Magic method to catch all get and set function calls
	 * 
	 * @param string $functionName Name of the function being called
	 * @param array $arguments list of arguments passed
	 * 
	 * @throws Exception in case the argument count is incorrects
	 * 
	 * @return mixed $response Response on the setter/ getter
	 */
	public function __call($functionName, $arguments)
	{
		$parameterName = substr($functionName, 3);
		$functionName = substr($functionName, 0, 3);

		if ($functionName == 'get'){
			return $this->get($parameterName);
		} else if ($functionName == 'set') {
			if (!isset($arguments[0])) {
				throw new Exception("Missing value");
			}
			return $this->set($parameterName, $arguments);
		} else {
			throw new Exception("Invalid function");
		}
	}

	/**
	 * Function to get a given field in the object
	 * 
	 * @throws Exception in case the field name is invalid
	 * 
	 * @return mixed $value Value of the field
	 */
	private function get($name)
    {
    	if (!array_key_exists($name, $this->data)) {
    		throw new Exception("Invalid field name");
    	}
        return $this->data[$name];
    }

    /**
     * Function to set the value for a given field
     * 
     * @return void
     */
    private function set($name, $value)
    {
    	$this->data[$name] = $value;
    	$this->modifiedFields[] = $name;
    }

    /**
     * Function to query the DB (table) based on given parameters
     * 
     * @param mixed $parameters Associative array containing the search parameters
     * @param array $fields List of fields to be fetched 
     * [optional, all fields would be fetched by default]
     * @param int $limit Limit on number of records fetched [optional, default value 10]
     * Passing zero would give all the results
     * @param int $offset Offset for the results to be returned
     * @param string $orderBy Field to sort the results by
     * [optional, defaults to primary key of the table]
     * @param bool $orderByAsc Flag if the results are to be sorted in asc or desc
     * 
     * @return array 
     */
    public function find($parameters, $fields = array(), $limit = 10, $offset = 0, $orderBy = null, $orderByAsc = true)
    {

    }
}

/**
* Exceptions Related to Models
*/
class ModelException extends Exception
{
	public const DUPLICATE = 'DUPLICATE';
	public const FOREIGN_KEY = 'FOREIGN_KEY';
	public const SYNTAX = 'SYNTAX';
}