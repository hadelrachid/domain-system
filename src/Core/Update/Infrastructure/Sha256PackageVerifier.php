<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\Update\ProtectedPathsInterface;
use DomainSystem\Core\Contracts\Update\UpdateManifestParserInterface;
use DomainSystem\Core\Contracts\Update\UpdatePackageVerifierInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Exception\InvalidManifestException;
use DomainSystem\Core\Update\Exception\PackageIntegrityException;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Verificador de integridade criptográfica (SHA-256) e estrutural do pacote de atualização.
 */
final class Sha256PackageVerifier implements UpdatePackageVerifierInterface
{
    public function __construct(
        private readonly UpdateManifestParserInterface $manifestParser,
        private readonly ProtectedPathsInterface $protectedPaths,
        private readonly ZipPackageExtractor $extractor,
        private readonly string $quarantineBaseDir,
    ) {
    }

    public function verify(DownloadedPackage $package): VerifiedPackage
    {
        if (!is_file($package->archivePath) || !is_readable($package->archivePath)) {
            throw new PackageIntegrityException(sprintf(
                'Pacote baixado não encontrado para verificação: "%s".',
                $package->archivePath
            ));
        }

        $actualZipSha256 = hash_file('sha256', $package->archivePath);
        if ($actualZipSha256 === false) {
            throw new PackageIntegrityException('Não foi possível calcular o hash SHA-256 do pacote ZIP.');
        }

        if (!hash_equals(strtolower($package->release->sha256), strtolower($actualZipSha256))) {
            throw new PackageIntegrityException(sprintf(
                'O hash SHA-256 do arquivo ZIP (%s) diverge do esperado na Release (%s).',
                $actualZipSha256,
                $package->release->sha256
            ));
        }

        $quarantineDir = rtrim($this->quarantineBaseDir, '/\\')
            . DIRECTORY_SEPARATOR
            . 'quarantine-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $package->release->version)
            . '-' . bin2hex(random_bytes(6));

        $this->extractor->extract($package->archivePath, $quarantineDir);

        try {
            $manifestPath = $quarantineDir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!is_file($manifestPath) || !is_readable($manifestPath)) {
                throw new InvalidManifestException('Arquivo manifest.json ausente na raiz do pacote.');
            }

            $manifestJson = file_get_contents($manifestPath);
            if ($manifestJson === false) {
                throw new InvalidManifestException('Não foi possível ler o arquivo manifest.json.');
            }

            $manifest = $this->manifestParser->parse($manifestJson);

            if ($manifest->version !== $package->release->version) {
                throw new PackageIntegrityException(sprintf(
                    'A versão declarada no manifest.json (%s) diverge da versão da Release (%s).',
                    $manifest->version,
                    $package->release->version
                ));
            }

            $expectedFilesMap = [];
            foreach ($manifest->files as $entry) {
                $relPath = $entry['path'];
                $this->extractor->assertSafeRelativePath($relPath);

                if ($relPath === 'manifest.json') {
                    throw new PackageIntegrityException('O arquivo manifest.json não deve ser listado em "files".');
                }

                if (!$this->isPathWithinAllowedRoots($manifest->kind, $manifest->slug, $relPath, $entry['mode'])) {
                    throw new PackageIntegrityException(sprintf(
                        'Arquivo listado no manifest está fora das raízes permitidas: "%s".',
                        $relPath
                    ));
                }

                if (isset($expectedFilesMap[$relPath])) {
                    throw new PackageIntegrityException(sprintf(
                        'Arquivo duplicado no manifest.json: "%s".',
                        $relPath
                    ));
                }

                $fullPath = $quarantineDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                if (!is_file($fullPath)) {
                    throw new PackageIntegrityException(sprintf(
                        'Arquivo listado no manifest.json está ausente no pacote extraído: "%s".',
                        $relPath
                    ));
                }

                $actualSize = filesize($fullPath);
                if ($actualSize === false || $actualSize !== $entry['size']) {
                    throw new PackageIntegrityException(sprintf(
                        'Tamanho do arquivo "%s" (%s bytes) diverge do manifest (%d bytes).',
                        $relPath,
                        (string)$actualSize,
                        $entry['size']
                    ));
                }

                $actualHash = hash_file('sha256', $fullPath);
                if ($actualHash === false || !hash_equals(strtolower($entry['sha256']), strtolower($actualHash))) {
                    throw new PackageIntegrityException(sprintf(
                        'Hash SHA-256 do arquivo "%s" diverge do declarado no manifest.json.',
                        $relPath
                    ));
                }

                $expectedFilesMap[$relPath] = true;
            }

            foreach ($manifest->removed as $removedPath) {
                $this->extractor->assertSafeRelativePath($removedPath);
                if (!$this->isPathWithinAllowedRoots($manifest->kind, $manifest->slug, $removedPath, 'replace')) {
                    throw new PackageIntegrityException(sprintf(
                        'Caminho em "removed" está fora das raízes permitidas: "%s".',
                        $removedPath
                    ));
                }
            }

            $extractedFiles = $this->listExtractedRelativeFiles($quarantineDir);
            foreach ($extractedFiles as $extractedRelPath) {
                if ($extractedRelPath === 'manifest.json') {
                    continue;
                }
                if (!isset($expectedFilesMap[$extractedRelPath])) {
                    throw new PackageIntegrityException(sprintf(
                        'Arquivo extra não declarado no manifest.json encontrado no pacote: "%s".',
                        $extractedRelPath
                    ));
                }
            }

            return new VerifiedPackage(
                release: $package->release,
                manifest: $manifest,
                extractedPath: $quarantineDir,
            );
        } catch (\Throwable $e) {
            ZipPackageExtractor::removeDirectory($quarantineDir);
            if ($e instanceof PackageIntegrityException || $e instanceof InvalidManifestException) {
                throw $e;
            }
            throw new PackageIntegrityException('Falha ao verificar integridade do pacote: ' . $e->getMessage(), 0, $e);
        }
    }

    private function isPathWithinAllowedRoots(UpdateKind $kind, string $slug, string $relPath, string $mode): bool
    {
        if ($this->protectedPaths instanceof CoreProtectedPaths) {
            return $this->protectedPaths->isAllowedPath($kind, $slug, $relPath, $mode);
        }

        $normalized = ltrim(str_replace('\\', '/', $relPath), '/');
        if ($kind === UpdateKind::Core && $mode === 'default_if_missing' && str_starts_with($normalized, 'config/')) {
            return true;
        }

        foreach ($this->protectedPaths->allowedRoots($kind, $slug) as $root) {
            $normRoot = str_replace('\\', '/', $root);
            if (str_ends_with($normRoot, '/')) {
                if (str_starts_with($normalized, $normRoot) && strlen($normalized) > strlen($normRoot)) {
                    return true;
                }
            } elseif ($normalized === $normRoot) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function listExtractedRelativeFiles(string $quarantineDir): array
    {
        $files = [];
        $baseLen = strlen(rtrim($quarantineDir, '/\\')) + 1;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($quarantineDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if ($item->isFile() || $item->isLink()) {
                $rel = substr($item->getPathname(), $baseLen);
                $files[] = str_replace('\\', '/', $rel);
            }
        }

        sort($files);
        return $files;
    }
}
