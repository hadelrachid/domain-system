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
        $pascalName = str_replace(' ', '', ucwords(str_replace('_', ' ', $pluginName)));
        $dir = sanitizePath($basePath, $pluginName);

        foreach (['', '/Contracts', '/Controllers', '/views', '/manual'] as $sub) {
            if (!is_dir($dir . $sub)) {
                mkdir($dir . $sub, 0777, true);
            }
        }

        // 1. plugin.json
        file_put_contents($dir . '/plugin.json', json_encode([
            "name"        => $pluginName,
            "version"     => "1.0.0",
            "author"      => "CLI-Workstation",
            "description" => "Plugin Ring 3 gerado via OS-CLI com contratos SOLID.",
            "core"        => false,
            "os_version"  => "2.3"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Contracts/<Pascal>ServiceInterface.php
        file_put_contents($dir . "/Contracts/{$pascalName}ServiceInterface.php", <<<PHP
<?php

declare(strict_types=1);

namespace DomainSystem\\Plugins\\{$pluginName}\\Contracts;

interface {$pascalName}ServiceInterface
{
    public function getTitle(): string;
}
PHP
        );

        // 3. Controllers/<Pascal>Controller.php
        file_put_contents($dir . "/Controllers/{$pascalName}Controller.php", <<<PHP
<?php

declare(strict_types=1);

namespace DomainSystem\\Plugins\\{$pluginName}\\Controllers;

use DomainSystem\\Core\\Contracts\\ResponseInterface;
use DomainSystem\\Core\\Http\\Request;
use DomainSystem\\Core\\Http\\Responses\\ViewResponse;

class {$pascalName}Controller
{
    public function index(Request \$request): ResponseInterface
    {
        ob_start();
        include dirname(__DIR__) . '/views/index.php';
        return new ViewResponse((string)ob_get_clean());
    }
}
PHP
        );

        // 4. views/index.php
        file_put_contents($dir . '/views/index.php', <<<HTML
<div class="wrap">
    <h1>🧩 Módulo: {$pascalName}</h1>
    <p>Plugin gerado em conformidade com os contratos Ring 3 do Domain-System OS.</p>
</div>
HTML
        );

        // 5. manual/module.php (Auto-descoberto pelo ManualModuleInterface)
        file_put_contents($dir . '/manual/module.php', <<<PHP
<?php

declare(strict_types=1);

use DomainSystem\\Core\\Contracts\\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string { return '{$pluginName}'; }
    public function getTitle(): string { return 'Plugin {$pascalName}'; }
    public function getSubtitle(): string { return 'Módulo gerado via OS-CLI com contrato ManualModuleInterface.'; }
    public function getBadge(): string { return 'Módulo Auto-Descoberto'; }
    public function getAccentColor(): string { return '#06b6d4'; }
    public function getAdminUrl(): ?string { return '/admin/{$pluginName}'; }
    public function getChecklistItems(): array
    {
        return [
            ['id' => '{$pluginName}-boot', 'label' => 'Validar rota administrativa /admin/{$pluginName} sem erros.'],
        ];
    }
    public function renderContent(string \$baseUrl = ''): string
    {
        return '<p>Documentação automática do módulo <strong>{$pascalName}</strong>.</p>';
    }
};
PHP
        );

        // 6. Plugin.php (OsExtensionInterface)
        file_put_contents($dir . '/Plugin.php', <<<PHP
<?php

declare(strict_types=1);

namespace DomainSystem\\Plugins\\{$pluginName};

use DomainSystem\\Core\\Contracts\\OsConnectorInterface;
use DomainSystem\\Core\\Contracts\\OsExtensionInterface;
use DomainSystem\\Core\\Contracts\\OsRuntimeInterface;
use DomainSystem\\Core\\Plugin\\AbstractPlugin;
use DomainSystem\\Core\\Routing\\Router;
use DomainSystem\\Plugins\\{$pluginName}\\Controllers\\{$pascalName}Controller;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface \$os): void
    {
        \$os->listenHook('admin.menu');
        \$os->listenHook('router.register');
    }

    public function osBoot(OsRuntimeInterface \$runtime): void
    {
        \$runtime->onHook('admin.menu', function (array \$menus, string \$role = 'guest'): array {
            if (\$role === 'admin') {
                \$menus[] = [
                    'title' => '{$pascalName}',
                    'url'   => '/admin/{$pluginName}',
                    'icon'  => '🧩',
                ];
            }
            return \$menus;
        });

        \$runtime->onHook('router.register', function (Router \$router): void {
            \$router->addRoute('GET', '/admin/{$pluginName}', [{$pascalName}Controller::class, 'index'], '{$pluginName}', ['admin']);
        });
    }
}
PHP
        );

        echo "✅ Plugin SOLID Scaffolded com sucesso no Workstation: {$pluginName}\n";
        echo "Estrutura criada: plugin.json, Plugin.php, Contracts/, Controllers/, views/ e manual/module.php\n";
        echo "Após terminar o desenvolvimento, compacte e instale pelo painel!\n";
        break;

    default:
        echo "Comando não reconhecido: {$command}\n";
        break;
}
