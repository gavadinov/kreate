<?php

return array(

	/*
	 * Application environment(dev | test | live).
	 * In dev environment detailed errors will be shown.
	 * Leave blank for dynamic environment determined from the url
	 */
	'env' => '',

	/*
	 * Path from the root (/) to the index of our app
	 * RegEx is also acceptable
	 */
	'devPath' => '\/.*\/public',

	'testPath' => '/',

	'livePath' => '/',

	/*
	 * Here we can set the DEVELOPMENT urls for our application
	 * RegEx is also acceptable
	 */
	'devUrls' => array(),

	/*
	 * Here we can set the TEST urls for our application
	 * RegEx is also acceptable
	 */
	'testUrls' => array(),

	'devMemcacheServers' => array(),

	'testMemcacheServers' => array(),

	'liveMemcacheServers' => array(),

	/*
	 * Mysql default
	 */
	'mysqlDefaultHost' => '',
	'mysqlDefaultDbName' => '',

	'mysqlUser' => '',
	'mysqlPassword' => '',

	'mongoUser' => '',
	'mongoPassword' => '',

	/*
	 * Determines if the Unit of Work module ot the persistance is active or not
	 */
	'UnitOfWork' => true,

	/*
	 * Session storage (files | memcached)
	 */
	'sessionStorage' => 'files',

	/*
	 * Session lifetime. 84600 = 24 hours
	*/
	'sessionLife' => 84600 * 5,

	/*
	 * Name for the session
	 */
	'sessionName' => 'KREATE',

	'secureSession' => true,

	/*
	 * Name of the layout file
	 */
	'layout' => 'master',

	/*
	 * Should the framework call the ErrorController on a 404 or just send response code 404
	 */
	'render404' => true,

	/*
	 * HTML title
	 */
	'title' => 'KREATE',

	'devErrorHandlerLevel' => E_ALL,

	'keepSessionVariables' => array(),

	/*
	 * false or 'ob_gzhandler'
	 */
	'zipResponse' => false,
);
