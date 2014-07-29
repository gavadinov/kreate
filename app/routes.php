<?php

use Kreate\Routing\Routes;

/**
 * Application routes.
 *
 * Here you can register the routes for your application.
 * Simply register an URI you want Kreate to respond to.
 */

Routes::get('/', function() {
    return 'wohooo I\'m in the home route';
});

//Routes::get('photos/:any/:num', 'PhotoController@test');
Routes::resource('/photos', 'PhotoController');
Routes::resource('/photos/albums', 'AlbumsController');