<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts\Update;

/**
 * Contrato de controle do modo de manutenção do sistema no Core (Ring 0).
 *
 * Emenda 4: adaptador sobre o arquivo `<raiz>/.maintenance` já respeitado pelo `public/index.php` e `MaintenancePolicy`.
 */
interface MaintenanceModeInterface
{
    /**
     * Ativa o modo de manutenção.
     */
    public function enable(string $message = 'maintenance', ?string $adminBypassToken = null): void;

    /**
     * Desativa o modo de manutenção.
     */
    public function disable(): void;

    /**
     * Indica se o modo de manutenção está ativo no momento.
     */
    public function isEnabled(): bool;
}
