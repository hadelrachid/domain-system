<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Plugin\PluginInterface;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: PluginBootStack (A Pilha de Inicialização do Kernel)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * O Domain System OS separa seus módulos em duas camadas de confiança,
 * seguindo o modelo de "Rings" dos Sistemas Operacionais reais:
 *
 *   ┌───────────────────────────────────────────────┐
 *   │  Ring 0 — SystemApps (Kernel Space)           │
 *   │  Database, Auth, SystemAdmin, SystemMonitor   │
 *   │  → Sempre ativos, não podem ser desligados    │
 *   │  → Boot PRIMEIRO (garantia de infraestrutura) │
 *   ├───────────────────────────────────────────────┤
 *   │  Ring 3 — Plugins de Usuário (User Space)     │
 *   │  Pages, Academy, Builder Flex, Ecommerce      │
 *   │  → Podem ser ativados/desativados pelo admin  │
 *   │  → Boot DEPOIS (dependem da infraestrutura)   │
 *   └───────────────────────────────────────────────┘
 *
 * POR QUE ESSA SEPARAÇÃO?
 * ───────────────────────
 * 1. SEGURANÇA: Um plugin de usuário malicioso ou com bug não consegue 
 *    desligar o Banco de Dados ou o módulo de Autenticação.
 * 2. ESTABILIDADE: Se o plugin 'Academy' explodir ao dar Boot, o sistema 
 *    continua rodando perfeitamente porque Database e Auth já estão vivos.
 * 3. CLAREZA ARQUITETURAL: Auditorias (humanas e de IA) conseguem enxergar 
 *    imediatamente o que é infraestrutura protegida e o que é extensão.
 *
 * COMO O APPLICATION.PHP USA ESTA CLASSE:
 * ────────────────────────────────────────
 *   // 1. Carrega SystemApps (Ring 0) — forceActive=true, isSystemApp=true
 *   $pluginManager->discoverPlugins($systemAppsPath, $configPath, true, true);
 *   
 *   // 2. Carrega Plugins de Usuário (Ring 3) — lê plugins.json
 *   $pluginManager->discoverPlugins($pluginsPath, $configPath, false, false);
 *   
 *   // 3. Boot na ordem da Pilha (Sistema → Usuário)
 *   $pluginManager->bootPlugins();
 *
 * PADRÃO DE PROJETO: Composite / Priority Queue
 * PRINCÍPIO SOLID:   SRP — só gerencia a ordem de inicialização.
 */
class PluginBootStack
{
    /** @var PluginInterface[] Módulos nativos (Ring 0 — protegidos) */
    private array $systemApps = [];

    /** @var PluginInterface[] Plugins de usuário (Ring 3 — controlados) */
    private array $userPlugins = [];

    /**
     * Empilha um módulo nativo do sistema (Ring 0).
     * Estes módulos NÃO podem ser desligados pelo painel administrativo.
     */
    public function pushSystemApp(PluginInterface $plugin): void
    {
        $this->systemApps[$plugin->getName()] = $plugin;
    }

    /**
     * Empilha um plugin de usuário (Ring 3).
     * Estes plugins podem ser ativados/desativados livremente pelo admin.
     */
    public function pushUserPlugin(PluginInterface $plugin): void
    {
        $this->userPlugins[$plugin->getName()] = $plugin;
    }

    /**
     * Retorna a pilha consolidada na ordem estrita de dependência:
     * Primeiro todos os SystemApps (Ring 0), depois os Plugins (Ring 3).
     *
     * O PluginBootstrapper consome esta lista para garantir que o Banco de 
     * Dados, Auth e outros módulos vitais estejam prontos ANTES de qualquer 
     * plugin de usuário tentar acessá-los.
     *
     * @return PluginInterface[]
     */
    public function getOrderedStack(): array
    {
        return array_merge($this->systemApps, $this->userPlugins);
    }

    /** @return PluginInterface[] Apenas os módulos nativos (Ring 0) */
    public function getSystemApps(): array
    {
        return $this->systemApps;
    }

    /** @return PluginInterface[] Apenas os plugins de usuário (Ring 3) */
    public function getUserPlugins(): array
    {
        return $this->userPlugins;
    }

    /**
     * Verifica se um plugin pertence à camada de sistema (Ring 0).
     * Usado pelo No-Break Shield para determinar o nível de Severidade.
     */
    public function isSystemApp(string $pluginName): bool
    {
        return isset($this->systemApps[$pluginName]);
    }
}
