<?php

declare(strict_types=1);

namespace DomainSystem\Tools\Release;

use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Infrastructure\CoreProtectedPaths;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Empacotador determinístico de Releases oficiais do Domain System (Modelo A).
 *
 * Gera `dist/domain-system-X.Y.Z.zip` (com `manifest.json` assinado por SHA-256)
 * e `dist/domain-system-X.Y.Z.zip.sha256`.
 */
final class ReleaseBuilder
{
    private string $rootDir;
    private CoreProtectedPaths $protectedPaths;

    public function __construct(string $rootDir, ?CoreProtectedPaths $protectedPaths = null)
    {
        $this->rootDir = rtrim(str_replace('\\', '/', $rootDir), '/');
        $this->protectedPaths = $protectedPaths ?? new CoreProtectedPaths();
    }

    /**
     * @param list<string> $removedPaths Lista opcional de caminhos obsoletos a declarar em `removed`.
     * @return array{
     *   version: string,
     *   zipPath: string,
     *   sha256Path: string,
     *   sha256: string,
     *   fileCount: int,
     *   migrationCount: int,
     *   manifest: array<string, mixed>
     * }
     */
    public function build(?string $outputDir = null, array $removedPaths = []): array
    {
        $autoloadPath = $this->rootDir . '/vendor/autoload.php';
        if (!is_file($autoloadPath)) {
            throw new RuntimeException(
                'vendor/autoload.php não encontrado. Execute "composer install --no-dev --optimize-autoloader" antes de empacotar a Release.'
            );
        }

        $version = $this->extractConstantFromBootstrap('DS_VERSION');
        $osVersion = $this->extractConstantFromBootstrap('DS_OS_VERSION', '2.3');
        $excludePatterns = $this->loadExcludePatterns();

        $candidateFiles = $this->collectCandidateFiles($excludePatterns);
        $filesManifest = [];
        $seenLower = [];

        foreach ($candidateFiles as $relPath) {
            $lower = strtolower($relPath);
            if (isset($seenLower[$lower])) {
                continue;
            }

            $mode = (str_starts_with($relPath, 'config/') && str_ends_with($relPath, '.json'))
                ? 'default_if_missing'
                : 'replace';

            if (!$this->protectedPaths->isAllowedPath(UpdateKind::Core, 'core', $relPath, $mode)) {
                continue;
            }
            if ($mode === 'replace' && $this->protectedPaths->isProtectedPath(UpdateKind::Core, 'core', $relPath)) {
                continue;
            }

            $absPath = $this->rootDir . '/' . $relPath;
            if (!is_file($absPath) || is_link($absPath)) {
                continue;
            }

            $size = filesize($absPath);
            $sha256 = hash_file('sha256', $absPath);
            if ($size === false || $sha256 === false) {
                throw new RuntimeException(sprintf('Não foi possível calcular tamanho/hash de "%s".', $relPath));
            }

            $seenLower[$lower] = true;
            $filesManifest[] = [
                'path'   => $relPath,
                'sha256' => strtolower($sha256),
                'size'   => $size,
                'mode'   => $mode,
            ];
        }

        if ($filesManifest === []) {
            throw new RuntimeException('Nenhum arquivo elegível encontrado para empacotamento da Release.');
        }

        $migrationsManifest = $this->collectMigrations($seenLower);
        $expects = $this->loadSchemaExpects();

        $manifestData = [
            'protocol'           => 1,
            'minUpdaterProtocol' => 1,
            'kind'               => 'core',
            'slug'               => 'core',
            'version'            => $version,
            'requires'           => [
                'php'        => '8.1',
                'extensions' => ['pdo', 'zip', 'mbstring', 'openssl', 'json', 'curl'],
                'osVersion'  => $osVersion,
            ],
            'files'              => $filesManifest,
            'removed'            => array_values($removedPaths),
            'migrations'         => $migrationsManifest,
            'expects'            => $expects,
        ];

        $distDir = rtrim(str_replace('\\', '/', $outputDir ?? ($this->rootDir . '/dist')), '/');
        if (!is_dir($distDir) && !@mkdir($distDir, 0775, true) && !is_dir($distDir)) {
            throw new RuntimeException(sprintf('Não foi possível criar diretório de saída "%s".', $distDir));
        }

        $zipFilename = sprintf('domain-system-%s.zip', $version);
        $zipPath = $distDir . '/' . $zipFilename;
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(sprintf('Falha ao criar arquivo ZIP "%s".', $zipPath));
        }

        foreach ($filesManifest as $fileEntry) {
            $rel = $fileEntry['path'];
            $abs = $this->rootDir . '/' . $rel;
            if (!$zip->addFile($abs, $rel)) {
                $zip->close();
                throw new RuntimeException(sprintf('Falha ao adicionar "%s" ao pacote ZIP.', $rel));
            }
        }

        $manifestJson = json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($manifestJson === false || !$zip->addFromString('manifest.json', $manifestJson . "\n")) {
            $zip->close();
            throw new RuntimeException('Falha ao gravar manifest.json dentro do pacote ZIP.');
        }

        if (!$zip->close()) {
            throw new RuntimeException('Falha ao finalizar arquivo ZIP da Release.');
        }

        $zipSha256 = hash_file('sha256', $zipPath);
        if ($zipSha256 === false) {
            throw new RuntimeException('Não foi possível calcular SHA-256 do pacote ZIP gerado.');
        }
        $zipSha256 = strtolower($zipSha256);

        $sha256Path = $zipPath . '.sha256';
        file_put_contents($sha256Path, sprintf("%s  %s\n", $zipSha256, $zipFilename));

