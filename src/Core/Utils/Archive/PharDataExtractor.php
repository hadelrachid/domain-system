<?php

declare(strict_types=1);

namespace DomainSystem\Core\Utils\Archive;

use Exception;
use PharData;
use RecursiveIteratorIterator;

class PharDataExtractor implements ExtractorInterface
{
    public function extract(string $archivePath, string $destinationPath, string $descriptorFile = 'plugin.json'): string
    {
        $phar = new PharData($archivePath);
        $archiveRootPrefix = 'phar://' . str_replace('\\', '/', $archivePath) . '/';

        // 1. Valida recursivamente todos os caminhos internos do arquivo
        $iterator = new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entry) {
            $fullPath = str_replace('\\', '/', $entry->getPathname());
            $relative = str_starts_with($fullPath, $archiveRootPrefix)
                ? substr($fullPath, strlen($archiveRootPrefix))
                : $entry->getFilename();

            if (
                $relative === ''
                || strpos($relative, "\0") !== false
                || str_starts_with($relative, '/')
                || str_starts_with($relative, '\\')
                || preg_match('/^[a-zA-Z]:|^[a-zA-Z0-9+.-]+:\/\//', $relative) === 1
                || strpos($relative, '../') !== false
                || strpos($relative, '..\\') !== false
            ) {
                throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Caminho inválido detectado no pacote.");
            }

            foreach (explode('/', rtrim($relative, '/')) as $segment) {
                if ($segment === '' || $segment === '.' || $segment === '..') {
                    throw new Exception("Vulnerabilidade Crítica (Zip-Slip): Segmento inválido detectado no pacote.");
                }
            }
        }

        $hasDescriptor = false;
        $componentDirName = null;

        foreach ($phar as $file) {
            if ($file->isDir()) {
                $dirName = $file->getFilename();
                if (
                    preg_match('/^[a-zA-Z0-9_-]+$/', $dirName) === 1
                    && isset($phar[$dirName . '/' . $descriptorFile])
                ) {
                    $hasDescriptor = true;
                    $componentDirName = $dirName;
                    break;
                }
            }
        }

        if (!$hasDescriptor && isset($phar[$descriptorFile])) {
            $json = file_get_contents($phar[$descriptorFile]->getPathname());
            $data = is_string($json) ? json_decode($json, true) : null;
            if (is_array($data) && isset($data['name']) && is_string($data['name'])) {
                $hasDescriptor = true;
                $componentDirName = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['name'])), '-');

                if ($componentDirName === '') {
                    throw new Exception("Nome de componente inválido no manifesto ($descriptorFile).");
                }

                @mkdir($destinationPath . '/' . $componentDirName, 0755, true);
                $phar->extractTo($destinationPath . '/' . $componentDirName, null, true);
                return $componentDirName;
            }
        }

        if ($hasDescriptor && $componentDirName) {
            $phar->extractTo($destinationPath, null, true);
            return $componentDirName;
        }

        throw new Exception("ZIP inválido: Não possui um arquivo $descriptorFile válido no pacote.");
    }
}
