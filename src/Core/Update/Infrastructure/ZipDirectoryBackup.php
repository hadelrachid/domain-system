<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\BackupScope;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Backup e restauração de código e configuração em arquivos ZIP (`storage/backups/`).
 *
 * Para `BackupScope(core)`:
 * - Inclui: `src/`, `public/` (exceto `uploads`), `themes/`, `vendor/`, `bin/`, `tools/`,
 *   arquivos da raiz e `config/*.json`.
 * - Exclui: `storage/`, `temp/`, `.git/`, `.env`, `database.sqlite`, `uploads/`, `public/uploads/`.
 */
final class ZipDirectoryBackup implements BackupInterface
{
    private const CORE_INCLUDED_DIRS = [
        'src',
        'public',
        'themes',
        'vendor',
        'bin',
        'tools',
        'release',
    ];

    private const EXCLUDED_PREFIXES = [
        'storage/',
        'temp/',
        '.git/',
        'uploads/',
        'public/uploads/',
    ];

    private const EXCLUDED_EXACT = [
        '.env',
        'database.sqlite',
        '.git',
        '.DS_Store',
        'Thumbs.db',
    ];

    private string $rootPath;
    private string $backupsBaseDir;
    private JsonStoreInterface $jsonStore;
    private ZipPackageExtractor $extractor;

    public function __construct(
        string $rootPath,
        ?string $backupsBaseDir = null,
        ?JsonStoreInterface $jsonStore = null,
        ?ZipPackageExtractor $extractor = null,
    ) {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->backupsBaseDir = $backupsBaseDir !== null
            ? rtrim(str_replace('\\', '/', $backupsBaseDir), '/')
            : $this->rootPath . '/storage/backups';
        $this->jsonStore = $jsonStore ?? new AtomicJsonStore();
        $this->extractor = $extractor ?? new ZipPackageExtractor();
    }

    public function create(BackupScope $scope, string $label): BackupRecord
    {
        $this->ensureBackupsRootProtected();

        $id = gmdate('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $scopeDir = $this->resolveScopeDirectory($scope) . '/' . $id;

        if (!is_dir($scopeDir) && !@mkdir($scopeDir, 0755, true) && !is_dir($scopeDir)) {
            throw new RuntimeException(sprintf('Não foi possível criar diretório de backup: "%s".', $scopeDir));
        }

        $zipPath = $scopeDir . '/code.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(sprintf('Não foi possível criar arquivo ZIP de backup: "%s".', $zipPath));
        }

        $filesToBackup = $scope->kind === UpdateKind::Core
            ? $this->collectCoreFiles()
            : $this->collectPluginFiles($scope->slug);

        foreach ($filesToBackup as $relPath) {
            $fullPath = $this->rootPath . '/' . $relPath;
            if (is_file($fullPath) && !is_link($fullPath)) {
                $zip->addFile($fullPath, $relPath);
            }
        }

        // Garante que o arquivo ZIP seja gravado mesmo se a árvore tiver apenas marcador vazio
        if ($filesToBackup === []) {
            $zip->addFromString('.ds_empty_backup', '');
        }

        if (!$zip->close()) {
            throw new RuntimeException('Falha ao finalizar gravação do arquivo code.zip no backup.');
        }

        $record = new BackupRecord(
            id: $id,
            scope: $scope,
            label: $label,
            createdAt: gmdate('c'),
            codePath: $zipPath,
            dumpPath: null,
        );

        $this->jsonStore->write($scopeDir . '/meta.json', $record->toArray());

        return $record;
    }

    /**
     * Associa um caminho de dump de banco de dados ao registro de backup e atualiza `meta.json`.
     */
    public function attachDumpPath(BackupRecord $record, string $dumpPath): BackupRecord
    {
        $updated = new BackupRecord(
            id: $record->id,
            scope: $record->scope,
            label: $record->label,
            createdAt: $record->createdAt,
            codePath: $record->codePath,
            dumpPath: $dumpPath,
        );

        $metaPath = dirname($record->codePath) . '/meta.json';
        $this->jsonStore->write($metaPath, $updated->toArray());

        return $updated;
    }