        return [
            'version'        => $version,
            'zipPath'        => $zipPath,
            'sha256Path'     => $sha256Path,
            'sha256'         => $zipSha256,
            'fileCount'      => count($filesManifest),
            'migrationCount' => count($migrationsManifest),
            'manifest'       => $manifestData,
        ];
    }

    private function extractConstantFromBootstrap(string $constantName, ?string $default = null): string
    {
        $bootstrapPath = $this->rootDir . '/bootstrap.php';
        if (!is_file($bootstrapPath)) {
            if ($default !== null) {
                return $default;
            }
            throw new RuntimeException('Arquivo bootstrap.php não encontrado na raiz informada.');
        }

        $contents = (string)file_get_contents($bootstrapPath);
        $pattern = sprintf("/define\(\s*'%s'\s*,\s*'([^']+)'\s*\)/", preg_quote($constantName, '/'));
        if (preg_match($pattern, $contents, $matches) === 1) {
            return trim($matches[1]);
        }

        if ($default !== null) {
            return $default;
        }

        throw new RuntimeException(sprintf('Constante %s não encontrada em bootstrap.php.', $constantName));
    }

    /**
     * @return list<string>
     */
    private function loadExcludePatterns(): array
    {
        $excludeFile = $this->rootDir . '/release/exclude.txt';
        if (!is_file($excludeFile)) {
            return [
                '.git*',
                '.github/',
                'tests/',
                'docs/',
                'wiki/',
                'auditoria/',
                'scratch/',
                'token/',
                'storage/',
                'temp/',
                'dist/',
                '*.phar',
                '*.zip',
                '.env',
                'database.sqlite',
            ];
        }

        $lines = file($excludeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $patterns = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            $patterns[] = $trimmed;
        }

        return $patterns;
    }

    /**
     * @param list<string> $excludePatterns
     * @return list<string>
     */
    private function collectCandidateFiles(array $excludePatterns): array
    {
        $all = $this->scanDirectoryRecursive($this->rootDir, $excludePatterns);
        sort($all, SORT_STRING);
        return array_values(array_unique($all));
    }

    /**
     * @param list<string> $excludePatterns
     * @return list<string>
     */
    private function scanDirectoryRecursive(string $baseDir, array $excludePatterns): array
    {
        $results = [];
        $baseLen = strlen($baseDir) + 1;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if ($item->isLink() || !$item->isFile()) {
                continue;
            }

            $fullPath = str_replace('\\', '/', $item->getPathname());
            $relPath = substr($fullPath, $baseLen);
            if ($relPath === '' || $this->isExcluded($relPath, $excludePatterns)) {
                continue;
            }

            $results[] = $relPath;
        }

        return $results;
    }

    /**
     * @param list<string> $excludePatterns
     */
    private function isExcluded(string $relPath, array $excludePatterns): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $relPath), '/');
        $basename = basename($normalized);

        foreach ($excludePatterns as $pattern) {
            $normPattern = ltrim(str_replace('\\', '/', $pattern), '/');
            if ($normPattern === '') {
                continue;
            }

            if (str_ends_with($normPattern, '/')) {
                $prefix = rtrim($normPattern, '/');
                if ($normalized === $prefix || str_starts_with($normalized, $normPattern)) {
                    return true;
                }
                continue;
            }

            if (fnmatch($normPattern, $normalized) || fnmatch($normPattern, $basename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool> $includedFilesLower
     * @return list<array{scope: string, name: string, sha256: string, type: string}>
     */
    private function collectMigrations(array $includedFilesLower): array
    {
        $migrations = [];

        $coreMigDir = $this->rootDir . '/src/Core/Database/migrations';
        if (is_dir($coreMigDir)) {
            $files = glob($coreMigDir . '/*.php') ?: [];
            sort($files, SORT_STRING);
            foreach ($files as $file) {
                $relPath = 'src/Core/Database/migrations/' . basename($file);
                if (!isset($includedFilesLower[strtolower($relPath)])) {
                    continue;
                }
                $hash = hash_file('sha256', $file);
                if ($hash !== false) {
                    $migrations[] = [
                        'scope'  => '__core__',
                        'name'   => basename($file, '.php'),
                        'sha256' => strtolower($hash),
                        'type'   => 'additive',
                    ];
                }
            }
        }

        $pluginMigDirs = glob($this->rootDir . '/src/Plugins/*/migrations') ?: [];
        sort($pluginMigDirs, SORT_STRING);
        foreach ($pluginMigDirs as $migDir) {
            $slug = basename(dirname($migDir));
            $files = glob($migDir . '/*.php') ?: [];
            sort($files, SORT_STRING);
            foreach ($files as $file) {
                $relPath = 'src/Plugins/' . $slug . '/migrations/' . basename($file);
                if (!isset($includedFilesLower[strtolower($relPath)])) {
                    continue;
                }
                $hash = hash_file('sha256', $file);
                if ($hash !== false) {
                    $migrations[] = [
                        'scope'  => $slug,
                        'name'   => basename($file, '.php'),
                        'sha256' => strtolower($hash),
                        'type'   => 'additive',
                    ];
                }
            }
        }

        return $migrations;
    }

    /**
     * @return array<string, list<string>>
     */
    private function loadSchemaExpects(): array
    {
        $expectsFile = $this->rootDir . '/release/schema-expects.json';
        if (!is_file($expectsFile)) {
            return [];
        }

        $decoded = json_decode((string)file_get_contents($expectsFile), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('release/schema-expects.json contém JSON inválido.');
        }

        $result = [];
        foreach ($decoded as $table => $columns) {
            if (is_string($table) && is_array($columns)) {
                $result[$table] = array_values(array_map('strval', $columns));
            }
        }

        return $result;
    }
}
