<?php

namespace Cache;
/**
* Class to manage caching of objects
*/
class CacheManager extends \Redis
{
	private static $singleton = null;
	const DEFAULT_EXPIRY = 3 * 24 * 60 * 60;
	/**
	 * Function to instantiate, if not already done, 
	 * and return the singleton object
	 * 
	 * @return object $cache CacheManager Object
	 */
	public static function getInstance()
	{
		if (self::$singleton == null) {
			// instantiate the object
			self::$singleton = new \Redis(); 
			self::$singleton->connect('127.0.0.1', 6379); 
		}
		return self::$singleton;
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
	public static function getModelObject($model, $id)
	{
		$data = self::getHashData(self::getObjectKey($model, $id));
		$data[$model::primaryKeyName()] = $id;
		return $data;
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
	public static function setModelObject($object, $fields = [])
	{
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
		$cache = self::getInstance();
		$key = self::getObjectKey(get_class($object), $id);
		$cache->hMSet($key, $data);
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
	public static function getModelSchema($modelClass)
	{
		$cache = self::getInstance();
		return $cache->get(self::getModelPrefix($modelClass));
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
	public static function setModelSchema($modelClass, $schema)
	{
		$cache = self::getInstance();
		$cache->set(self::getModelPrefix($modelClass), $schema);
	}

	/**
	 * Function to get the Model Prefix
	 * 
	 * @return string $modelPrefix Key containing the schema of the model/
	 * Standard prefix for model keys
	 */
	private static function getModelPrefix($model)
	{
		return "O:". $model;
	}

	/**
	 * Function to get key for given model and id
	 * 
	 * @return string $key
	 */
	private static function getObjectKey($model, $id)
	{
		return Self::getModelPrefix($model) . ":$id";
	}

	/**
	 * Function to get all or selected fields from the given Hash
	 * 
	 * @param string $key identifier of the hash
	 * 
	 * @throws CacheMissException in case the hash is not found
	 * @throws CacheTypeException in case of key not of type hash
	 * 
	 * @return mixed $data data from Cache
	 */
	public static function getHashData($key, $fields = [])
	{
		$cache = self::getInstance();
		if (!$cache->exists($key)) {
			throw new CacheMissException("Not found");
		}
		if (!empty($fields)) {
			$data = $cache->hMget($key, $fields);
		} else {
			$data = $cache->hGetAll($key);
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
			throw new CacheMissException("Not found");
		}
		$unserializedValue = @unserialize($value);
		if ($unserializedValue === false) {
			$unserializedValue = $value;
		}
		return $unserializedValue;
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
		if ($serialize == true) {
			$value = serialize($value);
		} else if ($value != null && is_scalar($value)) {
				throw new CacheManagerTypeException("Cannot save data of type ". gettype($value) ." without serialization");
		}
		parent::set($key, $value, $expiry);
	}

	/**
	 * Function to check if an awb is present in the set for the given key
	 * 
	 * @param string $existingSet key of the exisiting set
	 * @param array $awb awb to be checked
	 * 
	 * @return void
	 */
	public static function existsInSet($existingSet, $awb)
	{
		return self::$singleton->sIsMember($existingSet, $awb);
	}

	/**
	 * Function to add the awb in redis
	 * 
	 * @param string $key
	 * @param array $awbSet Array of awb to be inserted in redis

	 * 
	 * @return void
	 */
	public static function addToSet($key, $awbSet)
	{
		// print_r($awbSet);
		foreach ($awbSet as $awb) {
			self::getInstance()->sAdd($key, $awb);
		}
		// #TODO use call_user_func_array to insert in batches of 10000 
		// echo call_user_func_array([self::getInstance(), 'sAdd'], [$key, $awbSet]);
	}
}

class CacheMissException extends \Exception
{
}