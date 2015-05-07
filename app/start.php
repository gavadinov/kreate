<?php

use Framework\Foundation\Application;
use Framework\Config\AppConfig;
use Framework\Persistence\UnitOfWork;
use Framework\Persistence\Exception\QueriesNotExecutedException;
use Framework\Controller\AbstractAjaxController;
use Framework\Http\Request;
use Framework\Controller\AbstractController;
use Framework\Controller\Exception\ControllerForwardException;
use Lib\Firewall\Firewall;

define('APP_ENV', AppConfig::resolveEnv());

$app = Application::getInstance();

$app->registerBefore(function($request) {
	if (file_exists(base_dir . 'down') && ! Request::isInConsole()) {
		if (Request::getInstance()->isAjax()) {
			$controllerName = 'AjaxError';
		} else {
			$controllerName = 'Error';
		}
		throw new ControllerForwardException($controllerName . '@maintenance');
	}
});

$app->registerAfter(function($request, $response) {
	if (UnitOfWork::hasQueries()) {
		throw new QueriesNotExecutedException('There are unexecuted queries in the UnitOfWork queue!!!');
	}
});

AbstractController::registerBefore(function(AbstractController $controller, $method) {

});

AbstractController::registerBefore(function(AbstractController $controller, $method) {
	Firewall::getInstance()->check($controller, $method);
});

AbstractAjaxController::registerBefore(function(AbstractController $controller, $method) {

});

AbstractAjaxController::registerAfter(function(&$result, AbstractController $controller) {

});
