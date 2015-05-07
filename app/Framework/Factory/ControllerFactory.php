<?php
namespace Framework\Factory;

use Framework\Routing\Exception\InvalidCallbackException;
use Framework\Foundation\Exception\AppException;
use Framework\Controller\AbstractController;

class ControllerFactory
{

    /**
     *
     
     * @param string $controllerName
     * @throws InvalidCallbackException
     * @return \Framework\Controller\AbstractController
     */
    public static function create($controllerName)
    {
        $controllerName = 'Controller\\' . ucfirst($controllerName) . 'Controller';

        if (! class_exists($controllerName)) {
            $message = "The controller class {$controllerName} does not exist.";
            throw new InvalidCallbackException($message);
        }
        $controller = new $controllerName();

        if (! $controller instanceof AbstractController) {
            throw new AppException('Controller must be an instance of AbstractController');
        }

        return $controller;
    }
}
