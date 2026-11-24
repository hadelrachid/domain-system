<?php
require dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'DomainSystem\\Tests\\')) {
        $rel = substr($class, strlen('DomainSystem\\Tests\\'));
        $file = __DIR__ . '/' . str_replace('\\', '/', $rel) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

define('DOMAIN_SYSTEM_ROOT', dirname(__DIR__));

if (!defined('DS_VERSION')) {
    $composerData = json_decode((string)file_get_contents(dirname(__DIR__) . '/composer.json'), true);
    define('DS_VERSION', (string)($composerData['version'] ?? '2.8.3'));
}
if (!defined('DS_OS_VERSION')) {
    $versionParts = explode('.', (string)($composerData['version'] ?? '2.8.3'));
    define('DS_OS_VERSION', $versionParts[0] . '.' . ($versionParts[1] ?? '0'));
}
