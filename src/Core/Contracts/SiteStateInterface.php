<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato oficial para o ciclo de vida e estado de visibilidade do site.
 */
interface SiteStateInterface
{
    public const STATUS_COMING_SOON = 'coming_soon';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_LIVE        = 'live';

    public function getStatus(): string;
    public function isComingSoon(): bool;
    public function isMaintenance(): bool;
    public function isLive(): bool;
    public function getActiveTheme(): string;
}
