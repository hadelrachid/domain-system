<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use Closure;
use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Contracts\Update\ProtectedPathsInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;

final class WritablePathsCheck implements PreflightCheckInterface
{
    /** @var Closure(string): bool */
    private Closure $writableCheck;

    /**
     * @param (callable(string): bool)|null $writableCheck
     */
    public function __construct(
        private readonly string $rootPath,
        private readonly ProtectedPathsInterface $protectedPaths,
        ?callable $writableCheck = null,
    ) {
        $this->writableCheck = $writableCheck !== null
            ? Closure::fromCallable($writableCheck)
            : static function (string $path): bool {
                $current = $path;
                while (!file_exists($current) && dirname($current) !== $current) {
                    $current = dirname($current);
                }
                return is_writable($current);
            };
    }

    public function id(): string
    {
        return 'writable_paths';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        $root = rtrim(str_replace('\\', '/', $this->rootPath), '/');
        $candidates = array_merge(
            ['', 'storage', 'temp', 'config'],
            $this->protectedPaths->allowedRoots($target->kind, $target->slug),
        );

        $notWritable = [];
        foreach ($candidates as $rel) {
            $cleanRel = trim(str_replace('\\', '/', $rel), '/');
            $fullPath = $cleanRel === '' ? $root : $root . '/' . $cleanRel;

            if (!($this->writableCheck)($fullPath)) {
                $notWritable[] = $cleanRel === '' ? '.' : $cleanRel;
            }
        }

        $notWritable = array_values(array_unique($notWritable));
        if ($notWritable !== []) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Block,
                    message: sprintf(
                        'Sem permissão de escrita nos caminhos obrigatórios: %s.',
                        implode(', ', $notWritable)
                    ),
                ),
            ];
        }

        return [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Ok,
                message: 'Todos os diretórios de destino, storage, temp e config possuem permissão de escrita.',
            ),
        ];
    }
}
