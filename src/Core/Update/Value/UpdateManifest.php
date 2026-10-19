<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use DomainSystem\Core\Update\Enum\UpdateKind;
use InvalidArgumentException;

final readonly class UpdateManifest
{
    /**
     * @param array{php: string, extensions: list<string>, osVersion: string} $requires
     * @param list<array{path: string, sha256: string, size: int, mode: string}> $files
     * @param list<string> $removed
     * @param list<array{scope: string, name: string, sha256: string, type: string}> $migrations
     * @param array<string, list<string>> $expects
     */
    public function __construct(
        public int $protocol,
        public int $minUpdaterProtocol,
        public UpdateKind $kind,
        public string $slug,
        public string $version,
        public array $requires,
        public array $files,
        public array $removed,
        public array $migrations,
        public array $expects,
    ) {
        if ($this->protocol < 1) {
            throw new InvalidArgumentException('O protocolo do UpdateManifest deve ser >= 1.');
        }
        if ($this->minUpdaterProtocol < 1) {
            throw new InvalidArgumentException('O minUpdaterProtocol do UpdateManifest deve ser >= 1.');
        }
        if (trim($this->slug) === '') {
            throw new InvalidArgumentException('O slug do UpdateManifest não pode ser vazio.');
        }
        if (trim($this->version) === '') {
            throw new InvalidArgumentException('A versão do UpdateManifest não pode ser vazia.');
        }

        if (
            !isset($this->requires['php'], $this->requires['extensions'], $this->requires['osVersion'])
            || !is_string($this->requires['php'])
            || trim($this->requires['php']) === ''
            || !is_array($this->requires['extensions'])
            || !is_string($this->requires['osVersion'])
            || trim($this->requires['osVersion']) === ''
        ) {
            throw new InvalidArgumentException('O campo requires do UpdateManifest é inválido.');
        }
        foreach ($this->requires['extensions'] as $ext) {
            if (!is_string($ext) || trim($ext) === '') {
                throw new InvalidArgumentException('Cada extensão em requires.extensions deve ser uma string não vazia.');
            }
        }

        foreach ($this->files as $entry) {
            if (
                !is_array($entry)
                || !isset($entry['path'], $entry['sha256'], $entry['size'])
                || !is_string($entry['path'])
                || trim($entry['path']) === ''
                || !is_string($entry['sha256'])
                || preg_match('/^[a-fA-F0-9]{64}$/', $entry['sha256']) !== 1
                || !is_int($entry['size'])
                || $entry['size'] < 0
            ) {
                throw new InvalidArgumentException('Entrada inválida em files do UpdateManifest.');
            }
            $mode = $entry['mode'] ?? 'replace';
            if (!in_array($mode, ['replace', 'default_if_missing'], true)) {
                throw new InvalidArgumentException('Modo de arquivo inválido em files do UpdateManifest.');
            }
        }

        foreach ($this->removed as $removedPath) {
            if (!is_string($removedPath) || trim($removedPath) === '') {
                throw new InvalidArgumentException('Cada caminho em removed deve ser uma string não vazia.');
            }
        }

        foreach ($this->migrations as $mig) {
            if (
                !is_array($mig)
                || !isset($mig['scope'], $mig['name'], $mig['sha256'], $mig['type'])
                || !is_string($mig['scope'])
                || trim($mig['scope']) === ''
                || !is_string($mig['name'])
                || trim($mig['name']) === ''
                || !is_string($mig['sha256'])
                || preg_match('/^[a-fA-F0-9]{64}$/', $mig['sha256']) !== 1
                || !in_array($mig['type'], ['additive', 'destructive'], true)
            ) {
                throw new InvalidArgumentException('Entrada inválida em migrations do UpdateManifest.');
            }
        }

        foreach ($this->expects as $table => $columns) {
            if (!is_string($table) || trim($table) === '' || !is_array($columns)) {
                throw new InvalidArgumentException('Entrada inválida em expects do UpdateManifest.');
            }
            foreach ($columns as $col) {
                if (!is_string($col) || trim($col) === '') {
                    throw new InvalidArgumentException('Coluna inválida em expects do UpdateManifest.');
                }
            }
        }
    }
}
