<?php

require_once __DIR__ . '/../bootstrap.php';

use DomainSystem\Core\Application;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Database\MigrationRunner;

$app = Application::getInstance();
if (!$app) {
    fwrite(STDERR, "Erro: Application não instanciada.\n");
    exit(1);
}

$app->boot();

$container = $app->getContainer();
$runner = new MigrationRunner($container->make(DatabaseConnectionInterface::class));

$pluginsPath = DOMAIN_SYSTEM_ROOT . '/src/Plugins';
$migrated = 0;
$errors = 0;

echo "== Migrations Versionadas do Domain System ==\n\n";

foreach (glob($pluginsPath . '/*', GLOB_ONLYDIR) ?: [] as $pluginDir) {
    $pluginName = basename($pluginDir);
    $migrationsPath = $pluginDir . '/migrations';

    if (!is_dir($migrationsPath)) {
        continue;
    }

    $result = $runner->runForPlugin($pluginName, $migrationsPath);

    if (!empty($result['executed'])) {
        echo "✅ {$pluginName}:\n";
        foreach ($result['executed'] as $m) {
            echo "   → {$m}\n";
            $migrated++;
        }
    }

    if (!empty($result['errors'])) {
        echo "❌ {$pluginName}:\n";
        foreach ($result['errors'] as $err) {
            echo "   → {$err}\n";
            $errors++;
        }
    }
}

echo "\n== Resumo ==\n";
echo "Migrations executadas: {$migrated}\n";
echo "Erros: {$errors}\n";

exit($errors > 0 ? 1 : 0);
