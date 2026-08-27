<?php
require 'vendor/autoload.php';
require 'src/Core/helpers.php';
$app = require 'bootstrap.php';
define('BASE_URL', 'http://localhost');
$app->boot();

$html = '
    <h2>Testando Shortcodes de Domínio</h2>
    
    <h3>Formulário de Pacientes</h3>
    [paciente_form]
    
    <h3>Lista de Pacientes</h3>
    [paciente_lista limit="2"]
    
    <h3>Formulário de Agendamento</h3>
    [agendamento_form doctor_id="1"]
';

print_r($app->getShortcodeManager()->getRegisteredShortcodes());
$parsed = do_shortcode($html);

if (strpos($parsed, 'ds-shortcode-form') !== false && strpos($parsed, 'ds-shortcode-list') !== false && strpos($parsed, 'ds-shortcode-booking') !== false) {
    echo "SUCCESS: Todos os shortcodes foram renderizados corretamente!\n";
} else {
    echo "ERROR: Falha na renderização dos shortcodes.\n";
    echo $parsed;
}
