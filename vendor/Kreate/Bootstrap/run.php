<?php

require_once kreate_vendor_dir . 'Kreate/Support/helpers.php';
require_once kreate_app_dir . 'routes.php';

$config = new Kreate\Support\Config('app.php');
$request = \Kreate\Http\Request::getInstance();
$app = new \Kreate\Foundation\Application($config, $request);
$app->redirectIfTrailingSlash();

return $app;
