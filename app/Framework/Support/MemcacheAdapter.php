<?php
namespace Framework\Support;

use Framework\Config\AppConfig;

class MemcacheAdapter
{
	private static $instance;

	/**
	 * @var \Memcached
	 */
	protected $driver;

	public static function getInstance()
	{
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->driver = new \Memcached(99);
		if (empty($this->driver->getServerList())) {
			$this->driver->addServers(AppConfig::get(APP_ENV . 'MemcacheServers'));
		}
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$result = $this->driver->get($key);

		if($result) {
			return $result;
		} else {
			return $default;
		}
	}

	public function delete(array $keys, $time = null)
	{
		$this->driver->deleteMulti($keys, $time);
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expiration
	 */
	public function set($key, $value, $expiration = 0)
	{
		$throw = create_function('', 'throw new Framework\Support\Exception\MemcacheException("Unable to set value to memcache");');
		$this->driver->set($key, $value, $expiration) or $throw();
	}

	/**
	 * Clear cache
	 *
	 *
	 * @param number $delay
	 */
	public function flush($delay = 0)
	{
		$this->driver->flush($delay);
	}

	/**
	 * Get the result code of the last memcache operation
	 *
	 *
	 * @return number
	 */
	public function getResultCode()
	{
		return $this->driver->getResultCode();
	}
}
