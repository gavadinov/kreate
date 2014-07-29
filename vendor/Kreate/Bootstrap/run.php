<?php

require_once kreate_vendor_dir . 'Kreate/Support/helpers.php';
require_once kreate_app_dir . 'routes.php';

$config = new Kreate\Support\Config('app.php');
$request = \Kreate\Http\Request::getInstance();
$app = \Kreate\Foundation\Application::getInstance($config, $request);
$app->redirectIfTrailingSlash();

require_once kreate_app_dir . 'global.php';

return $app;
