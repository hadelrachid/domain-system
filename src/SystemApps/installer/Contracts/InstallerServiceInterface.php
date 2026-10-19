<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\installer\Contracts;

/**
 * Contrato de serviço para o instalador do sistema (SOLID - DIP / N-3).
 */
interface InstallerServiceInterface
{
    public function getOrCreateSetupToken(): string;

    public function regenerateSetupToken(): string;

    public function validateSetupToken(string $inputToken): bool;

    public function checkRequirements(): array;

    public function testConnection(string $driver, string $host, string $port, string $dbname, string $user, string $pass): array;

    public function writeEnvFile(string $dsn, string $user, string $pass): void;
}
