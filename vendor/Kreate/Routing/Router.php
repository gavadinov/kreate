<?php

namespace Kreate\Routing;

use Kreate\Http\Request;
use Kreate\Routing\Exception\InvalidCallbackException;

class Router
{
    const CALLBACK_CLOSURE = 1;
    const CALLBACK_CONTROLLER = 2;

    private $routes = array(),
            $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->routes = Routes::getRoutes();
        Routes::flush();
    }

    public function dispatch()
    {
        list($route, $callback, $params) = $this->matchRoute();
        $type = $this->request->type;
        $callbackType = $this->resolveCallbackType($callback);
        switch ($callbackType) {
            case self::CALLBACK_CLOSURE:
                $result = $this->execClosure($callback, $params);
                break;
            case self::CALLBACK_CONTROLLER:
                list($controller, $method) = $this->parseCallbackForController($callback);
                $result = $this->execController($controller, $method, $params);
                break;
            default:
                $result = '';
                break;
        }
        return $result;
    }

    private function execClosure(\Closure $closure, $params)
    {
        return $closure->__invoke($params);
    }

    private function parseCallbackForController($callback)
    {
        list($controller, $method) = explode('@', $callback);
        return array($controller, $method);
    }

    private function execController($controllerName, $method, $params)
    {
        if ($controllerName[0] !== '\\') {
            $controllerName = '\\' . $controllerName;
        }
        if (! class_exists($controllerName)) {
            $message = "The controller class {$controllerName} does not exist.";
            throw new InvalidCallbackException($message);
        }
        $controller = new $controllerName();

        if (! method_exists($controller, $method)) {
            $message = "The controller class {$controllerName} does not have a method {$method}.";
            throw new InvalidCallbackException($message);
        }

        return $controller->$method($params);
    }

    private function matchRoute()
    {
        $type = $this->request->type;
        $currRoute = $this->request->uri;
        $routes = $this->routes[$type];
        foreach ($routes as $route => $callback) {
            $params = $this->prepareRouteForRegExp($route);
            list($match, $matches) = $this->match($currRoute, $route);
            if ($match) {
                $params = $this->prepareParams($params, $matches);
                return array($route, $callback, $params);
            }
        }
        $message = "The route {$currRoute} is not registered in routes.php";
        throw new Exception\RouteNotFoundException($message);
    }

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

    private function match($route, $pattern)
    {
        $match = preg_match($pattern, $route, $matches);
        return array($match, $matches);
    }

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

    private function resolveCallbackType($callback)
    {
        if ($callback instanceof \Closure) {
            $type = self::CALLBACK_CLOSURE;
        } else if (contains($callback, '@')) {
            $type = self::CALLBACK_CONTROLLER;
        } else {
            $message = "The route callback must be a Closure, Resource, or a Controller Method: Controller@Method";
            throw new InvalidCallbackException($message);
        }
        return $type;
    }

}
