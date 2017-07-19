<?php

namespace Cache;

/**
* Class to manage caching of objects
*/
class CacheManager extends \Redis
{
    private $singleton = null;
    const DEFAULT_EXPIRY = 259200; //3 * 24 * 60 * 60
    private $transaction = false;
    private $transactionFunction = [];


    /**
     * Constructor to initialise the connection with Redis Server
     */
    public function __construct($config)
    {
        $this->connect($config['host'], $config['port']);
        if (!empty($config['password'])) {
            $this->auth($config['password']);
        }
        if (!empty($config['database'])) {
            $this->select($config['database']);
        }
    }

    /**
     * Function to start transaction in cache
     * @return void
     */
    public function startTransaction()
    {
        $this->transaction = true;
    }

    /**
     * Function to commit transaction in cache
     * @return void
     */
    public function commitTransaction()
    {
        $this->transaction = false;
        foreach ($this->transactionFunction as $key => $functionArray) {
            foreach ($functionArray as $functionName => $arguments) {
                call_user_func_array([$this, $functionName], $arguments);
            }
        }
    }

    /**
     * Function to rollback transaction in cache
     * @return void
     */
    public function rollbackTransaction()
    {
        $this->transactionFunction = [];
        $this->transaction = false;
    }

    /**
     * Function to enqueue operations in case the transaction is enabled
     * 
     * @param string $functionName Function Name being enqueued
     * @param array $params Params passed to the function being enqueued
     * 
     * @return void
     */
    private function enqueueOperation($functionName, $params)
    {
        $this->transactionFunction[] = [$functionName => $params];
    }

    /**
     * Function to get object data from cache for the given id
     *
     * @param string $id Primary ID of the model
     *
     * @throws CacheMissException in case the object does not exist in cache
     *
     * @return mixed $data object data from cache
     */
    public function getModelObject($model, $id)
    {
        $data = $this->getHashData($this->getObjectKey($model, $id));
        $data[$model::primaryKeyName()] = $id;
        return $data;
    }

    /**
     * Function to set the value for key in cache
     *
     * @param string $key
     * @param string $value Value to be saved
     * @param int $expire Expiry of the key [optional, default 3 days]
     * @param bool $serialize Flag to disable serialization of value
     * [optional, default true]
     *
     * @throws CacheManagerTypeException if $serialize is false
     * and $value is array/ object
     *
     * @return void
     */

    public function set($key, $value, $expiry = self::DEFAULT_EXPIRY, $serialize = true)
    {
        if ($this->transaction) {
            $this->enqueueOperation('set', func_get_args());
            return;
        }

        if ($serialize == true) {
            $value = serialize($value);
        } elseif ($value != null && is_scalar($value)) {
            throw new CacheManagerTypeException("Cannot save data of type ". gettype($value) ." without serialization");
        }
        parent::set($key, $value, $expiry);
    }
    
    /**
     * Function to add values into a set in redis
     *
     * @param string $key
     * @param mixed $values Value/ Array of values to be inserted in redis
     *
     * @return void
     */

    public function addToSet($key, $values)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        $values = array_chunk($values, 1000);
        foreach ($values as $value) {
            call_user_func_array([$this, 'sAdd'], $value);
        }
    }

    /**
     * Function to set the model data in cache
     *
     * @param object $model model object to be saved
     * @param string $id primary id of the model
     * @param array $fields list of fields to be saved [optional]
     *
     * @throws Exception in case the save operation fails
     *
     * @return void
     */
    public function setModelObject($object, $fields = [])
    {
        if ($this->transaction) {
            $this->enqueueOperation('setModelObject', func_get_args());
            return;
        } 
        if (empty($fields)) {
            $fields = $object->allFields();
        }
        $id = $object->getPrimaryKey();
        $data = [];
        foreach ($fields as $field) {
            $fieldKey = $field;
            $field = ucfirst($field);
            $data[$fieldKey] = $object->{"get$field"}();
        }
        $key = $this->getObjectKey(get_class($object), $id);
        $this->hMSet($key, $data);
    }


    /**
     * Function to get list of fields in the model table
     *
     * @param string $modelClass Class Name of the Model
     *
     * @throws CacheMissException in case key does not exist
     *
     * @return array $list
     */
    public function getModelSchema($modelClass)
    {
        return $this->get($this->getModelPrefix($modelClass));
    }

    /**
     * Function to set the model schema
     *
     * @param string $modelClass Class Name of the Model
     * @param mixed $schema Schema of the model
     * @throws Exception in case save operation fails
     *
     * @return void
     */

    public function setModelSchema($modelClass, $schema)
    {
        if ($this->transaction) {
            $this->enqueueOperation('setModelSchema', func_get_args());
            return;
        }
        $this->set($this->getModelPrefix($modelClass), $schema);
    }

    /**
     * Function to remove all keys of given model
     * To be used when the schema is updated for the model
     * 
     * @param string $modelClass Name of the Model Class
     * 
     * @return void
     */
    public function clearModelSchema($modelClass)
    {
        $this->delete($this->keys($this->getModelPrefix($modelClass) . "*"));
    }

    /**
     * Function to get the Model Prefix
     *
     * @return string $modelClass Prefix Key containing the schema of the model/
     * Standard prefix for model keys
     */

    private static function getModelPrefix($modelClass)
    {
        return "O:". $modelClass::shortName();
    }


    /**
     * Function to get key for given model and id
     *
     * @param string $modelClass Name of the Model Class
     * @param string $id Primary Identifier of the Model
     * 
     * @return string $key
     */

    private static function getObjectKey($modelClass, $id)
    {
        return self::getModelPrefix($modelClass) . ":$id";
    }


    /**
     * Function to get all or selected fields from the given Hash
     *
     * @param string $key identifier of the hash
     *
     * @throws CacheMissException in case the hash is not found
     * @throws CacheManagerTypeException in case of key not of type hash
     *
     * @return mixed $data data from Cache
     */

    public function getHashData($key, $fields = [])
    {
        $type = $this->type($key);
        if ($type == self::REDIS_NOT_FOUND) {
            throw new CacheMissException("Hash Not found");
        } else if ($type != self::REDIS_HASH) {
            throw new CacheManagerTypeException("Data Type Mismatch");
        }

        if (!empty($fields)) {
            $data = $this->hMget($key, $fields);
        } else {
            $data = $this->hGetAll($key);
        }
        return $data;
    }


    /**
     * Function to get value for given key in cache
     *
     * @param string $key
     *
     * @throws CacheMissException in case key is not found
     *
     * @return mixed $value deserialised value
     */

    public function get($key)
    {
        $value = parent::get($key);
        if ($value === false) {
            throw new CacheMissException("Key Not found");
        }
        $unserializedValue = @unserialize($value);
        if ($unserializedValue === false) {
            $unserializedValue = $value;
        }
        return $unserializedValue;
    }

    /**
     * Function to check if a value is present in the  given set
     *
     * @param string $existingSet key of the exisiting set
     * @param array $value Value to be checked
     *
     * @return bool $isMember Boolean if the value already exists in set or not
     */

    public function existsInSet($existingSet, $value)
    {
        return $this->sIsMember($existingSet, $value);
    }
}