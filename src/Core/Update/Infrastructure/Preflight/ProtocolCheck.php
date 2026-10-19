<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Contracts\Update\UpdateManifestParserInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

final class ProtocolCheck implements PreflightCheckInterface
{
    public function __construct(
        private readonly UpdateManifestParserInterface $manifestParser,
    ) {
    }

    public function id(): string
    {
        return 'protocol';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $supported = $this->manifestParser->supportedProtocols();
        $maxSupported = $supported !== [] ? max($supported) : 1;

        if ($package->manifest->minUpdaterProtocol <= $maxSupported) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Ok,
                    message: sprintf(
                        'Protocolo do atualizador compatível (mínimo exigido: v%d, suportado: v%d).',
                        $package->manifest->minUpdaterProtocol,
                        $maxSupported
                    ),
                ),
            ];
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Block,
                message: sprintf(
                    'Este pacote exige protocolo de atualizador >= v%d (atual suporta até v%d). Atualize manualmente.',
                    $package->manifest->minUpdaterProtocol,
                    $maxSupported
                ),
            ),
        ];
    }
}
