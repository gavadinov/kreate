<?php

use Framework\Routing\Routes;

/**
 * Application routes.
 *
 * Here you can register the routes for the application.
 */

Routes::get('/', 'home', 'Home@index');
