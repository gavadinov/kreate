<?php

require_once '../vendor/Kreate/Bootstrap/_init.php';
require_once kreate_vendor_dir . 'Kreate/Bootstrap/autoload.php';

$app = require_once kreate_vendor_dir . 'Kreate/Bootstrap/run.php';

try {
    $app->run();
} catch (Exception $e) {
    $app->handleException($e);
}


$app->shutdown();