    public function restore(BackupRecord $record): void
    {
        if (!is_file($record->codePath) || !is_readable($record->codePath)) {
            throw new RollbackFailedException(sprintf(
                'Arquivo de backup não encontrado ou ilegível: "%s".',
                $record->codePath
            ));
        }

        $stagingDir = $this->backupsBaseDir . '/.restore-staging-' . bin2hex(random_bytes(4));

        try {
            $this->extractor->extract($record->codePath, $stagingDir);

            $marker = $stagingDir . '/.ds_empty_backup';
            if (is_file($marker)) {
                @unlink($marker);
            }

            $baseLen = strlen($stagingDir) + 1;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($stagingDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $item) {
                if (!$item->isFile() || $item->isLink()) {
                    continue;
                }

                $relPath = str_replace('\\', '/', substr($item->getPathname(), $baseLen));
                $this->extractor->assertSafeRelativePath($relPath);

                if ($this->isExcludedRelativePath($relPath)) {
                    continue;
                }

                $destPath = $this->rootPath . '/' . $relPath;
                $destDir = dirname($destPath);
                if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                    throw new RollbackFailedException(sprintf(
                        'Não foi possível criar diretório "%s" durante a restauração.',
                        $destDir
                    ));
                }

                $tmpPath = $destDir . '/.' . basename($destPath) . '.dsrestore.' . bin2hex(random_bytes(3));
                if (!@copy($item->getPathname(), $tmpPath)) {
                    throw new RollbackFailedException(sprintf(
                        'Falha ao copiar arquivo temporário para "%s" durante a restauração.',
                        $relPath
                    ));
                }

                if (DIRECTORY_SEPARATOR === '\\' && is_file($destPath)) {
                    @unlink($destPath);
                }

                if (!@rename($tmpPath, $destPath)) {
                    @unlink($tmpPath);
                    throw new RollbackFailedException(sprintf(
                        'Falha ao substituir "%s" durante a restauração.',
                        $relPath
                    ));
                }
            }
        } catch (Throwable $e) {
            if ($e instanceof RollbackFailedException) {
                throw $e;
            }
            throw new RollbackFailedException('Erro ao restaurar backup de código: ' . $e->getMessage(), 0, $e);
        } finally {
            ZipPackageExtractor::removeDirectory($stagingDir);
        }
    }

    public function list(?BackupScope $scope = null): array
    {
        if (!is_dir($this->backupsBaseDir)) {
            return [];
        }

        $scopeDirs = [];
        if ($scope !== null) {
            $dir = $this->resolveScopeDirectory($scope);
            if (is_dir($dir)) {
                $scopeDirs[] = $dir;
            }
        } else {
            $coreDir = $this->backupsBaseDir . '/core';
            if (is_dir($coreDir)) {
                $scopeDirs[] = $coreDir;
            }
            $pluginsBase = $this->backupsBaseDir . '/plugins';
            if (is_dir($pluginsBase)) {
                foreach (scandir($pluginsBase) ?: [] as $slug) {
                    if ($slug === '.' || $slug === '..') {
                        continue;
                    }
                    $pDir = $pluginsBase . '/' . $slug;
                    if (is_dir($pDir)) {
                        $scopeDirs[] = $pDir;
                    }
                }
            }
        }

        $records = [];
        foreach ($scopeDirs as $sDir) {
            foreach (scandir($sDir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $metaPath = $sDir . '/' . $entry . '/meta.json';
                if (!is_file($metaPath)) {
                    continue;
                }
                $data = $this->jsonStore->read($metaPath, []);
                if ($data === []) {
                    continue;
                }
                try {
                    $records[] = BackupRecord::fromArray($data);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        usort(
            $records,
            static function (BackupRecord $a, BackupRecord $b): int {
                $cmp = strcmp($b->createdAt, $a->createdAt);
                return $cmp !== 0 ? $cmp : strcmp($b->id, $a->id);
            }
        );

        return $records;
    }

    public function prune(BackupScope $scope, int $keep): int
    {
        $keep = max(0, $keep);
        $records = $this->list($scope);

        if (count($records) <= $keep) {
            return 0;
        }

        $toRemove = array_slice($records, $keep);
        $removedCount = 0;

        foreach ($toRemove as $record) {
            $backupDir = dirname($record->codePath);
            if (is_dir($backupDir)) {
                ZipPackageExtractor::removeDirectory($backupDir);
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * @return list<string>
     */
    private function collectCoreFiles(): array
    {
        $files = [];

        // 1. Arquivos soltos na raiz
        foreach (scandir($this->rootPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $this->rootPath . '/' . $entry;
            if (is_file($full) && !is_link($full) && !$this->isExcludedRelativePath($entry)) {
                $files[] = $entry;
            }
        }

        // 2. config/*.json
        $configDir = $this->rootPath . '/config';
        if (is_dir($configDir)) {
            foreach (glob($configDir . '/*.json') ?: [] as $jsonFile) {
                if (is_file($jsonFile) && !is_link($jsonFile)) {
                    $files[] = 'config/' . basename($jsonFile);
                }
            }
        }

        // 3. Diretórios do núcleo
        foreach (self::CORE_INCLUDED_DIRS as $dirName) {
            $dirFull = $this->rootPath . '/' . $dirName;
            if (!is_dir($dirFull)) {
                continue;
            }

            $baseLen = strlen($this->rootPath) + 1;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirFull, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $item) {
                if (!$item->isFile() || $item->isLink()) {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($item->getPathname(), $baseLen));
                if (!$this->isExcludedRelativePath($rel)) {
                    $files[] = $rel;
                }
            }
        }

        sort($files);
        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    private function collectPluginFiles(string $slug): array
    {
        $cleanSlug = trim($slug, '/\\');
        $pluginDir = $this->rootPath . '/src/Plugins/' . $cleanSlug;
        if (!is_dir($pluginDir)) {
            return [];
        }

        $files = [];
        $baseLen = strlen($this->rootPath) + 1;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if (!$item->isFile() || $item->isLink()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($item->getPathname(), $baseLen));
            if (!$this->isExcludedRelativePath($rel)) {
                $files[] = $rel;
            }
        }

        sort($files);
        return $files;
    }

    private function isExcludedRelativePath(string $relPath): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $relPath), '/');
        if ($normalized === '') {
            return true;
        }

        $basename = basename($normalized);
        if (in_array($normalized, self::EXCLUDED_EXACT, true) || in_array($basename, ['.DS_Store', 'Thumbs.db'], true)) {
            return true;
        }

        if (str_ends_with($normalized, '.phar') || str_ends_with($normalized, '.sqlite')) {
            return true;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolveScopeDirectory(BackupScope $scope): string
    {
        if ($scope->kind === UpdateKind::Core) {
            return $this->backupsBaseDir . '/core';
        }

        return $this->backupsBaseDir . '/plugins/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $scope->slug);
    }

    private function ensureBackupsRootProtected(): void
    {
        if (!is_dir($this->backupsBaseDir)) {
            @mkdir($this->backupsBaseDir, 0755, true);
        }

        $htaccess = $this->backupsBaseDir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents(
                $htaccess,
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
            );
        }
    }
}
