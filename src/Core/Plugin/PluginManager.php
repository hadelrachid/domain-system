<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use Exception;

/**
     * Executa o Boot de todos os plugins na ordem da Pilha (Ring 0 -> Ring 3).
     * 
     * ⚠️ ATENÇÃO - SEGURANÇA DE TIPAGEM E RINGS:
     * Sempre envie os SystemApps e UserPlugins como dois parâmetros estritos separados.
     * O Bootstrapper precisa dessa separação para garantir matematicamente que
     * módulos de usuário (Ring 3) nunca subvertam a inicialização do Kernel (Ring 0),
     * mesmo que declarem dependências maliciosas no JSON.
     */
    public function bootPlugins(): void
    {
        $this->bootstrapper->bootPlugins(
            $this->bootStack->getSystemApps(),
            $this->bootStack->getUserPlugins()
        );
    }

    public function getPlugins(): array
    {
        return $this->bootStack->getOrderedStack();
    }

    public function getActiveStates(): array
    {
        return $this->stateManager->getActiveStates();
    }

    public function enable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;
        $this->stateManager->enable($pluginName);
    }

    public function disable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;
        $this->stateManager->disable($pluginName);
    }

    public function installFromZip(string $zipFilePath): string
    {
        return $this->installer->installFromZip($zipFilePath);
    }

    public function delete(string $pluginName, string $pluginFolder): void
    {
        $this->installer->delete($pluginName, $pluginFolder);
    }

    /**
     * Verifica se um plugin é considerado "core" (protegido).
     * Primeiro checa se está na camada Ring 0 (SystemApps), depois
     * verifica a flag 'core' do próprio plugin.
     */
    public function isCore(string $pluginName): bool
    {
        // Ring 0 → sempre core
        if ($this->bootStack->isSystemApp($pluginName)) {
            return true;
        }

        // Verifica a flag do plugin
        $all = $this->bootStack->getOrderedStack();
        if (isset($all[$pluginName])) {
            return $all[$pluginName]->isCore();
        }

        return false;
    }

    /**
     * Retorna o ProcessRegistry para consulta externa (SystemMonitor).
     */
    public function getProcessRegistry(): Services\ProcessRegistry
    {
        return $this->bootstrapper->getProcessRegistry();
    }
}
