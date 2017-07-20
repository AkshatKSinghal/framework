<?php

namespace Model;

use \Cache\CacheManager as CacheManager;
use \DB\DB as DB;
use \DB\FilterQuery as FilterQuery;
use \DB\FilterList as FilterList;

/**
* CRUD Operations
*/
class Base extends \Base\Object
{
    protected static $tableName = '';
    protected static $primaryKey = 'id';
    protected static $uniqueKeys = [];
    protected static $searchableFields = [];
    protected static $dbFields = [];
    protected $modifiedFields = [];
    protected $relativeValues = [];
    protected $data = [];
    protected $new = true;
    private static $databases;
    private static $currentDB;
    private static $cache;

    /**
     * Constructor to create model instance of existing record
     * OR new record
     *
     * @param string $id Primary Key of the record [optional]
     *
     * @throws ModelException in case the ID is invalid
     *
     */
    public function __construct($id = null, $data = null)
    {
        if ($id != null) {
            $this->new = false;
            try {
                if (!empty($this->cache)) {
                    $this->data = $this->cache->getModelObject(get_called_class(), $id);
                } else {
                    $this->data = $this->getDataFromDB($id);
                    return;
                }
            } catch (\Exception $ex) {
                $this->data = $this->getDataFromDB($id);
                $this->cache->setModelObject($this);
            }
        } elseif (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public static function setup($config)
    {
        $dbConfig = $config['\DB\DB'];
        $this->currentDB = $this->getDBHash($dbConfig);
        if (empty($this->db[$hash])) {
            $this->databases[$hash] = new DB($dbConfig);
        }

        $cacheConfig = $config['\Cache\Redis'];
        if (empty($this->cache)) {
            $this->cache = new CacheManager($cacheConfig);
        }
    }

    protected function getDBHash($config)
    {
        return $config['username'] . $config['hostname'] . $config['database'];
    }

    protected function getDB($config = null)
    {
        if (!empty($config)) {
            $this->setup($config);
        }
        return $this->databases[$this->currentDB];
    }

    protected function getDataFromDB($id = null)
    {
        if ($id == null) {
            $id = $this->getId();
        }

        $response = $this->getDB()->get(get_called_class(), $id);
        if ($response->num_rows == 0) {
            throw new \Exception("Invalid " . $this->primaryKeyName());
        }
        $data = [];
        while ($row = $response->fetch_assoc()) {
            foreach ($row as $key => $value) {
                $fieldName = $this->convertToPropertyName($key);
                $data[$fieldName] = $value;
            }
        }
        return $data;
    }


    /**
     * Function to convert the model property name into DB field name
     *
     * @param string $input Model Property Name
     *
     * @return string $dbFieldName Converted DB Field Name
     */

    public function convertToDBField($input)
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

    public function convertToPropertyName($input)
    {
        $arr = explode('_', $input);
        foreach ($arr as $value) {
            $valueArr[] = ucfirst($value);
        }
        $ucdbField = implode('_', $valueArr);
        $propertyName = lcfirst(str_replace('_', '', /*ucwords($dbField, '_')*/$ucdbField));
        return $propertyName;
        // $propertyName = lcfirst(str_replace('_', '', ucwords($input, '_')));
        // return $propertyName;
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
    public function save($validate = true, $fields = [])
    {
        if ($this->new) {
            $fields = array_keys($this->dbFields(false, true));
        }
        if (empty($fields)) {
            $fields = $this->modifiedFields;
        }

        if ($validate) {
            $this->validate($fields);
        }

        $compareArray = array_keys($this->dbFields(false, true));
        if (!empty(array_intersect($fields, $compareArray))) {
            $result = $this->getDB()->saveObject($this, $fields, $this->relativeValues);
            if (($this->new && $result) || !empty($this->relativeValues)) {
                $this->data = $this->getDataFromDB($result);
                $this->new = false;
            }
        }
        $this->modifiedFields = [];
        $this->relativeValues = [];
        $this->cache->setModelObject($this, $fields);
        return $this->getPrimaryKey();
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
        foreach ($this->dbFields() as $fieldName => $fieldInfo) {
            $propertyName = $this->convertToPropertyName($fieldName);
            $value = $this->get($propertyName);

            $limit = $fieldInfo['limit'];
            $values = $fieldInfo['values'];
            $nullable = $fieldInfo['null'];
            switch ($fieldInfo['type']) {
                case 'tinyint':
                    if ($limit == 1 && !is_bool($value)) {
                        throw new \Exception("Only boolean values allowed for $propertyName");
                    }
                    break;
                case 'float':
                case 'int':
                    $limit = pow(10, $limit);
                    if ($value > $limit) {
                        throw new \Exception("$propertyName cannot be greater than $limit");
                    }
                    break;
                case 'text':
                case 'varchar':
                    if (strlen($value) > $limit) {
                        throw new \Exception("Length of $propertyName cannot be greater than $limit");
                    }
                    break;
                case 'enum':
                    if (!in_array($value, $values)) {
                        throw new \Exception("Invalid value ". $value. " for $propertyName. Allowed values " . implode(", ", $values));
                    }
                    break;
                case 'timestamp':
                    if (!is_numeric($value)) {
                        throw new \Exception("Invalid format for $propertyName");
                    }
                    if ($timestamp > 2147483647 || $timestamp < 0) {
                        throw new \Exception("$propertyName should be between 01-01-1970 and 19-01-2038");
                    }
                    break;
                case 'date':
                case 'datetime':
                    #TODO Put the check here
                    break;
                default:
                    # code...
                    break;
            }
        }
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
    public static function primaryKeyName()
    {
        return static::$primaryKey;
    }

    /**
     * Function to get DB fields for the model
     *
     * @return array $fields List of DB Fields in the table
     * corresponding to the model
     */
    public static function dbFields($includePrimaryKey = true, $camel = false)
    {
        $fields = '';
        if (!isset(static::$dbFields[get_called_class()])) {
            try {
                $fields = $this->getModelSchema(get_called_class());
            } catch (\Exception $e) {
                $table = static::tableName();
                $fields = $this->getDB()->getDBSchema($table);
                $this->cache->setModelSchema(get_called_class(), $fields);
            } finally {
                static::$dbFields[get_called_class()] = $fields;
            }
        }
        if (!$includePrimaryKey) {
            unset(static::$dbFields[get_called_class()]['id']);
        }
        if ($camel) {
            $result = array();
            foreach (static::$dbFields[get_called_class()] as $key => $value) {
                $key = (new Base)->convertToPropertyName($key);
                $result[$key] = $value;
            }
            static::$dbFields[get_called_class()] = $result;
        }
        return static::$dbFields[get_called_class()];
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
        return $this->get($this->primaryKeyName());
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
        $parameterName = lcfirst(substr($functionName, 3));
        $functionName = substr($functionName, 0, 3);
        if ($functionName == "get") {
            return $this->get($parameterName);
        } elseif ($functionName == "set") {
            if (!isset($arguments[0])) {
                throw new \Exception("Missing value ". $parameterName);
            }
            return $this->set($parameterName, $arguments);
        } else {
            throw new \Exception("Invalid function ". $functionName);
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
            throw new \Exception("Invalid field name : " . $name);
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
        $this->data[$name] = $value[0];
        $this->modifiedFields[] = $name;
        if (isset($value[1]) && $value[1] == 'UPDATE') {
            $field = $this->convertToDBField($name);
            $this->relativeValues[$field] = $value[0];
        }
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
        if (!is_array($fields)) {
            $fields = array($fields);
        }
    }

    /**
     * Function to get the table name for the model
     * @return string $tableName
     */
    public static function tableName()
    {
        return static::$tableName;
    }

    /**
     * get all the searchableFIelds in a model.
     * @return mixed array containing fields for searching in snake case
     */
    public static function searchableFields()
    {
        return static::$searchableFields;
    }

    /**
     * Function to get all the fields in a model
     * @return mixed fields
     */
    public function allFields()
    {
        return array_keys($this->data);
    }

    public static function shortName()
    {
        $parts = explode("\\", get_called_class());
        return end($parts);
    }

    /**
     * Function to get by certain params
     * @param mixed $dataArray key value pair of the fields and value to be searched in the model
     * @return mixed data according to the search performed
     */
    public function getByParam($dataArray)
    {
        $sqlParts = [];
        $dbFields = static::searchableFields();
        foreach ($dbFields as $dbField) {
            if (isset($dataArray[$dbField])) {
                $filterQuery[] = new FilterQuery($dbField, $dataArray[$dbField], '=');
            }
        }
        $sqlWhere = new FilterList('AND', $filterQuery);
        $query = 'SELECT * from ' . self::tableName() . ' where ' . $sqlWhere->getSQL($this->getDB()->getInstance());
        $response = $this->getDB()->executeQuery($query);

        $data = [];
        while ($row = $response->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Function to get all the records and the selected fields of the model
     * @param mixed|null $fieldList list of fields to be selected
     * @return mixed data after fetching from db
     */
    public function getAll($fieldList = null)
    {
        if ($fieldList == null) {
            $fields = '*';
        } else {
            $fields = implode(', ', $fieldList);        
        }
        $query = 'SELECT '. $fields .' from ' . self::tableName();
        $response = $this->getDB()->executeQuery($query);

        $data = [];
        while ($row = $response->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Function to begin the transaction for DB
     * @return void
     */
    public function startTransaction()
    {
        $this->getDB()->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
        $this->cache->startTransaction();
    }

    /**
     * Function to commit the trasaction
     * @return void
     */
    public function commitTransaction()
    {
        $this->getDB()->commit();
        $this->cache->commitTransaction();
    }

    /**
     * function to rollback the trasaction
     * @return void
     */
    public function rollbackTransaction()
    {
        $this->getDB()->rollback();
        $this->cache->rollbackTransaction();
    }
}

/**
* Exceptions Related to Models
*/
class ModelException extends \Exception
{
    const DUPLICATE = 'DUPLICATE';
    const FOREIGN_KEY = 'FOREIGN_KEY';
    const SYNTAX = 'SYNTAX';
}
