<?php
namespace Lib;

use Framework\Foundation\Exception\AppException;

/**
 * JSON config adapter
 *
 
 */
class Config
{
	protected static $configs = array();

	/**
	 * Set new config in the static configs pool and returns it
	 *
	* @param string $name Later this config can be accessed by calling the static method $name
	 * @param string $path Path to the json file starting from app/config and without .json at the end
	 * @throws AppException
	 * @return array
	 */
	public static function load($name, $path, $refresh = false)
	{
		if (! empty(self::$configs[$name]) && ! $refresh) {
			return self::$configs[$name];
		}

		$path = preg_replace('/\./', '/', $path);
		$config = json_decode(file_get_contents(config_dir . $path . '.json'), true);

		if (empty($config)) {
			throw new AppException('Invalid JSON required in Config: ' . $path);
		}
		self::$configs[$name] = $config;

		return $config;
	}

	/**
	 * Magic method for accessing diferent configs
	 *
	* @param string $method
	 * @param array $args
	 * @return multitype
	 */
	public static function __callStatic($method, $args)
	{
		if (in_array($method, array_keys(self::$configs))) {
			$arr = self::$configs[$method];
			if (empty($args)) {
				return $arr;
			}
			$name = $args[0];
			$default = (isset($args[1]) ? $args[1] : null);

			if (isset($arr[$name])) {
				return $arr[$name];
			} else {
				return $default;
			}
		}
	}
}
