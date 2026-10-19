<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

final class DiskSpaceCheck implements PreflightCheckInterface
{
    public function __construct(
        private readonly string $rootPath,
        private readonly ?int $freeBytesOverride = null,
    ) {
    }

    public function id(): string
    {
        return 'disk_space';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $manifestBytes = 0;
        foreach ($package->manifest->files as $file) {
            $manifestBytes += $file['size'];
        }

        $baseSize = max($package->release->sizeBytes, $manifestBytes, 1);
        $requiredBytes = $baseSize * 3;

        $freeSpace = $this->freeBytesOverride;
        if ($freeSpace === null) {
            $probed = @disk_free_space($this->rootPath);
            $freeSpace = $probed !== false ? (int)$probed : null;
        }

        if ($freeSpace === null) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Warn,
                    message: 'Não foi possível aferir o espaço livre em disco no servidor.',
                ),
            ];
        }

        if ($freeSpace < $requiredBytes) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Block,
                    message: sprintf(
                        'Espaço em disco insuficiente: livre %d bytes, exigido no mínimo %d bytes (3x o tamanho do pacote).',
                        $freeSpace,
                        $requiredBytes
                    ),
                ),
            ];
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Ok,
                message: sprintf('Espaço livre em disco suficiente (%d bytes disponíveis).', $freeSpace),
            ),
        ];
    }
}
