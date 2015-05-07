<?php

use Framework\Foundation\Application;
use Framework\Exception\Assert;
use Framework\Exception\TerminalException;


$app = Application::getInstance();

/*
$app->registerExceptionHandler(function($e) {
	if ($e->getCode() == 1) {
		return array('message' => $e->getMessage());
	}
});
*/

/**
 * Assertions handler
 */
$app->registerExceptionHandler(function($e) {
	if ($e instanceof Assert) {
		throw new TerminalException('Caught assertion: ' . $e->getMessage());
	}
});

$app->registerExceptionHandler(function($e) {

});
