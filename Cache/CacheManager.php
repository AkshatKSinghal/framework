<?php

/**
* Class to manage caching of objects
*/
class CacheManager extends Redis
{
	private static $singleton = null;
	public const DEFAULT_EXPIRY = 3 * 24 * 60 * 60;
	/**
	 * Function to instantiate, if not already done, 
	 * and return the singleton object
	 * 
	 * @return object $cache CacheManager Object
	 */
	private static function getInstance()
	{
		if ($this->singleton == null) {
			// instantiate the object
		}
		return $this->singleton;
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
		$data[$model::getPrimaryKeyName()] = $id;
		return $data;
	}

	/**
	 * Function to set the model data in cache
	 * 
	 * @param object $model model object to be saved
	 * @param string $id primary id of the model
	 * @param array $fields list of fields to be saved [optional]
	 * 
	 * @return void
	 */
	public static function setModelObject($model, $id, $fields = [])
	{
		if (empty($fields)) {
			// $fields = serialise $model object to get all fields except primary key
		}
		$data = [];
		foreach ($fields as $field) {
			$data[$field] = $model->{"get$field"};
		}
		$cache = self::getInstance();
		$key = self::getObjectKey($model, $id);
		$cache->hMSet($key, $data);
	}


	/**
	 * Function to get list of fields in the model table
	 * 
	 * @throws CacheMissException in case key does not exist
	 * @return array $list
	 */
	public static function getModelSchema($model)
	{
		$cache = self::getInstance();
		$list = $cache->get(self::getModelPrefix($model));
		if ($list === false) {
			throw new CacheMissException("Not found", 1);
		}
		return $list;
	}

	/**
	 * Function to get the Model Prefix
	 * 
	 * @return string $modelPrefix Key containing the schema of the model/
	 * Standard prefix for model keys
	 */
	private static function getModelPrefix($model)
	{
		return "O:$model";
	}

	/**
	 * Function to get key for given model and id
	 * 
	 * @return string $key
	 */
	private static function getObjectKey($model, $id)
	{
		return $this->getModelPrefix($model) . ":$id";
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
		$cache = $this->getInstance();
		if ($cache->exists($key)) {
			throw new CacheMissException("Not found");
		}
		if (!empty($fields)) {
			$data = $cache->hmget($key, $fields);
		} else {
			$data = $cache->hgetall($key);
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
}