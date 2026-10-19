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
