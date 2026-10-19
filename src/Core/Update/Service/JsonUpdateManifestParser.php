<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\Update\UpdateManifestParserInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\InvalidManifestException;
use DomainSystem\Core\Update\Value\UpdateManifest;
use InvalidArgumentException;
use JsonException;

/**
 * Parser e validador estrito do `manifest.json` de pacotes de atualização (protocolo v1).
 */
final class JsonUpdateManifestParser implements UpdateManifestParserInterface
{
    /**
     * @return list<int>
     */
    public function supportedProtocols(): array
    {
        return [1];
    }

    public function parse(string $json): UpdateManifest
    {
        if (trim($json) === '') {
            throw new InvalidManifestException('O conteúdo do manifest.json está vazio.');
        }

        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidManifestException('JSON inválido no manifest.json: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new InvalidManifestException('O manifest.json deve representar um objeto JSON.');
        }

        $requiredKeys = [
            'protocol',
            'minUpdaterProtocol',
            'kind',
            'slug',
            'version',
            'requires',
            'files',
            'removed',
            'migrations',
            'expects',
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidManifestException(sprintf('Campo obrigatório "%s" ausente no manifest.json.', $key));
            }
        }

        if (!is_int($data['protocol'])) {
            throw new InvalidManifestException('O campo "protocol" deve ser um inteiro.');
        }

        if (!in_array($data['protocol'], $this->supportedProtocols(), true)) {
            throw new InvalidManifestException(sprintf(
                'Protocolo de manifest %d não suportado por este atualizador (suportados: %s).',
                $data['protocol'],
                implode(', ', $this->supportedProtocols())
            ));
        }

        if (!is_int($data['minUpdaterProtocol']) || $data['minUpdaterProtocol'] < 1) {
            throw new InvalidManifestException('O campo "minUpdaterProtocol" deve ser um inteiro >= 1.');
        }

        if (!is_string($data['kind'])) {
            throw new InvalidManifestException('O campo "kind" deve ser uma string.');
        }

        $kind = UpdateKind::tryFrom($data['kind']);
        if ($kind === null) {
            throw new InvalidManifestException(sprintf('Valor inválido para "kind": "%s".', $data['kind']));
        }

        if (!is_string($data['slug']) || trim($data['slug']) === '') {
            throw new InvalidManifestException('O campo "slug" deve ser uma string não vazia.');
        }

        if (!is_string($data['version']) || trim($data['version']) === '') {
            throw new InvalidManifestException('O campo "version" deve ser uma string não vazia.');
        }

        if (!is_array($data['requires'])) {
            throw new InvalidManifestException('O campo "requires" deve ser um objeto.');
        }

        if (
            !array_key_exists('php', $data['requires'])
            || !array_key_exists('extensions', $data['requires'])
            || !array_key_exists('osVersion', $data['requires'])
            || !is_string($data['requires']['php'])
            || !is_array($data['requires']['extensions'])
            || !is_string($data['requires']['osVersion'])
        ) {
            throw new InvalidManifestException('O campo "requires" deve conter "php" (string), "extensions" (lista) e "osVersion" (string).');
        }

        $extensions = [];
        foreach ($data['requires']['extensions'] as $ext) {
            if (!is_string($ext) || trim($ext) === '') {
                throw new InvalidManifestException('Cada item de "requires.extensions" deve ser uma string não vazia.');
            }
            $extensions[] = $ext;
        }

        if (!is_array($data['files'])) {
            throw new InvalidManifestException('O campo "files" deve ser uma lista.');
        }

        $files = [];
        foreach ($data['files'] as $idx => $entry) {
            if (!is_array($entry)) {
                throw new InvalidManifestException(sprintf('Entrada files[%d] deve ser um objeto.', (int)$idx));
            }
            if (
                !array_key_exists('path', $entry)
                || !array_key_exists('sha256', $entry)
                || !array_key_exists('size', $entry)
                || !is_string($entry['path'])
                || !is_string($entry['sha256'])
                || !is_int($entry['size'])
            ) {
                throw new InvalidManifestException(sprintf('Entrada files[%d] não possui path, sha256 ou size válidos.', (int)$idx));
            }
            $mode = $entry['mode'] ?? 'replace';
            if (!is_string($mode) || !in_array($mode, ['replace', 'default_if_missing'], true)) {
                throw new InvalidManifestException(sprintf('Entrada files[%d] possui mode inválido.', (int)$idx));
            }

            $files[] = [
                'path' => $entry['path'],
                'sha256' => strtolower($entry['sha256']),
                'size' => $entry['size'],
                'mode' => $mode,
            ];
        }

        if (!is_array($data['removed'])) {
            throw new InvalidManifestException('O campo "removed" deve ser uma lista.');
        }

        $removed = [];
        foreach ($data['removed'] as $idx => $removedPath) {
            if (!is_string($removedPath) || trim($removedPath) === '') {
                throw new InvalidManifestException(sprintf('Entrada removed[%d] deve ser uma string não vazia.', (int)$idx));
            }
            $removed[] = $removedPath;
        }

        if (!is_array($data['migrations'])) {
            throw new InvalidManifestException('O campo "migrations" deve ser uma lista.');
        }

        $migrations = [];
        foreach ($data['migrations'] as $idx => $mig) {
            if (!is_array($mig)) {
                throw new InvalidManifestException(sprintf('Entrada migrations[%d] deve ser um objeto.', (int)$idx));
            }
            if (
                !array_key_exists('scope', $mig)
                || !array_key_exists('name', $mig)
                || !array_key_exists('sha256', $mig)
                || !array_key_exists('type', $mig)
                || !is_string($mig['scope'])
                || !is_string($mig['name'])
                || !is_string($mig['sha256'])
                || !is_string($mig['type'])
            ) {
                throw new InvalidManifestException(sprintf('Entrada migrations[%d] incompleta ou com tipos inválidos.', (int)$idx));
            }

            $migrations[] = [
                'scope' => $mig['scope'],
                'name' => $mig['name'],
                'sha256' => strtolower($mig['sha256']),
                'type' => $mig['type'],
            ];
        }

        if (!is_array($data['expects'])) {
            throw new InvalidManifestException('O campo "expects" deve ser um objeto mapa de tabela => colunas.');
        }

        $expects = [];
        foreach ($data['expects'] as $table => $columns) {
            if (!is_string($table) || trim($table) === '' || !is_array($columns)) {
                throw new InvalidManifestException('Cada entrada em "expects" deve mapear nome de tabela (string) para lista de colunas.');
            }
            $colList = [];
            foreach ($columns as $col) {
                if (!is_string($col) || trim($col) === '') {
                    throw new InvalidManifestException(sprintf('Coluna inválida em expects["%s"].', $table));
                }
                $colList[] = $col;
            }
            $expects[$table] = $colList;
        }

        try {
            return new UpdateManifest(
                protocol: $data['protocol'],
                minUpdaterProtocol: $data['minUpdaterProtocol'],
                kind: $kind,
                slug: $data['slug'],
                version: $data['version'],
                requires: [
                    'php' => $data['requires']['php'],
                    'extensions' => $extensions,
                    'osVersion' => $data['requires']['osVersion'],
                ],
                files: $files,
                removed: $removed,
                migrations: $migrations,
                expects: $expects,
            );
        } catch (InvalidArgumentException $e) {
            throw new InvalidManifestException($e->getMessage(), 0, $e);
        }
    }
}
