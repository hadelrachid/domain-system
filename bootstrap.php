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
// Carrega variáveis de ambiente (Ignora erros se não existir, mas em prod o servidor deve prover)
$envFile = DOMAIN_SYSTEM_ROOT . '/.env';
if (file_exists($envFile)) {
    $envVariables = parse_ini_file($envFile);
    if (is_array($envVariables)) {
        foreach ($envVariables as $key => $value) {
            putenv("$key=$value");
        }
    }
}

// Configuração de Fuso Horário
$timezone = getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo';
date_default_timezone_set($timezone);

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
