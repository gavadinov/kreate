<?php

namespace Framework\Support;

use Framework\Support\Exception\OptionResolverException;
/**
 * Generic class for option resolving
 *
 */
class OptionResolver
{
	private $options,
			$defaults,
			$required;

	/**
	 * Constructs an option resolver
	 *
	 * @param array $options key => value pair of options to be stored.
	 * @param array $defaults key => value pair of default values for the missing options.
	 * @param array $required Array with key names for all required options.
	 */
	public function __construct($options, $defaults = array(), $required = array())
	{
		$this->options = $options;
		$this->defaults = $defaults;
		$this->required = $required;
		$this->cache = array();
	}

	/**
	 * Checks for required options existence
	 * It seems complicated, but it actually is not.
	 *
	 * @throws Exception If requirements are not fitted
	 */
	private function checkRequirements($requirementsList, $options, $keyFullName = '')
	{
		foreach ($requirementsList as $key => $subList) {

			if ($keyFullName == '') {
				$currentKeyFullName = $key;
			} else {
				$currentKeyFullName = $keyFullName . '.' . $key;
			}

			if (! isset($options[$key])) {
				throw new OptionResolverException("OptionResolver: Missing required option '{$currentKeyFullName}'.");
			} else {
				if (is_array($subList)) {
					$this->checkRequirements($subList, $options[$key], $currentKeyFullName);
				} else {
					if ($subList !== true) {
						if (is_object($options[$key])) {
							$className = get_class($options[$key]);

							if ($options[$key] instanceof $subList) {
								// This option is ok
								continue;
							} elseif ($className != $subList) {
								// Exception : Invalid option
							} else {
								$type = gettype($options[$key]);
								if ($type != $subList) {
									// Exception : Invalid option
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Searches for a value recurevely
	 *
	 * @param array $path Array of key names
	 * @param array $source Source array to search for
	 * @throws OptionResolverException
	 * @return mixed
	 */
	private function getDeepValue($path, $source)
	{
		$currentKey = array_shift($path);
		$result = null;
		if (is_array($source) && isset($source[$currentKey])) {
			$rest = $source[$currentKey];
			if (count($path) > 0) {
				$result = $this->getDeepValue($path, $rest);
			} else {
				$result = $rest;
			}
		} else {
			throw new OptionResolverException();
		}
		return $result;
	}

	/**
	 * Populates the object with new options
	 * IMPORTNAT: The metod will invoke checkRequirements()
	 *
	 * @param array $options key => value pair of options to be stored
	 */
	public function setOptions($options)
	{
		$this->options = $options;
		$this->cache = array();

		$this->checkRequirements($this->required, $this->options);
	}

	/**
	 * Gets an option by key name
	 * The method will checks it cached values first.
	 * After that if the options is not found the method will search first in $this->defaults private field and if
	 * nothing is found
	 * there, it will return the $default value passed to the method
	 * If a value is found it will be cached for later extraction
	 *
	 * @param string $key The dotted option key name
	 * @param mixed $default A default value to be returned if no match in $this->$options and $this->$defaults found.
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$path = explode('.', $key);
		try {
			$value = $this->getDeepValue($path, $this->options);
			$this->cache[$key] = $value;
		} catch (OptionResolverException $e) {
			try {
				$value = $this->getDeepValue($path, $this->defaults);
				$this->cache[$key] = $value;
			} catch (OptionResolverException $e) {
				$value = $default;
			}
		}

		return $value;
	}
}
