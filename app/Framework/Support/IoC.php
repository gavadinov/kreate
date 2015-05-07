<?php
namespace Framework\Support;

class IoC
{
	protected static $registry = array();

	/**
	 * Add a new resolver to the registry array.
	 *
	 *

	 * @param string $name
	 * @param \Closure $resolve
	 */
	public static function bind($name, \Closure $resolve)
	{
		static::$registry[$name] = $resolve;
	}

	/**
	 * Create the instance
	 *
	 *

	 * @param sting $name
	 * @throws \Exception
	 */
	public static function make($name, $params = null)
	{
		if (static::has($name)) {
			$name = static::$registry[$name];
			return $name($params);
		}

		throw new \InvalidArgumentException('Nothing registered with that name, fool.');
	}

	/**
	 * Determine whether the name is registered
	 *
	 *

	 * @param string $name
	 * @return boolean
	 */
	public static function has($name)
	{
		return array_key_exists($name, static::$registry);
	}
}
