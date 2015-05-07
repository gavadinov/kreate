<?php

namespace Framework\Http;

use Framework\Config\AppConfig;

class Request
{

	protected
			$host,
			$uri,
			$url,
			$baseUrl,
			$trimmedUri,
			$fullUri,
			$type,
			$getParamsString,
			$params = array();

	/*
	 * Singleton instance of the request
	 */
	private static $instance;

	private function __construct()
	{
		if ($this->isInConsole()) {
			return;
		}

		$this->host = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

		$this->url = $this->host . $_SERVER['REQUEST_URI'];

		$env = AppConfig::resolveEnv();
		$path = AppConfig::get($env . 'Path');
		if ($path == '/' || $path == '\/') {
			$this->baseUrl = $this->host . '/';
		} else {
			$this->baseUrl = preg_replace_callback('/(?:.*' . $path . '\/)(.*)/', function ($matches)
			{
				$replace = preg_quote($matches[1]);
				return preg_replace('#' . $replace . '\b#', '', $matches[0]);
			}, $_SERVER['REQUEST_URI']);

			$this->baseUrl = $this->host . $this->baseUrl;
		}

		$this->setUri();

		$this->getParamsString = substr($this->url, strpos($this->url, '?'));

		$this->resolveType();
	}

	private function setUri()
	{
		$env = AppConfig::resolveEnv();
		$remove = AppConfig::get($env . 'Path');
		if ($remove == '/' || $remove == '\/') {
			$remove = '//';
		} else {
			$remove = '/.*' . $remove . '/';
		}

		$serverUri = explode('?', $_SERVER['REQUEST_URI'])[0];
		$uri = preg_replace($remove, '', $serverUri);
		if ($uri !== '/') {
			$uri = rtrim($uri, '/');
			$serverUri = rtrim($serverUri, '/');
		}
		$this->uri = $uri;
		preg_match($remove, $serverUri, $matches);
		$trimmedUri = '';
		if (! empty($matches)) {
			$trimmedUri = $matches[0];
		}
		$this->trimmedUri = $trimmedUri;
		$this->fullUri = $serverUri;
	}

	/**
	 * Resolve request type - get, post, put, delete
	*/
	private function resolveType()
	{
		$type = (isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']);
		$this->type = strtolower($type);
	}

	public function __get($name)
	{
		return (isset($this->$name) ? $this->$name : null);
	}

	/**
	 * Sets a request system parameter
	 * Used for passing data between parts of the framwork
	 * Can be set using DOT notation (->setParam('arr.anotherArr.key', 1))
	 * If we need the value to be added to the deepest array the syntax is (->setParam('arr.anotherArr.key[]', 1))
	* @param string $name
	 * @param string $value
	 */
	public function setParam($name, $value)
	{
		if (contains($name, '.')) {
			$arr = $this->params;
			setArrayDotNotation($name, $value, $arr);
			$this->params = $arr;
		} else {
			if (endsWith($name, '[]')) {
				$name = preg_replace('/\[\]/', '', $name);
				$this->params[$name][] = $value;
			} else {
				$this->params[$name] = $value;
			}
		}
	}

	/**
	 * Sets a request system parameter
	 * Used for passing data between parts of the framwork
	 * Can be accessed using DOT notation (->getParam('arr.anotherArr.key'))
	* @param string $name Parameter name
	 * @param mixed $default Optional. A default value which will be returned if the parameter is not presented
	 * @return mixed
	 */
	public function getParam($name, $default = null)
	{
		if (contains($name, '.')) {
			return getArrayDotNotation($name, $this->params, $default);
		}
		if (isset($this->params[$name])) {
			return $this->params[$name];
		} else {
			if (is_callable($default)) {
				return $default->__invoke();
			}
			return $default;
		}
	}

	/**
	 * Unset param from the request
	* @param unknown $name
	 */
	public function clearParam($name)
	{
		unset($this->params[$name]);
	}

	/**
	 * Singleton implementation
	* @return Request
	 */
	public static function getInstance()
	{
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Check if the request is asynchronous
	* @return boolean
	 */
	public function isAjax()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	/**
	 * Determine if we are running in the console.
	* @return bool
	 */
	public static function isInConsole()
	{
		return php_sapi_name() == 'cli';
	}
}
