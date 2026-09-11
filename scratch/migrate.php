<?php
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost');

require_once BASE_PATH . '/vendor/autoload.php';



$app = require BASE_PATH . '/bootstrap.php';
$app->boot();

// Force boot plugins (this should trigger activate/schema building)
$pluginManager = $app->getContainer()->make(\DomainSystem\Core\Plugin\PluginManager::class);
// wait, PluginManager activates plugins when they are installed. Since they are already active in system, it might not run `activate`.

// Let's manually get all plugins and call activate()
$plugins = $pluginManager->getPlugins();
foreach ($plugins as $plugin) {
    echo "Activating " . get_class($plugin) . "...\n";
    try {
        $plugin->activate();
        echo "OK\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
