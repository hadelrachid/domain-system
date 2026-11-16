<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DemandMetricsRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DemandTrackerInterface;
use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;

/**
 * Rastreia cliques com proteção anti-ruído baseada na sessão (SRP).
 */
final class SessionGuardedDemandTracker implements DemandTrackerInterface
{
    /**
     * Debounce mínimo (em segundos) apenas para prevenir duplo-clique acidental de mouse.
     * Permite auditoria imediata no exato tempo de cada interação do visitante.
     */
    private const DEFAULT_DEBOUNCE_SECONDS = 2;

    public function __construct(
        private readonly DemandMetricsRepositoryInterface $repository,
        private readonly ?SessionManagerInterface $session = null,
        private readonly int $debounceSeconds = self::DEFAULT_DEBOUNCE_SECONDS
    ) {
    }

    public function recordInteraction(DemandEvent $event): bool
    {
        $productId = trim($event->productId);
        if ($productId === '') {
            return false;
        }

        // Valida duplicação na sessão (evita F5 e cliques repetidos inflarem o placar)
        if ($this->isDuplicate($productId, $event->actionType)) {
            return false;
        }

        $this->repository->incrementMetric(
            $productId,
            $event->productTitle,
            $event->categorySlug,
            $event->actionType,
            $event
        );

        return true;
    }

    private function isDuplicate(string $productId, string $actionType): bool
    {
        if ($this->session === null) {
            return false;
        }

        // Conversões de venda real nunca são barradas por trava de clique
        if ($actionType === 'conversion') {
            return false;
        }

        $sessionKey = 'tracked_drop_' . $actionType . '_' . md5($productId);
        $lastRecorded = (int)$this->session->get($sessionKey, 0);
        $now = time();

        if ($lastRecorded > 0 && ($now - $lastRecorded) < $this->debounceSeconds) {
            return true;
        }

        $this->session->set($sessionKey, $now);
        return false;
    }
}
