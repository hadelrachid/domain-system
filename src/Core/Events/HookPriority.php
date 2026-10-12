<?php

declare(strict_types=1);

namespace DomainSystem\Core\Events;

/**
 * Constantes canônicas de prioridade para Eventos e Hooks do Kernel.
 * Como o EventDispatcher usa ksort() (ordem ascendente), números menores executam PRIMEIRO.
 */
final class HookPriority
{
    public const HIGHEST = -1000; // Executa primeiro (ex: Installer, Guard de Manutenção)
    public const HIGH    = -500;  // Executa antes dos padrões (ex: Autenticação/RBAC)
    public const NORMAL  = 0;     // Padrão de execução para plugins
    public const LOW     = 500;   // Executa depois dos padrões
    public const LOWEST  = 1000;  // Executa por último (ex: Loggers, Fallbacks)

    private function __construct() {}
}
