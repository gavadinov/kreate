<?php
namespace Framework\Factory;

class ServiceFactory
{
	private static $instances = array();

	/**
	* @param string $name
	 * @return \Framework\Service\AbstractService
	 */
	public static function create($name, $flush = false)
	{
		$name = preg_replace('/\./', '\\', $name);
		if (contains($name, '\\')) {
			$name = preg_replace('{\\\}', ' ', $name);
			$name = ucwords($name);
			$name = preg_replace('/ /', '\\', $name);
		}
		$fqn = 'Service\\' . ucfirst($name) . 'Service';

		if (isset(self::$instances[$fqn]) && ! $flush) {
			return self::$instances[$fqn];
		}

		$service = new $fqn();

		if ($service->getIsSingleton()) {
			self::$instances[$fqn] = $service;
		}

		return $service;
	}
}
