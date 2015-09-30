<?php

use Framework\Factory\AdapterFactory;
use Framework\Http\Request;

/**
 * Dump and Die
 *
 * @param  dynamic  mixed
 * @return void
 */
function dd()
{
	echo '<pre>';
	array_map(function($x) { var_dump($x); }, func_get_args());
	die;
}

/**
 * Pretty Print
 *
 * @param  dynamic  mixed
 * @return void
 */
function pp()
{
	echo '<pre>';
	array_map(function($x) { print_r($x); }, func_get_args());
}

function backtrace()
{
	echo "<pre>";
	var_dump(array_map(function($x) { return array($x['file'] => $x['line']); }, debug_backtrace()));
	die;
}

/**
 * Get key from array or get default value
 *
 *
 * @param array $array
 * @param mixed $value
 * @param string $default
 * @return Ambigous <string, unknown>
 */
function get(array $array, $key, $default = null)
{
	if (contains($key, '.')) {
		return getArrayDotNotation($key, $array, $default);
	}
	return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Return the passed object. Useful for chaining.
 *
 * @param Object $object
 * @return Object
 */
function chain($object)
{
	return $object;
}

/**
 * Checks if a string ends with a given substring
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function endsWith($haystack, $needle)
{
	if ($needle == substr($haystack, -strlen($needle))) return true;
	return false;
}

/**
 * Checks if a string starts with a given substring
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function startsWith($haystack, $needle)
{
	if ($needle != '' && strpos($haystack, $needle) === 0) return true;
	return false;
}

/**
 * Checks if a string contains a given substring
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function contains($haystack, $needle)
{
	if ($needle != '' && strpos($haystack, $needle) !== false) return true;
	return false;
}

/**
* Convert strings with underscores into CamelCase
*
* @param    string    $string    The string to convert
* @param    bool    $first_char_caps    camelCase or CamelCase
* @return    string    The converted string
*/
function underscoreToCamelCase($string, $first_char_caps = false)
{
	if( $first_char_caps == true ) {
		$string[0] = strtoupper($string[0]);
	}
	$func = create_function('$c', 'return strtoupper($c[1]);');
	return preg_replace_callback('/_([a-z])/i', $func, $string);
}

/**
 * Converts camelCase string to its under_score representation.
 * The method works only on standard latin characters.
 *
 * @param string $camelCased
 * @return string
 */
function camelCaseToUnderScore($camelCased)
{
	$underscored = preg_replace('/([A-Z])/', '_$0', $camelCased);
	$underscored = mb_convert_case($underscored, MB_CASE_LOWER);

	return $underscored;
}

/**
 * Converts camelCase string to its human readable representation.
 * The method works only on standard latin characters.
 *
 * @param string $camelCased
 * @return string
 */
function camelCaseToSpaces($camelCased)
{
	$spaced = preg_replace('/([A-Z])/', ' $0', $camelCased);
	$spaced = mb_convert_case($spaced, MB_CASE_LOWER);

	return $spaced;
}

/**
 * Converts human readable string to its camelCase representation.
 * The method works only on standard latin characters.
 *
 * @param string $camelCased
 * @return string
 */
function spacesToCamelCase($spaced, $first_char_caps = false)
{
	$spaced = preg_replace('/\s\s+/', ' ', $spaced);
	if( $first_char_caps == true ) {
		$spaced[0] = strtoupper($spaced[0]);
	}

	$func = create_function('$c', 'return strtoupper($c[1]);');

	return preg_replace_callback('/ ([a-z])/i', $func, $spaced);
}

function getRealIpAddress()
{
	if (Request::getInstance()->isInConsole) {
		return;
	}
	if (! empty($_SERVER['HTTP_CLIENT_IP']) && ($_SERVER['HTTP_CLIENT_IP'] != 'unknown')) {
		$ip = $_SERVER['HTTP_CLIENT_IP']; // share internet
	} elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR']) && ($_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown')) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; // pass from proxy
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}

/**
 * Get DB date
 *
 *
 * @return number
 */
function getCurrentDate()
{
	return AdapterFactory::create()->currentDate;
}

/**
 * Get DB timestamp
 *
 *
 * @return number
 */
function getCurrentTimestamp()
{
	return AdapterFactory::create()->currentTimestamp;
}

/**
 * Checks if array is associative
 *
 * @param array $array
 * @return boolean
 */
function isAssoc(array $array)
{
	return (bool) count(array_filter(array_keys($array), 'is_string'));
}

/**
 * Flatten multidimentional array
 *
 * @param array $array
 * @return array
 */
function arrayFlatten(array $array)
{
	$return = array();
	$callback = function($a) use (&$return) {
		$return[] = $a;
	};
	array_walk_recursive($array, $callback);
	return $return;
}

/**
 * Convern array keys to camelCase
 * @param array $array
 * @return array
 */
function arrayKeysToCamelCase(array $array)
{
	if (empty($array)) {
		return $array;
	}

	foreach ($array as $key => $value) {
		$return[underscoreToCamelCase($key)] = $value;
	}

	return $return;
}

