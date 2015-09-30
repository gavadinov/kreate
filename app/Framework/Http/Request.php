<?php

namespace Framework\Http;

class Request
{

	protected
			$host,
			$httpHost,
			$uri,
			$url,
			$baseUrl,
			$fullUri,
			$type,
			$isSecure,
			$getParamsString,
			$isInConsole = false,
			$params = array();

	/*
	 * Singleton instance of the request
	 */
	private static $instance;

	private function __construct()
	{
		$this->isInConsole = (php_sapi_name() == 'cli');

		if ($this->isInConsole) {
			return;
		}

		$this->isSecure = isset($_SERVER['HTTPS']);

		$this->httpHost = $_SERVER['HTTP_HOST'];

		$this->host = 'http' . ($this->isSecure ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

		$this->setUrl();

		$this->setUri();

		$this->resolveType();
	}

	private function setUrl()
	{
		$this->url = $this->host . $_SERVER['REQUEST_URI'];

		$script = $_SERVER['PHP_SELF'];
		$index = strrpos($script, '/');
		$baseUri = substr($script, 0, $index);
		$this->baseUrl = $this->host . $baseUri . '/';
	}

	private function setUri()
	{
		$serverUri = explode('?', $_SERVER['REQUEST_URI'])[0];
		$urlWithoutGet = explode('?', $this->url)[0];
		$uri = str_replace($this->baseUrl, '', $urlWithoutGet);
		if ($uri !== '/') {
			$uri = trim($uri, '/');
			$uri = '/' . $uri;
			$serverUri = rtrim($serverUri, '/');
		}
		$this->uri = $uri;

		$this->fullUri = $serverUri;

		$this->getParamsString = substr($this->url, strpos($this->url, '?'));
	}

	/**
	 * Resolve request type - get, post, put, delete
	 *
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
	 * For testing purposes
	 *
	 *
	 * @param bool $isInConsole
	 */
	public function setIsInConsole($isInConsole)
	{
		$this->isInConsole = $isInConsole;
	}

	/**
	 * Sets a request system parameter
	 * Used for passing data between parts of the framwork
	 * Can be set using DOT notation (->setParam('arr.anotherArr.key', 1))
	 * If we need the value to be added to the deepest array the syntax is (->setParam('arr.anotherArr.key[]', 1))
	 *
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
	 *
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
	 *
	 * @param unknown $name
	 */
	public function clearParam($name)
	{
		unset($this->params[$name]);
	}

	/**
	 * Singleton implementation
	 *
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
	 *
	 * @return boolean
	 */
	public function isAjax()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
}
