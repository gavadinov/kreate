<?php

namespace Framework\Support;

use Framework\Support\Exception\InputException;
/**
 * Class for data holder representation
 *
 */
class Input
{
	private static $json = array(
		'get' => false,
		'post' => false
	);
	private static $instance;

	private static $validMethods = array(
		'GET',
		'POST',
		'FILES',
		'COOKIE'
	);

	/** Get param from GET
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($name = null, $default = null, $all = false)
	{
		if (! self::$json['get']) {
			if (isset($_GET['jsonRequest'])) {
				self::jsonParse($_GET['jsonRequest'], 'setGet');
			}
			self::$json['get'] = true;
		}

		if ($all || is_null($name)) {
			return $_GET;
		} else if (contains($name, '.')) {
			return getArrayDotNotation($name, $_GET, $default);
		} else if (isset($_GET[$name])) {
			return $_GET[$name];
		} else {
			return self::returnDefault($default);
		}
	}

	public static function post($name = null, $default = null, $all = false)
	{
		if (! self::$json['post']) {
			if (isset($_POST['jsonRequest'])) {
				self::jsonParse($_POST['jsonRequest'], 'setPost');
			}
			self::$json['post'] = true;
		}

		if ($all || is_null($name)) {
			return $_POST;
		} else if (contains($name, '.')) {
			return getArrayDotNotation($name, $_POST, $default);
		} else if (isset($_POST[$name])) {
			return $_POST[$name];
		}
		return self::returnDefault($default);
	}

	public static function files($name, $default = null, $all = false)
	{
		if ($all) {
			return $_FILES;
		} else if (isset($_FILES[$name])) {
			return $_FILES[$name];
		}
		return self::returnDefault($default);
	}

	public static function cookie($name, $default = null, $all = false)
	{
		if ($all) {
			return $_COOKIE;
		} else if (isset($_COOKIE[$name])) {
			return $_COOKIE[$name];
		}
		return self::returnDefault($default);
	}

	/** Set value for variable in $_GET
	 *
	 * @param string method - GET/POST/FILES/COOKIE
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setGet($name, $value)
	{
		$_GET[$name] = $value;
	}

	/** Set value for variable in $_POST
	 *
	 * @param string method - GET/POST/FILES/COOKIE
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setPost($name, $value)
	{
		$_POST[$name] = $value;
	}

	/** Set value for variable in $_COOKIE
	 *
	 * @param string method - GET/POST/FILES/COOKIE
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setCookie($name, $value)
	{
		$_COOKIE[$name] = $value;
	}

	/** Set value for variable in $_FILES
	 *
	 * @param string method - GET/POST/FILES/COOKIE
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setFiles($name, $value)
	{
		$_FILES[$name] = $value;
	}

	/**
	 * Sets a parameter in the bag if the given value is not null
	 *
	 * @param string $method
	 * @param string $name
	 * @param mixed $value
	 * @return boolean TRUE if the value is added to the bag. FALSE otherwise.
	 */
	public static function setIfNotNull($method, $name, $value)
	{
		if (! is_null($value)) {
			$this->set($method, $name, $value);
			return true;
		}
		return false;
	}

	/**
	 * Sets a parameter in the bag if the given value is not empty
	 * Uses empty() to determine if the value is empty
	 *
	 * @param string $method
	 * @param string $name
	 * @param mixed $value
	 * @return boolean TRUE if the value is added to the bag. FALSE otherwise.
	 */
	public static function setIfNotEmpty($method, $name, $value)
	{
		if (! empty($value)) {
			$this->set($method, $name, $value);
			return true;
		}
		return false;
	}

	/**
	 * Get all data form method
	 *
	 * @param string $method
	 * @param string $name
	 * @return array
	 */
	public static function all($method)
	{
		$method = strtolower($method);
		return self::$method(null, null, true); // return entire array
	}

	/**
	 * Unset value from $_GET
	 *
	 * @param string $name
	 */
	public static function clearGet($name)
	{
		$_GET[$name] = null;
	}

	/**
	 * Unset value from $_POST
	 *
	 * @param string $name
	 */
	public static function clearPost($name)
	{
		$_POST[$name] = null;
	}

	/**
	 * Unset value from $_COOKIE
	 *
	 * @param string $name
	 */
	public static function clearCookie($name)
	{
		$_COOKIE[$name] = null;
	}

	/**
	 * Unset value from $_FILES
	 *
	 * @param string $name
	 */
	public static function clearFiles($name)
	{
		$_FILES[$name] = null;
	}

	/**
	 * Returns default - value or functions
	 *
	 * @param mixed $default
	 * @return string
	 */
	private static function returnDefault($default = null)
	{
		if (is_callable($default)) {
			return $default->__invoke();
		}
		return $default;
	}

	/** Checks if method is from valid methods
	 *
	 * @param string $method
	 * @throws InputException
	 * @return string|boolean
	 */
	private static function methodResolver($method)
	{
		if (is_string($method)) {
			$method = strtoupper($method);
			if (in_array($method, self::$validMethods)) {
				$methodName = '_' . $method;
				return $methodName;
			} else {
				throw new InputException($method . ' is not valid method!');
			}
		}
		return false;
	}

	private static function jsonParse($json, $method)
	{
		$aJson = json_decode($json, true);
		foreach ($aJson AS $key => $value) {
			self::$method($key, $value);
		}
	}
}
