<?php

namespace Kreate\Routing;

use Kreate\Http\Request;

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

    private function execClosure($closure, $params)
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
            throw new Exception\InvalidCallbackException($message);
        }
        $controller = new $controllerName();

        if (! method_exists($controller, $method)) {
            $message = "The controller class {$controllerName} does not have a method {$method}.";
            throw new Exception\InvalidCallbackException($message);
        }

        return $controller->$method($params);
    }

    private function matchRoute()
    {
        $type = $this->request->type;
        $currRoute = $this->request->uri;
        $routes = $this->routes[$type];
        foreach ($routes as $route => $callback) {
            $route = $this->prepareRouteForRegExp($route);
            list($match, $matches) = $this->match($currRoute, $route);
            if ($match) {
                $params = $this->prepareParams($matches);
                return array($route, $callback, $params);
            }
        }
        $message = "The route {$currRoute} is not registered in routes.php";
        throw new Exception\RouteNotFoundException($message);
    }

    private function prepareParams(array $matches)
    {
        if (count($matches) > 1) {
            array_shift($matches);
            if (count($matches) > 1) {
                return $matches;
            } else {
                return $matches[0];
            }
        }
        return;
    }

    private function match($route, $pattern)
    {
        $match = preg_match($pattern, $route, $matches);
        return array($match, $matches);
    }

    private function prepareRouteForRegExp($route)
    {
        $route = '#' . $route . '$#';
        $route = str_replace(':any', '([0-9a-zA-Z]+)', $route);
        $route = str_replace(':num', '([0-9]+)', $route);
        $route = str_replace(':char', '([a-zA-Z]+)', $route);
        return $route;
    }

    private function resolveCallbackType($callback)
    {
        if ($callback instanceof \Closure) {
            $type = self::CALLBACK_CLOSURE;
        } else if (contains($callback, '@')) {
            $type = self::CALLBACK_CONTROLLER;
        } else {
            $message = "The route callback must be a Closure, Resource, or a Controller method: Controller@Method";
            throw new Exception\InvalidCallbackException($message);
        }
        return $type;
    }

}
