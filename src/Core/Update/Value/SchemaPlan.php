<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class SchemaPlan
{
    /**
     * @param list<array{scope: string, name: string, sha256: string, type: string, path?: string}> $pending
     * @param list<string> $blocked
     */
    public function __construct(
        public string $scope,
        public array $pending,
        public array $blocked,
        public bool $destructive,
        public bool $requiresDatabaseBackup,
    ) {
        if (trim($this->scope) === '') {
            throw new InvalidArgumentException('O escopo do SchemaPlan não pode ser vazio.');
        }

        foreach ($this->pending as $item) {
            if (
                !is_array($item)
                || !isset($item['scope'], $item['name'], $item['sha256'], $item['type'])
                || !is_string($item['scope'])
                || trim($item['scope']) === ''
                || !is_string($item['name'])
                || trim($item['name']) === ''
                || !is_string($item['sha256'])
                || preg_match('/^[a-fA-F0-9]{64}$/', $item['sha256']) !== 1
                || !in_array($item['type'], ['additive', 'destructive'], true)
            ) {
                throw new InvalidArgumentException('Item pendente inválido em SchemaPlan::pending.');
            }
        }

        foreach ($this->blocked as $blockedMigration) {
            if (!is_string($blockedMigration) || trim($blockedMigration) === '') {
                throw new InvalidArgumentException('Cada item em SchemaPlan::blocked deve ser uma string não vazia.');
            }
        }
    }
}
