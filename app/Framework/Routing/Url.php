<?php
namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Routing\Exception\UrlGeneratorException;

class Url
{
	/**
	 * Generates URL based on a router rule
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
		$url = $request->host . $request->trimmedUri . '/' . $uri;

		return $url;
	}

	/**
	 * Find the relative route by name
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
}
