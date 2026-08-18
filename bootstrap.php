<?php

/**
 * Domain-System Bootstrap
 * Initializes the Kernel
 */

use DomainSystem\Core\Application;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

define('DOMAIN_SYSTEM_ROOT', __DIR__);

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Core Components
$container = new Container();
$dispatcher = new EventDispatcher();

// Initialize the Application Kernel
$app = new Application($container, $dispatcher, DOMAIN_SYSTEM_ROOT);

return $app;
