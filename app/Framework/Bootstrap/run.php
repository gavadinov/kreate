<?php

use Framework\Foundation\Application;
use Framework\Http\Request;
use Framework\Config\AppConfig;
use Framework\Support\Profiler;
use Framework\Event\EventDispatcher;
use Framework\Foundation\Exception\AppException;

if (AppConfig::resolveEnv() == AppConfig::ENV_LIVE && ! AppConfig::get('showErrors', false)) {
	error_reporting(0);
	ini_set("display_errors", false);
} else {
	error_reporting(E_ALL);
	ini_set("display_errors", true);
}

Profiler::start();

$request = Request::getInstance();
$app = Application::getInstance($request);
EventDispatcher::registerAllSubscribers();

require_once app_dir . 'start.php';
require_once app_dir . 'routes.php';
require_once app_dir . 'inversionOfControl.php';
require_once app_dir . 'exceptionHandlers.php';
require_once app_dir . 'functions.php';

//Deal with catchable PHP errors
if (AppConfig::resolveEnv() != AppConfig::ENV_LIVE && ! Request::getInstance()->isInConsole) {
	set_error_handler(function ($errno , $errstr, $errfile = null, $errline = null, $errcontext = array() ) {

		$ex = new AppException("{$errstr} at {$errfile} {$errline}");
		Application::getInstance()->renderKernelPanicAlert($ex);
	}, AppConfig::get('devErrorHandlerLevel'));
}

try {
	list($controllerName, $method, $params) = $app->setup();
	$app->run($controllerName, $method, $params);
} catch (Exception $e) {
	$app->handleException($e);
}

$app->shutdown();
