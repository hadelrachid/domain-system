<?php
require_once __DIR__ . "/../bootstrap.php";
$app = \DomainSystem\Core\Application::getInstance();
$app->boot();

echo "Running migrations...\n";

// Reactivate all plugins to make sure we run all activates
$pluginsJson = json_decode(file_get_contents("config/plugins.json"), true);
$activePlugins = [];
foreach ($pluginsJson as $pluginName => $isActive) {
    if ($isActive) {
        $activePlugins[] = $pluginName;
    }
}
$pm = $app->getPluginManager();
foreach ($activePlugins as $name) {
    $plugin = current(array_filter($pm->getPlugins(), fn($p) => $p->getName() === $name));
    if ($plugin) {
        echo "Running activate() for: " . $plugin->getName() . "\n";
        try {
            $plugin->activate();
        } catch (\Exception $e) {
            echo "Error activating " . $plugin->getName() . ": " . $e->getMessage() . "\n";
        }
    }
}
echo "Migrations complete.\n";

