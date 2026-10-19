<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Contracts\Update\VersionComparatorInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

final class DowngradeCheck implements PreflightCheckInterface
{
    public function __construct(
        private readonly VersionComparatorInterface $comparator,
    ) {
    }

    public function id(): string
    {
        return 'downgrade';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $candidate = $package->manifest->version;
        $installed = $target->installedVersion;

        if (!$this->comparator->isValid($candidate) || !$this->comparator->isValid($installed)) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Block,
                    message: sprintf('Versão inválida para comparação SemVer (%s -> %s).', $installed, $candidate),
                ),
            ];
        }

        if ($this->comparator->isNewer($candidate, $installed)) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Ok,
                    message: sprintf('Progressão de versão válida (%s -> %s).', $installed, $candidate),
                ),
            ];
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Block,
                message: sprintf(
                    'Downgrade ou reinstalação da mesma versão bloqueado (instalada: %s, pacote: %s).',
                    $installed,
                    $candidate
                ),
            ),
        ];
    }
}
