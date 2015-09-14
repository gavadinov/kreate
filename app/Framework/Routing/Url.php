<?php
namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Routing\Exception\UrlGeneratorException;
use Framework\Routing\Exception\RouteNotFoundException;
use Framework\Support\Input;

class Url
{
	/**
	 * Generates URL based on a router rule
	 *
	 *
	 * @param string $name
	 * @param array $params Parameters to be replaced in the URL
	 * @throws UrlGeneratorException
	 * @return string
	 */
	public static function generate($name, array $params = array())
	{
		$rule = Routes::getRouteByName($name);
		if (! $rule) {
			throw new \InvalidArgumentException('Unknown named route: ' . $name);
		}
		$uri = ltrim($rule->route, '/');

		foreach ($params as $name => $value) {
			$pattern = '/:' . $name . '/';
			$uri = preg_replace($pattern, $value, $uri);
		}

		if (preg_match_all('/:[a-zA-Z0-9]+/', $uri, $matches)) {
			throw new UrlGeneratorException('Missing params for route ' . $name . ': ' . implode(', ', $matches[0]));
		}

		$request = Request::getInstance();
		$url = $request->baseUrl . $uri;

		return $url;
	}

	/**
	 * Generate GET string from the current GET params
	 *
	 *
	 * @return string
	 */
	public static function generateGetString(array $addParams = array())
	{
		$get = '?';
		$params = array_merge(Input::get(), $addParams);
		foreach ($params as $key => $value) {
			$get .= "{$key}={$value}&";
		}

		$get = rtrim($get, '&');

		if ($get == '?') {
			return '';
		}

		return $get;
	}

	/**
	 * Find the relative route by name
	 *
	 *
	 * @param string $name
	 * @throws \InvalidArgumentException
	 */
	public static function find($name)
	{
		$rule = Routes::getRouteByName($name);
		if (! $rule) {
			throw new \InvalidArgumentException('Unknown named route: ' . $name);
		}
		return ltrim($rule->route, '/');
	}

	public static function findRouteCallback($route, $type = 'post')
	{
		try {
			$route = '/' . ltrim($route, '/');
			list($controllerName, $method) = (new Router(Request::getInstance()))->resolve($route, $type);
		} catch (RouteNotFoundException $e) {
			return false;
		}

		return $controllerName . '@' . $method;
	}
}
