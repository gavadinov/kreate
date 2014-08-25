<?php
error_reporting(E_ALL);

require_once '../vendor/Kreate/Bootstrap/_init.php';

$app = require_once kreate_vendor_dir . 'Kreate/Bootstrap/run.php';

try {
    $app->run();
} catch (Exception $e) {
    $app->handleException($e);
}


$app->shutdown();