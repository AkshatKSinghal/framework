<?php

/**
* 
*/
class ObjectManager
{
	/**
	 * Function to extract the value from given associative array
	 * 
	 * @param array $array Associative Array containing key value pairs
	 * @param string $key Key to be looked for
	 * @param mixed|null $default Default value in case the key is not found
	 * 
	 * @throws Exception In case $array is not Array
	 * 
	 * @return mixed|null Value at the given key/ default value in case key is not found
	 */
	public static function getValue($array, $key, $default = null)
	{
		if (!is_array($array)) {
			throw new Exception("array required");
		}

		if (isset($array[$key])) {
			return $array[$key];
		} else {
			return $default;
		}
	}
}