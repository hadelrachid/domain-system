<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\Update\MaintenanceModeInterface;
use RuntimeException;

/**
 * Implementação de `MaintenanceModeInterface` baseada no arquivo `<raiz>/.maintenance`.
 */
final class FileMaintenanceMode implements MaintenanceModeInterface
{
    private string $lockFilePath;
    private bool $wasAlreadyEnabled = false;

    public function __construct(string $rootPath)
    {
        $this->lockFilePath = rtrim(str_replace('\\', '/', $rootPath), '/') . '/.maintenance';
        $this->wasAlreadyEnabled = is_file($this->lockFilePath);
    }

    public function enable(string $message = 'maintenance', ?string $adminBypassToken = null): void
    {
        $this->wasAlreadyEnabled = $this->isEnabled();
        $content = trim($message) !== '' ? $message : 'maintenance';

        if (@file_put_contents($this->lockFilePath, $content) === false) {
            throw new RuntimeException(sprintf(
                'Não foi possível ativar o modo de manutenção em "%s".',
                $this->lockFilePath
            ));
        }
    }

    public function disable(): void
    {
        if (is_file($this->lockFilePath)) {
            @unlink($this->lockFilePath);
        }
    }

    public function isEnabled(): bool
    {
        return is_file($this->lockFilePath);
    }

    /**
     * Indica se o modo de manutenção já estava ativo antes da chamada a `enable()`.
     */
    public function wasAlreadyEnabled(): bool
    {
        return $this->wasAlreadyEnabled;
    }
}
