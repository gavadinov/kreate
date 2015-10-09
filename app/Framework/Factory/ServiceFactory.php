<?php
namespace Framework\Factory;

class ServiceFactory
{
	private static $instances = array();

	/**
	 *
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

		$name = ucfirst($name);
		$serviceName = $name . 'Service';
		$fqn = 'Service\\' . $serviceName;
		if (! class_exists($fqn, true)) {
			$fqn = 'Service\\' . $name .'\\' . $serviceName;
		}

		if (isset(self::$instances[$fqn]) && ! $flush) {
			return self::$instances[$fqn];
		}

		$service = new $fqn();

		if ($service->isSingleton()) {
			self::$instances[$fqn] = $service;
		}

		return $service;
	}
}
