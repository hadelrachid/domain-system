<?php

/**
 * Domain-System Bootstrap
 * Initializes the Kernel
 */

use DomainSystem\Core\Application;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

define('DOMAIN_SYSTEM_ROOT', __DIR__);

// Configura o banco de dados principal do sistema para ser um arquivo físico
$dbPath = DOMAIN_SYSTEM_ROOT . '/database.sqlite';
putenv("DB_DSN=sqlite:{$dbPath}");

require_once __DIR__ . '/vendor/autoload.php';

// Include global helper functions
require_once __DIR__ . '/src/Core/helpers.php';

// Initialize Error Handler
$errorLogPath = DOMAIN_SYSTEM_ROOT . '/temp/error_logs.json';
$errorHandler = new \DomainSystem\Core\Error\ErrorHandler($errorLogPath);
$errorHandler->register();

// Initialize Core Components
$container = new Container();
$dispatcher = new EventDispatcher();

// Initialize the Application Kernel
$app = new Application($container, $dispatcher, DOMAIN_SYSTEM_ROOT);

return $app;
