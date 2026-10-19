<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato para persistência atômica e segura contra condições de corrida (race conditions)
 * em arquivos de estado JSON do Kernel (plugins.json, disarmed.json, error_logs.json, etc.).
 */
interface JsonStoreInterface
{
    /**
     * Lê e decodifica um arquivo JSON com lock compartilhado (LOCK_SH).
     *
     * @param array<string|int, mixed> $default
     * @return array<string|int, mixed>
     */
    public function read(string $filePath, array $default = []): array;

    /**
     * Grava um array em formato JSON de forma atômica (arquivo temporário + LOCK_EX + rename).
     *
     * @param array<string|int, mixed> $data
     */
    public function write(string $filePath, array $data): bool;

    /**
     * Executa uma leitura-modificação-gravação protegida por lock exclusivo (LOCK_EX).
     *
     * @param callable(array<string|int, mixed>): array<string|int, mixed> $mutator
     * @param array<string|int, mixed> $default
     * @return array<string|int, mixed>
     */
    public function update(string $filePath, callable $mutator, array $default = []): array;
}
