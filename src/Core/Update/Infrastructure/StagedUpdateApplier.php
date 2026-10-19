<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\Update\BackupInterface;
use DomainSystem\Core\Contracts\Update\ProtectedPathsInterface;
use DomainSystem\Core\Contracts\Update\UpdateApplierInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\ApplyException;
use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use DomainSystem\Core\Update\Exception\RollbackFailedException;
use DomainSystem\Core\Update\Value\ApplyResult;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use Throwable;
use ZipArchive;

/**
 * Aplicador em estágios dos arquivos do pacote de atualização sobre a árvore instalada.
 *
 * Ordem de escrita:
 *   1. `vendor/`
 *   2. `src/`
 *   3. `themes/`
 *   4. demais arquivos permitidos
 *   5. `public/index.php` e `bootstrap.php` (por último)
 */
final class StagedUpdateApplier implements UpdateApplierInterface
{
    private string $rootPath;
    private ProtectedPathsInterface $protectedPaths;
    private BackupInterface $backup;
    private ZipPackageExtractor $pathValidator;
    /** @var list<string> */
    private array $lastWritten = [];

    public function __construct(
        string $rootPath,
        ProtectedPathsInterface $protectedPaths,
        BackupInterface $backup,
        ?ZipPackageExtractor $pathValidator = null,
    ) {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->protectedPaths = $protectedPaths;
        $this->backup = $backup;
        $this->pathValidator = $pathValidator ?? new ZipPackageExtractor();
    }

    /**
     * @return list<string>
     */
    public function lastWrittenFiles(): array
    {
        return $this->lastWritten;
    }

