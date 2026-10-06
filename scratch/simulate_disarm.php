<?php
$pluginsFile = __DIR__ . '/../config/plugins.json';
$disarmedFile = __DIR__ . '/../temp/disarmed.json';

// Desativa o settings no plugins.json
$plugins = json_decode(file_get_contents($pluginsFile), true) ?: [];
$plugins['settings'] = false;
file_put_contents($pluginsFile, json_encode($plugins, JSON_PRETTY_PRINT));

// Adiciona o settings na lista de desarmados pelo disjuntor
$disarmed = [];
if (file_exists($disarmedFile)) {
    $disarmed = json_decode(file_get_contents($disarmedFile), true) ?: [];
}
$disarmed['settings'] = [
    'time' => time(),
    'error' => 'Simulação de erro fatal para teste do Modo Desenvolvedor',
    'file' => 'src/Plugins/settings/Controllers/SettingsController.php',
    'line' => 42
];
file_put_contents($disarmedFile, json_encode($disarmed, JSON_PRETTY_PRINT));

echo "Plugin 'settings' foi desarmado artificialmente para testes!";
