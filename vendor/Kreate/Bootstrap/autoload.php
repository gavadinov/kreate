<?php

require_once kreate_vendor_dir . 'autoload.php';

/**
 * We register our own autoloader "behind" the composers autoloader,
 * so we can keep the users classes in the root namespace level
 */
\Kreate\Support\ClassLoader::register();
\Kreate\Support\ClassLoader::addDirectories(require kreate_config_dir . 'ClassMap.php');
