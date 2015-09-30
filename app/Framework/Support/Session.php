<?php

namespace Framework\Support;

use Framework\Config\AppConfig;
use Framework\Http\Request;
use Lib\Config;
/**
 * Simple session abstraction
 *
 *
 */
class Session
{
	private static $instance;

	private $sessionStarted = false;

	public static function getInstance()
	{
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		if (Request::getInstance()->isInConsole) {
			return;
		}
		$this->setStorage();
		if ($name = AppConfig::get('sessionName', false)) {
			session_name($name);
		}

		if (Request::getInstance()->isSecure && AppConfig::get('secureSession', false)) {
			ini_set('session.cookie_secure', 1);
		}

		session_start();
	}

	/**
	 *
	 * @return string
	 */
	private function prepareMemcacheServers()
	{
		$return = '';
		$servers = AppConfig::get(AppConfig::resolveEnv() . 'MemcacheServers');
		foreach ($servers as $server) {
			$return .= $server[0] . ':' . $server[1];
			$weight = (isset($server[2]) ? $server[2] : 0);
			$return .= '?persistent=1&amp;weight=' . $weight . '&amp;timeout=1&amp;retry_interval=15,';
		}
		$return = rtrim($return, ',');
		return $return;
	}

	/**
	 *
	 */
	private function setStorage()
	{
		$sessionLife = AppConfig::get('sessionLife', 84600);
		ini_set('session.cookie_lifetime', $sessionLife);
		ini_set('session.gc_maxlifetime', $sessionLife);
		ini_set('session.cookie_httponly', true);

		if (AppConfig::get('sessionStorage', 'file') == 'memcached') {
			$ips = $this->prepareMemcacheServers();
			ini_set('session.save_handler', 'memcached');
			ini_set('session.save_path', $ips);
		}
	}

	/**
	 * Gets a value from the session
	 *
	 * @param string $name
	 * @param mixed $default Optional. A value/function to return if this session var is not presented
	 * @return unknown
	 */
	public function get($name, $default = null)
	{

		if (isset($_SESSION[$name])) {
			return $_SESSION[$name];
		} else {
			if (is_callable($default)) {
				return $default->__invoke();
			}
			return $default;
		}
	}

	/**
	 * Sets a session variable
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * Clears a session variable
	 *
	 * @param unknown_type $name
	 */
	public function clear($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * Gets the id of the session
	 *
	 * @return string
	 */
	public function getId()
	{
		return session_id();
	}

	/**
	 * Check if a variable is present in the session
	 *
	 *
	 * @param string $name
	 */
	public function has($name)
	{
		$result = $this->get($name);
		return isset($result);
	}

	/** Clear Session variables
	 *
	 */
	public function clearSession($keep = false)
	{
		if ($keep) {
			$keepedVars = AppConfig::get('keepSessionVariables');
			foreach ($_SESSION as $key => $value) {
				if (! in_array($key, $keepedVars)) {
					$this->clear($key);
				}
			}
		} else {
			$_SESSION = array();
			session_destroy();
			session_start();
		}
	}
}
