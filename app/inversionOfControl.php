<?php

use Framework\Support\IoC;

/*
IoC::bind('BlqBlq', function($params) {
	if (empty($params)) {
		throw new \InvalidArgumentException('Empty params for BlqBlq initialization');
	}
	$a = new A($params[0]);
	$b = new B($params[1]);
	$c = IoC::make('C');

	$class = '\test\BlqBlq';
	$instance = new $class($a, $b, $c);
	$instance->d = $params[2];

	return $instance;
});

$blqBlq = IoC::make('BlqBlq', array(1, 2, 3));
*/

