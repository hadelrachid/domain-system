<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

final class PhpVersionCheck implements PreflightCheckInterface
{
    public function __construct(
        private readonly string $currentPhpVersion = PHP_VERSION,
    ) {
    }

    public function id(): string
    {
        return 'php_version';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $requiredPhp = ltrim(trim($package->manifest->requires['php']), '>=vV ');
        if ($requiredPhp === '') {
            $requiredPhp = '8.1.0';
        }

        if (version_compare($this->currentPhpVersion, $requiredPhp, '>=')) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Ok,
                    message: sprintf('Versão do PHP compatível (%s >= %s).', $this->currentPhpVersion, $requiredPhp),
                ),
            ];
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Block,
                message: sprintf(
                    'Versão do PHP incompatível: atual %s, mínimo exigido pelo pacote %s.',
                    $this->currentPhpVersion,
                    $requiredPhp
                ),
            ),
        ];
    }
}
