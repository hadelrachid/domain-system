<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use Closure;
use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

final class ExtensionsCheck implements PreflightCheckInterface
{
    /** @var Closure(string): bool */
    private Closure $extensionLoadedDetector;

    /**
     * @param (callable(string): bool)|null $extensionLoadedDetector
     */
    public function __construct(?callable $extensionLoadedDetector = null)
    {
        $this->extensionLoadedDetector = $extensionLoadedDetector !== null
            ? Closure::fromCallable($extensionLoadedDetector)
            : static fn(string $ext): bool => extension_loaded($ext)
                || ($ext === 'zip' && class_exists(\PharData::class) && extension_loaded('zlib'));
    }

    public function id(): string
    {
        return 'extensions';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $required = array_values(array_unique(array_merge(
            ['curl', 'zip'],
            $package->manifest->requires['extensions'],
        )));

        $missing = [];
        foreach ($required as $ext) {
            if (!($this->extensionLoadedDetector)($ext)) {
                $missing[] = $ext;
            }
        }

        if ($missing === []) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Ok,
                    message: sprintf('Todas as extensões PHP obrigatórias estão ativas (%s).', implode(', ', $required)),
                ),
            ];
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Block,
                message: sprintf('Extensões PHP obrigatórias ausentes: %s.', implode(', ', $missing)),
            ),
        ];
    }
}