/**
 * Convern array keys to under_score
 * @param array $array
 * @return array
 */
function arrayKeysToUnderScore(array $array)
{
	if (empty($array)) {
		return $array;
	}

	foreach ($array as $key => $value) {
		$return[camelCaseToUnderScore($key)] = $value;
	}

	return $return;
}

/**
 * Return the given value casted to INTEGER
 * NOTE - php function intval returns 0 if null is passed
 * Used mostly to remain the possibility to unsetNullValues()
 * @param mixed $value
 * @return Ambigous <NULL, integer>
 */
function toInt($value)
{
	return is_null($value) ? null : intval($value);
}

/**
 * Return the given value casted to FLOAT
 * NOTE - php function floatval returns 0 if null is passed
 * Used mostly to remain the possibility to unsetNullValues()
 * @param mixed $value
 * @return Ambigous <NULL, number>
 */
function toFloat($value)
{
	return is_null($value) ? null : floatval($value);
}

/**
 * Set values deep in the array using DOT notation
 *
 *
 * @param string $path
 * @param mixed $value
 * @param array $arr
 */
function setArrayDotNotation($path, $value, array &$arr)
{
	$addToArray = false;
	if (endsWith($path, '[]')) {
		$addToArray = true;
		$path = preg_replace('/\[\]/', '', $path);
	}
	$keys = explode('.', $path);
	while(count($keys) > 1) {
		$key = array_shift($keys);
		if(! isset($arr[$key])) {
			$arr[$key] = array();
		}
		$arr = &$arr[$key];
	}

	$key = reset($keys);
	if ($addToArray) {
		$arr[$key][] = $value;
	} else {
		$arr[$key] = $value;
	}
}

/**
 * Get values from array using DOT notation
 *
 *
 * @param string $path
 * @param array $arr
 * @param mixed $default
 * @return array|unknown
 */
function getArrayDotNotation($path, array $arr, $default = null)
{
	$keys = explode('.', $path);
	foreach ($keys as $key) {
		if (isset($arr[$key])) {
			$arr = $arr[$key];
		} else {
			return $default;
		}
	}

	return $arr;
}

/**
 * Format the memory (in bytes)
 * If no memory is supplied current memory allocated by the PHP process will be used
 *
 *
 * @param int $size
 * @return string
 */
function readMemory($size = null)
{
	if (is_null($size)) {
		$size = memory_get_usage();
	}
	$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
	return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

/**
 * Return classname without namespace
 *
 *
 * @param unknown $class
 */
function parseClassname($class)
{
	if (is_object($class)) {
		$class = get_class($class);
	}
	return join('', array_slice(explode('\\', $class), -1));
}

/**
 *
 * @param number $from
 * @param number $percent
 * @return number
 */
function calcPercent($from, $percent, $round = true)
{
	$return = $from * ($percent / 100);
	if ($round) {
		$return = round($return);
	}

	return toInt($return);
}

/**
 * Cast numeric string to int or float
 *
 *
 * @param unknown $value
 */
function castNumeric($value)
{
	if (is_numeric($value)) {
		return $value + 0;
	}

	return $value;
}

/**
 * Check if number is odd
 *
 * @param unknown $number
 */
function isOdd($number)
{
	return ((int)$number % 2) != 0;
}

/**
 * Check if a number is even
 *
 * @param unknown $number
 */
function isEven($number)
{
	return ((int)$number % 2) == 0;
}

/**
 *	clear input variable (anti hack)
 *
 * @param string $value
 * @return string
 */
function clearInput($data)
{
	$data = trim(addslashes(strip_tags($data)));

	return $data;
}

/**
 * Format a number and prefix it with + or - if positive or negative
 *
 *
 * @param unknown $num
 * @return string
 */
function formatNumber($num, $round = true, $precision = 0) {
	if ($round && $precision > 0) {
		$precision = min(strlen(substr(strrchr($num, "."), 1)), $precision);
		$key = "%+." . $precision . 'f';
		return sprintf($key, $num);
	} else {
		return sprintf("%+d", $num);
	}
}

/**
 * Get weighted rand between $min and $max and weight it by gamma
 * 1 - unweighted, < 1 - higher numbers, > 1 - lower numbers
 *
 * @param unknown $min
 * @param unknown $max
 * @param number $gamma
 * @return number
 */
function weightedRand($min, $max, $gamma = 1) {
	$offset = $max - $min;
	return floor($min + pow(lcg_value(), $gamma) * $offset);
}


/**
 * Recursive directory search
 *
 *
 * @param string $folder
 * @param RegEx $pattern
 * @return multitype:
 */
function rsearch($folder, $pattern = '/.*\.php/') {
	$dir = new RecursiveDirectoryIterator($folder);
	$ite = new RecursiveIteratorIterator($dir);
	$files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
	$fileList = array();

	foreach($files as $file) {
		$fileList = array_merge($fileList, $file);
	}

	return $fileList;
}
