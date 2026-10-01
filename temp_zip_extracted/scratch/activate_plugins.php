<?php
require 'bootstrap.php';
$app = \DomainSystem\Core\Application::getInstance();
$plugins = [
    \DomainSystem\Plugins\patients\Plugin::class,
    \DomainSystem\Plugins\doctors\Plugin::class,
    \DomainSystem\Plugins\appointments\Plugin::class,
    \DomainSystem\Plugins\medical_records\Plugin::class,
    \DomainSystem\Plugins\finance\Plugin::class,
    \DomainSystem\Plugins\triage\Plugin::class
];

foreach ($plugins as $class) {
    if (class_exists($class)) {
        $plugin = new $class($app->getContainer(), $app->getDispatcher());
        if (method_exists($plugin, 'activate')) {
            $plugin->activate();
            echo "Activated: $class\n";
        }
    } else {
        echo "Not found: $class\n";
    }
}
