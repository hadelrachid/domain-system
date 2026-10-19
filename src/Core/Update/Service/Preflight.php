<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Contracts\Update\PreflightInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\PreflightReport;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use Throwable;

/**
 * Agregador Composite das verificações de pré-atualização (Preflight).
 */
final class Preflight implements PreflightInterface
{
    /** @var list<PreflightCheckInterface> */
    private array $checks = [];

    /**
     * @param iterable<PreflightCheckInterface> $checks
     */
    public function __construct(iterable $checks = [])
    {
        foreach ($checks as $check) {
            $this->checks[] = $check;
        }
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): PreflightReport
    {
        $items = [];

        foreach ($this->checks as $check) {
            try {
                $results = $check->run($package, $target);
                foreach ($results as $item) {
                    $items[] = $item;
                }
            } catch (Throwable $e) {
                $items[] = new PreflightCheck(
                    id: $check->id(),
                    level: CheckLevel::Block,
                    message: sprintf('Erro na verificação "%s": %s', $check->id(), $e->getMessage()),
                );
            }
        }

        return new PreflightReport($items);
    }
}
