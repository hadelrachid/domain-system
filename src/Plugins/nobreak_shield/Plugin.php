<?php
namespace DomainSystem\Plugins\nobreak_shield;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnectorInterface $os): void
    {
        // Interceptar o Kernel não requer hooks nominais, pois atua na camada PHP, 
        // mas marcamos a intenção de inicializar o Shield.
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // O No-Break Shield nativo agora é gerenciado pelo Core\Error\ErrorHandler.
        // Este plugin foi desativado para evitar sobreposição de tratamentos de erro (Concorrência de Exception Handlers).
    }

    public function activate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void
    {
    }

    public function deactivate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void
    {
    }
}
