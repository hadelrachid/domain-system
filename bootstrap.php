<?php

/**
 * Domain-System Bootstrap
 * Initializes the Kernel
 */

use DomainSystem\Core\Application;
use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;

define('DOMAIN_SYSTEM_ROOT', __DIR__);

// Configura o banco de dados principal (Lido do .env abaixo)
// $dbPath = DOMAIN_SYSTEM_ROOT . '/database.sqlite';
// putenv("DB_DSN=sqlite:{$dbPath}");
// Carrega variáveis de ambiente (Ignora erros se não existir, mas em prod o servidor deve prover)
$envFile = DOMAIN_SYSTEM_ROOT . '/.env';
if (file_exists($envFile)) {
    $envVariables = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if (is_array($envVariables)) {
        foreach ($envVariables as $key => $value) {
            $value = trim($value, '"\'');
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Configuração de Fuso Horário
$timezone = getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo';
date_default_timezone_set($timezone);

require_once __DIR__ . '/vendor/autoload.php';

// Custom autoloader para resolver classes dentro do bundled_plugins sem depender do composer dump-autoload
spl_autoload_register(function ($class) {
    $prefix = 'DomainSystem\\Plugins\\';
    if (str_starts_with($class, $prefix)) {
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/src/Plugins/clinic_pack/bundled_plugins/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Include global helper functions
require_once __DIR__ . '/src/Core/helpers.php';

// Initialize Error Handler
$errorLogPath = DOMAIN_SYSTEM_ROOT . '/temp/error_logs.json';
$configPath = DOMAIN_SYSTEM_ROOT . '/config/plugins.json';
$disarmedPath = DOMAIN_SYSTEM_ROOT . '/temp/disarmed.json';

$logger = new \DomainSystem\Core\Error\ErrorLogger($errorLogPath);
$circuitBreaker = new \DomainSystem\Core\Error\CircuitBreaker($configPath, $disarmedPath);
$renderer = new \DomainSystem\Core\Error\ErrorRenderer();

$errorHandler = new \DomainSystem\Core\Error\ErrorHandler($logger, $circuitBreaker, $renderer);
$errorHandler->register();

// Initialize Core Components
$container = new Container();
$dispatcher = new EventDispatcher();

// Initialize the Application Kernel
$app = new Application($container, $dispatcher, DOMAIN_SYSTEM_ROOT);

return $app;
