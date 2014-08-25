<?php

namespace Kreate\Routing;

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

    public static function getRoutes() {
        return self::$routes;
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
            'route' => '/:any'
        ),
        'edit' => array(
            'method' => 'get',
            'route' => '/:any/edit'
        ),
        'update' => array(
            'method' => 'put',
            'route' => '/:any'
        ),
        'destroy' => array(
            'method' => 'delete',
            'route' => '/:any'
        ));

    public static function get($route, $callback)
    {
        self::$routes['get'][self::parseRoute($route)] = $callback;
    }

    public static function post($route, $callback)
    {
        self::$routes['post'][self::parseRoute($route)] = $callback;
    }

    public static function put($route, $callback)
    {
        self::$routes['put'][self::parseRoute($route)] = $callback;
    }

    public static function delete($route, $callback)
    {
        self::$routes['delete'][self::parseRoute($route)] = $callback;
    }

    public static function resource($route, $controller)
    {
        foreach (self::$restDefaults as $method => $options) {
            $callMethod = $options['method'];
            self::{$callMethod}($route . $options['route'], $controller . '@' . $method);
        }
    }

    public static function parseRoute($route)
    {
        if ($route[0] != '/') {
            $route = '/' . $route;
        }
        if (strlen($route) > 1 && endsWith($route, '/')) {
            $route = rtrim($route, '/');
        }
        return $route;
    }

    public static function flush()
    {
        self::$routes = array();
    }
}
