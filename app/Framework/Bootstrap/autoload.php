<?php

use Framework\Support\ClassLoader;

require_once base_dir . 'vendor/autoload.php';

require_once framework_dir . 'Support/ClassLoader.php';
ClassLoader::register();
ClassLoader::addDirectories(require config_dir . 'classMap.php');