    public function apply(VerifiedPackage $package, UpdateTarget $target, BackupRecord $backup): ApplyResult
    {
        $this->lastWritten = [];
        $extractedBase = rtrim(str_replace('\\', '/', $package->extractedPath), '/');
        $sortedFiles = $this->sortManifestFiles($package->manifest->files);

        // Pré-validação completa de todos os caminhos em files[] e removed[] (defesa em profundidade)
        foreach ($sortedFiles as $entry) {
            $relPath = $entry['path'];
            $mode = $entry['mode'] ?? 'replace';
            $this->assertValidAndAllowedPath($target->kind, $target->slug, $relPath, $mode);
        }

        foreach ($package->manifest->removed as $removedPath) {
            $this->assertValidAndAllowedPath($target->kind, $target->slug, $removedPath, 'replace');
            if ($this->isProtected($target->kind, $target->slug, $removedPath)) {
                throw new ApplyException(sprintf(
                    'Tentativa bloqueada de remover caminho protegido: "%s".',
                    $removedPath
                ));
            }
        }

        $written = [];
        $removed = [];
        $skippedProtected = [];

        foreach ($sortedFiles as $entry) {
            $relPath = ltrim(str_replace('\\', '/', $entry['path']), '/');
            $mode = $entry['mode'] ?? 'replace';
            $expectedSha = strtolower($entry['sha256']);

            $sourcePath = $extractedBase . '/' . $relPath;
            $destPath = $this->rootPath . '/' . $relPath;

            if (!is_file($sourcePath) || !is_readable($sourcePath)) {
                throw new ApplyException(sprintf(
                    'Arquivo de origem ausente na quarentena durante aplicação: "%s".',
                    $relPath
                ));
            }

            $isProtected = $this->isProtected($target->kind, $target->slug, $relPath);

            if ($isProtected) {
                if ($mode === 'default_if_missing') {
                    if (file_exists($destPath)) {
                        $skippedProtected[] = $relPath;
                        continue;
                    }
                } else {
                    $skippedProtected[] = $relPath;
                    continue;
                }
            } elseif ($mode === 'default_if_missing' && file_exists($destPath)) {
                $skippedProtected[] = $relPath;
                continue;
            }

            // Pula arquivo se já existir em disco com o mesmo hash SHA-256
            if (is_file($destPath)) {
                $currentHash = @hash_file('sha256', $destPath);
                if ($currentHash !== false && hash_equals($expectedSha, strtolower($currentHash))) {
                    continue;
                }
            }

            $parentDir = dirname($destPath);
            if (file_exists($parentDir) && !is_dir($parentDir)) {
                throw new ApplyException(sprintf(
                    'O caminho pai de "%s" é um arquivo existente e impede a criação do diretório.',
                    $relPath
                ));
            }

            if (!is_dir($parentDir) && !@mkdir($parentDir, 0755, true) && !is_dir($parentDir)) {
                throw new ApplyException(sprintf(
                    'Não foi possível criar diretório de destino para "%s".',
                    $relPath
                ));
            }

            $permissions = $this->resolvePermissions($relPath, $destPath);
            $tmpFile = $parentDir . '/.' . basename($destPath) . '.dsnew';

            if (!@copy($sourcePath, $tmpFile)) {
                throw new ApplyException(sprintf(
                    'Falha ao gravar arquivo temporário ".dsnew" para "%s".',
                    $relPath
                ));
            }

            @chmod($tmpFile, $permissions);

            if (DIRECTORY_SEPARATOR === '\\' && is_file($destPath)) {
                if (!@unlink($destPath)) {
                    @unlink($tmpFile);
                    throw new ApplyException(sprintf(
                        'Não foi possível remover arquivo anterior antes da troca em "%s".',
                        $relPath
                    ));
                }
            }

            if (!@rename($tmpFile, $destPath)) {
                @unlink($tmpFile);
                throw new ApplyException(sprintf(
                    'Falha ao renomear arquivo temporário para "%s".',
                    $relPath
                ));
            }

            @chmod($destPath, $permissions);
            $written[] = $relPath;
            $this->lastWritten[] = $relPath;
        }

        foreach ($package->manifest->removed as $removedRaw) {
            $relPath = ltrim(str_replace('\\', '/', $removedRaw), '/');
            $destPath = $this->rootPath . '/' . $relPath;

            if (is_dir($destPath)) {
                throw new ApplyException(sprintf(
                    'Remoção de diretório inteiro em "removed" não permitida: "%s".',
                    $relPath
                ));
            }

            if (is_file($destPath)) {
                if (!@unlink($destPath)) {
                    throw new ApplyException(sprintf(
                        'Falha ao remover arquivo obsoleto "%s".',
                        $relPath
                    ));
                }
                $removed[] = $relPath;
            }
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        return new ApplyResult(
            backup: $backup,
            written: $written,
            removed: $removed,
            skippedProtected: $skippedProtected,
        );
    }

    public function revert(ApplyResult $result): void
    {
        try {
            $backupFilesMap = $this->readBackupFileSet($result->backup->codePath);

            $this->backup->restore($result->backup);

            $candidatesToClean = array_values(array_unique(array_merge($result->written, $this->lastWritten)));
            foreach ($candidatesToClean as $writtenRel) {
                $normalized = ltrim(str_replace('\\', '/', $writtenRel), '/');
                if (!isset($backupFilesMap[$normalized])) {
                    $fullPath = $this->rootPath . '/' . $normalized;
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } catch (Throwable $e) {
            if ($e instanceof RollbackFailedException) {
                throw $e;
            }
            throw new RollbackFailedException('Falha ao reverter aplicação do pacote: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param list<array{path: string, sha256: string, size: int, mode: string}> $files
     * @return list<array{path: string, sha256: string, size: int, mode: string}>
     */
    private function sortManifestFiles(array $files): array
    {
        usort($files, function (array $a, array $b): int {
            $prioA = $this->stagePriority($a['path']);
            $prioB = $this->stagePriority($b['path']);
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }
            return strcmp($a['path'], $b['path']);
        });

        return $files;
    }

    private function stagePriority(string $relPath): int
    {
        $normalized = ltrim(str_replace('\\', '/', $relPath), '/');

        if ($normalized === 'public/index.php') {
            return 40;
        }
        if ($normalized === 'bootstrap.php') {
            return 41;
        }
        if (str_starts_with($normalized, 'vendor/')) {
            return 10;
        }
        if (str_starts_with($normalized, 'src/')) {
            return 20;
        }
        if (str_starts_with($normalized, 'themes/')) {
            return 30;
        }

        return 35;
    }

    private function assertValidAndAllowedPath(UpdateKind $kind, string $slug, string $relPath, string $mode): void
    {
        try {
            $this->pathValidator->assertSafeRelativePath($relPath);
        } catch (PackageIntegrityException $e) {
            throw new ApplyException($e->getMessage(), 0, $e);
        }

        $allowed = false;
        if ($this->protectedPaths instanceof CoreProtectedPaths) {
            $allowed = $this->protectedPaths->isAllowedPath($kind, $slug, $relPath, $mode);
        } else {
            $normalized = ltrim(str_replace('\\', '/', $relPath), '/');
            if ($kind === UpdateKind::Core && $mode === 'default_if_missing' && str_starts_with($normalized, 'config/')) {
                $allowed = true;
            } else {
                foreach ($this->protectedPaths->allowedRoots($kind, $slug) as $root) {
                    $normRoot = str_replace('\\', '/', $root);
                    if (str_ends_with($normRoot, '/')) {
                        if (str_starts_with($normalized, $normRoot) && strlen($normalized) > strlen($normRoot)) {
                            $allowed = true;
                            break;
                        }
                    } elseif ($normalized === $normRoot) {
                        $allowed = true;
                        break;
                    }
                }
            }
        }

        if (!$allowed) {
            throw new ApplyException(sprintf(
                'Caminho fora das raízes permitidas na aplicação: "%s".',
                $relPath
            ));
        }
    }

    private function isProtected(UpdateKind $kind, string $slug, string $relPath): bool
    {
        if ($this->protectedPaths instanceof CoreProtectedPaths) {
            return $this->protectedPaths->isProtectedPath($kind, $slug, $relPath);
        }

        $normalized = ltrim(str_replace('\\', '/', $relPath), '/');
        foreach ($this->protectedPaths->protectedPaths($kind, $slug) as $protected) {
            $normProt = str_replace('\\', '/', $protected);
            if (str_ends_with($normProt, '/')) {
                $prefix = rtrim($normProt, '/');
                if ($normalized === $prefix || str_starts_with($normalized, $normProt)) {
                    return true;
                }
            } elseif ($normalized === $normProt || str_starts_with($normalized, $normProt . '/')) {
                return true;
            }
        }

        return false;
    }

    private function resolvePermissions(string $relPath, string $destPath): int
    {
        if (is_file($destPath)) {
            $perms = @fileperms($destPath);
            if ($perms !== false) {
                return $perms & 0777;
            }
        }

        if ($relPath === 'os-cli.php' || str_starts_with($relPath, 'bin/')) {
            return 0755;
        }

        return 0644;
    }

    /**
     * @return array<string, bool>
     */
    private function readBackupFileSet(string $zipPath): array
    {
        $map = [];
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::RDONLY) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && !str_ends_with($name, '/')) {
                    $map[ltrim(str_replace('\\', '/', $name), '/')] = true;
                }
            }
            $zip->close();
        }
        return $map;
    }
}
