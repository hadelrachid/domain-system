<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Plugin\PluginInterface;

/**
 * Gerencia a Pilha (Stack) de Inicialização do Sistema
 * Garante que os SystemApps (Ring 0) inicializem antes dos Plugins de Usuário (Ring 3).
 */
class PluginBootStack
{
    private array $systemApps = [];
    private array $userPlugins = [];

    public function pushSystemApp(PluginInterface $plugin): void
    {
        $this->systemApps[$plugin->getName()] = $plugin;
    }

    public function pushUserPlugin(PluginInterface $plugin): void
    {
        $this->userPlugins[$plugin->getName()] = $plugin;
    }

    /**
     * Retorna a pilha consolidada na ordem estrita de dependência (Sistema -> Usuário)
     *
     * @return PluginInterface[]
     */
    public function getOrderedStack(): array
    {
        return array_merge($this->systemApps, $this->userPlugins);
    }
    
    public function getSystemApps(): array { return $this->systemApps; }
    public function getUserPlugins(): array { return $this->userPlugins; }
}
