<?php
$app = require __DIR__ . '/bootstrap.php';

echo "Iniciando reinstalação limpa...\n\n";

$app->getDispatcher()->dispatch('kernel_pre_boot');
$app->boot();

$manager = $app->getPluginManager();

// Explicitly install/activate in order
$order = [
    'database', 'auth', 'patients', 'doctors', 'appointments', 'clinic_pack'
];

$plugins = $manager->getPlugins();

foreach ($order as $name) {
    if (isset($plugins[$name])) {
        $plugin = $plugins[$name];
        echo "Instalando plugin: " . $plugin->getName() . "...\n";
        if (method_exists($plugin, 'install')) $plugin->install();
        if (method_exists($plugin, 'activate')) $plugin->activate();
    }
}

// Then the rest
foreach ($manager->getPlugins() as $plugin) {
    if (!in_array($plugin->getName(), $order)) {
        echo "Instalando plugin: " . $plugin->getName() . "...\n";
        if (method_exists($plugin, 'install')) $plugin->install();
        if (method_exists($plugin, 'activate')) $plugin->activate();
    }
}

echo "\nRecriando Usuário Admin Padrão...\n";
$db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class);
$password = password_hash('123456', PASSWORD_BCRYPT);
try {
    $db->getPdo()->exec("INSERT INTO users (name, email, password, role) VALUES ('Administrador', 'admin@admin.com', '{$password}', 'admin')");
    echo "Usuário admin criado (admin@admin.com / 123456).\n";
} catch (Exception $e) {
    echo "Admin já existe ou erro: " . $e->getMessage() . "\n";
}

// Seeder de médicos
echo "\nSemeando médicos...\n";
try {
    $db->getPdo()->exec("INSERT INTO doctors (name, crm, specialty) VALUES ('Dr. Roberto', '12345', 'Cardiologista')");
    $db->getPdo()->exec("INSERT INTO doctors (name, crm, specialty) VALUES ('Dra. Amanda', '54321', 'Dermatologista')");
    echo "Médicos semeados.\n";
} catch (Exception $e) {
    echo "Médicos já semeados ou erro.\n";
}

echo "\nSeu banco de dados local foi reconstruído com sucesso!\n";
