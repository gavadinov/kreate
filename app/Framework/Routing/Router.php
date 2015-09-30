<?php

namespace Framework\Routing;

use Framework\Http\Request;

class Router
{
	private $routes = array(),
			$request;

	/**
	 * If app is running in the console resolve the controller and method
	 *
	 *
	 * @return array
	 */
	private function resolveCLI()
	{
		$controllerName = $this->request->getParam('cliData.controller');
		$method = $this->request->getParam('cliData.method');
		$params = $this->request->getParam('cliData.params', array());
		return array($controllerName, $method, $params);
	}

	/**
	 * Parse callback of type Controller@Method
	 *
	 *
	 * @param unknown $callback
	 * @return array
	 */
	public static function parseCallbackForController($callback)
	{
		list($controllerName, $method) = explode('@', $callback);

		return array($controllerName, $method);
	}

	/**
	 * Find the current route in the pool of registered routes
	 *
	 *
	 * @throws Exception\RouteNotFoundException
	 * @return array
	 */
	private function matchRoute($currRoute, $type)
	{
		$routes = $this->routes[$type];
		if (isset($routes[$currRoute])) {
			return array($routes[$currRoute], null);
		}
		foreach ($routes as $route => $rule) {
			$callback = $rule->callback;
			$params = $this->prepareRouteForRegExp($route);
			list($match, $matches) = $this->match($currRoute, $route);
			if ($match) {
				$params = $this->prepareParams($params, $matches);
				if (! empty($params['controller']) && ! empty($params['method'])) {
					$callback = ucfirst($params['controller']) . '@' . $params['method'];
					unset($params['controller']);
					unset($params['method']);
				}
				return array($rule, $params);
			}
		}
		$message = "The route {$currRoute} is not registered in routes.php";
		throw new Exception\RouteNotFoundException($message);
	}

	/**
	 * Prepare route params (:param)
	 *
	 *
	 * @param array $params
	 * @param array $matches
	 * @return array
	 */
	private function prepareParams(array $params, array $matches)
	{
		$result = array();
		if (empty($params)) return;

		if (count($params) == 1) return array_pop($matches);

		foreach ($params as $key => $value) {
			$k = ltrim($value, ':');
			$result[$k] = $matches[$key];
		}

		return $result;
	}

	/**
	 * Match route with reg ex
	 *
	 *
	 * @param unknown $route
	 * @param unknown $pattern
	 * @return array
	 */
	private function match($route, $pattern)
	{
		$match = preg_match($pattern, $route, $matches);
		return array($match, $matches);
	}

	/**
	 *
	 * @param unknown $route
	 * @return array
	 */
	private function prepareRouteForRegExp(&$route)
	{
		$params = array();
		$route = preg_replace_callback('/(:[a-zA-Z0-9\_\-]*)/', function($matches) use (&$params) {
			$params[count($params)+1] = $matches[1];
			return '([^/]*)';
		}, $route);
		$route = '#' . $route . '$#';

		return $params;
	}

	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->routes = Routes::getRoutes();
	}

	/**
	 * Does the magic
	 *
	 *
	 * @return array
	 */
	public function resolve($currRoute = null, $type = null)
	{
		if (Request::getInstance()->isInConsole) {
			return $this->resolveCLI();
		}

		if (is_null($currRoute)) {
			$currRoute = $this->request->uri;
		}

		if (is_null($type)) {
			$type = $this->request->type;
		}

		list($route, $params) = $this->matchRoute($currRoute, $type);

		Request::getInstance()->setParam('currRoute', $route);
		list($controllerName, $method) = self::parseCallbackForController($route->callback);
		return array($controllerName, $method, $params);
	}
}
