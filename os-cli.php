<?php
/**
 * OS-CLI: O Terminal Emulado do Domain System
 * 
 * Uma interface de linha de comando segura (Sandbox) desenvolvida em PHP.
 * Aceita comandos no formato nativo do Linux (ls, mkdir, cat, rm) para 
 * ser amigável a Inteligências Artificiais e Desenvolvedores web, 
 * protegendo o sistema contra fugas de diretório (Path Traversal).
 */

require_once __DIR__ . '/vendor/autoload.php';

// Verificação de segurança (Se está rodando via CLI de verdade ou Web Terminal)
if (php_sapi_name() !== 'cli' && !isset($_GET['web_cli_token'])) {
    die("Acesso Restrito: Execute via terminal (php os-cli.php) ou forneça token válido.");
}

$args = $argv;
array_shift($args); // Remove o nome do script

if (empty($args)) {
    echo "OS-CLI v2.1.0 - Domain System Sandbox\n";
    echo "Comandos Suportados: ls, mkdir, rm, cat, touch, make:plugin, make:widget\n";
    exit;
}

$command = strtolower($args[0]);
$basePath = __DIR__ . '/workstation';

// Cria a pasta workstation se não existir
if (!is_dir($basePath)) {
    mkdir($basePath, 0777, true);
}

// Função auxiliar para sanitizar caminhos e prevenir sair do diretório workstation
function sanitizePath($base, $path) {
    $realBase = realpath($base);
    $target = $base . '/' . ltrim($path, '/');
    $realTarget = realpath(dirname($target)) . '/' . basename($target);
    
    // Se o caminho tentar sair da raiz da workstation, bloqueia
    if (strpos(realpath(dirname($target)), $realBase) !== 0) {
         die("Erro de Segurança: Caminho fora do Laboratório (workstation) permitido.");
    }
    return $target;
}

switch ($command) {
    case 'ls':
        $dir = sanitizePath($basePath, $args[1] ?? '.');
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo (is_dir($dir . '/' . $file) ? "[DIR] " : "[FILE] ") . $file . "\n";
                }
            }
        } else {
            echo "Erro: Diretório não encontrado no workstation.\n";
        }
        break;

    case 'mkdir':
        if (!isset($args[1])) die("Erro: Falta o nome da pasta (ex: mkdir meu_plugin)\n");
        $dir = sanitizePath($basePath, $args[1]);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "Diretório criado no workstation: {$args[1]}\n";
        } else {
            echo "Aviso: Diretório já existe.\n";
        }
        break;

    case 'cat':
        if (!isset($args[1])) die("Erro: Faltou o nome do arquivo.\n");
        $file = sanitizePath($basePath, $args[1]);
        if (file_exists($file) && is_file($file)) {
            echo file_get_contents($file) . "\n";
        } else {
            echo "Erro: Arquivo não encontrado no workstation.\n";
        }
        break;

    case 'rm':
        if (!isset($args[1])) die("Erro: Faltou o nome do arquivo/pasta.\n");
        // Simulação super simplificada de remoção
        $target = sanitizePath($basePath, end($args)); 
        if (file_exists($target)) {
            if (is_dir($target)) {
                echo "Use comandos mais específicos do PHP para remover pastas por agora.\n";
            } else {
                unlink($target);
                echo "Arquivo removido do workstation: " . end($args) . "\n";
            }
        } else {
            echo "Erro: Alvo não encontrado.\n";
        }
        break;

    case 'make:plugin':
        if (!isset($args[1])) die("Erro: Qual o nome do plugin? (ex: make:plugin radio_online)\n");
        $pluginName = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($args[1]));
        $dir = sanitizePath($basePath, $pluginName);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        // Gera plugin.json
        file_put_contents($dir . '/plugin.json', json_encode([
            "name" => $pluginName,
            "version" => "1.0.0",
            "author" => "CLI-Workstation",
            "type" => "module"
        ], JSON_PRETTY_PRINT));
        
        echo "✅ Plugin Scaffolded com sucesso no Workstation: {$pluginName}\n";
        echo "Após terminar o desenvolvimento, compacte e instale pelo painel!\n";
        break;

    default:
        echo "Comando não reconhecido: {$command}\n";
        break;
}
