<?php

namespace Framework\Routing;

class Routes
{
	/*
	 * Collection of the registered routes
	 */
	protected static $routes = array(
		'get' => array(),
		'post' => array(),
		'put' => array(),
		'delete' => array(),
	);

	protected static $routesCalback = array();

	public static function getRoutes() {
		return self::$routes;
	}

	public static function getRoutesByCallback()
	{
		return self::$routesCalback;
	}

	public static function getRouteByCallback($callback)
	{
		return isset(self::$routesCalback[$callback][0]) ? self::$routesCalback[$callback][0] : false;
	}

	/*
	 * The default actions for a rest controller.
	 */
	protected static $restDefaults = array(
		'index' => array(
			'method' => 'get',
			'route' => ''
		),
		'create' => array(
			'method' => 'get',
			'route' => '/create'
		),
		'store' => array(
			'method' => 'post',
			'route' => ''
		),
		'show' => array(
			'method' => 'get',
			'route' => '/:id'
		),
		'edit' => array(
			'method' => 'get',
			'route' => '/:id/edit'
		),
		'update' => array(
			'method' => 'put',
			'route' => '/:id'
		),
		'destroy' => array(
			'method' => 'delete',
			'route' => '/:id'
		));

	protected static $registerMethods = array(
		'get',
		'post',
		'put',
		'delete',
		'any'
	);

	private static function parseRoute($route)
	{
		if ($route[0] != '/') {
			$route = '/' . $route;
		}
		if (strlen($route) > 1 && endsWith($route, '/')) {
			$route = rtrim($route, '/');
		}
		return $route;
	}

	public static function getRouteByName($name)
	{
		foreach (self::$routes as $type => $routes) {
			foreach ($routes as $route) {
				if ($route->name == $name) {
					return $route;
				}
			}
		}
		return false;
	}

	public static function __callStatic($method, $args)
	{
		if (in_array($method, self::$registerMethods)) {
			$route = $args[0];
			$name = $args[1];
			$callback = (! empty($args[2]) ? $args[2] : '');

			$rule = new \stdClass();
			$rule->route = self::parseRoute($route);
			$rule->callback = $callback;
			$rule->name = $name;

			if ($method == 'any') {
				self::$routes['get'][$rule->route] = $rule;
				self::$routes['post'][$rule->route] = $rule;
				self::$routes['put'][$rule->route] = $rule;
				self::$routes['delete'][$rule->route] = $rule;
			} else {
				self::$routes[$method][$rule->route] = $rule;
			}

			$rule->method = $method;
			self::$routesCalback[$rule->callback][] = $rule;
		}
	}

	public static function resource($route, $name, $controller)
	{
		foreach (self::$restDefaults as $method => $options) {
			$callMethod = $options['method'];
			$currRoute = $route . $options['route'];
			$currName = $name . '.' . $method;
			self::$callMethod($currRoute, $currName, $controller . '@' . $method);
		}
	}

	public static function flush()
	{
		self::$routes = array();
	}
}
