<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Support;

use DomainSystem\Core\Update\Enum\UpdateKind;
use DomainSystem\Core\Update\Value\DownloadedPackage;
use DomainSystem\Core\Update\Value\ReleaseInfo;
use RuntimeException;
use ZipArchive;

/**
 * Utilitário de testes para construir pacotes ZIP + manifest.json em diretório temporário.
 */
final class PackageBuilder
{
    private int $protocol = 1;
    private int $minUpdaterProtocol = 1;
    private UpdateKind $kind = UpdateKind::Core;
    private string $slug = 'core';
    private string $version = '2.4.0';

    /** @var array{php: string, extensions: list<string>, osVersion: string} */
    private array $requires = [
        'php' => '8.1',
        'extensions' => ['pdo', 'zip', 'mbstring', 'openssl', 'json', 'curl'],
        'osVersion' => '2.3',
    ];

    /** @var list<array{path: string, content: string, mode: string, sha256: string, size: int}> */
    private array $files = [];

    /** @var list<array{path: string, content: string}> */
    private array $extraZipFiles = [];

    /** @var list<array{path: string, sha256: string, size: int, mode: string}> */
    private array $missingDiskFiles = [];

    /** @var list<array{entryName: string, target: string}> */
    private array $symlinks = [];

    /** @var list<string> */
    private array $removed = [];

    /** @var list<array{scope: string, name: string, sha256: string, type: string}> */
    private array $migrations = [];

    /** @var array<string, list<string>> */
    private array $expects = [];

    /** @var array<string, mixed>|string|null */
    private array|string|null $rawManifestOverride = null;

    private bool $omitManifestFile = false;

    public static function create(): self
    {
        return new self();
    }

    public function withProtocol(int $protocol, int $minUpdaterProtocol = 1): self
    {
        $this->protocol = $protocol;
        $this->minUpdaterProtocol = $minUpdaterProtocol;
        return $this;
    }

    public function withKind(UpdateKind $kind, string $slug = 'core'): self
    {
        $this->kind = $kind;
        $this->slug = $slug;
        return $this;
    }

    public function withVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param array{php: string, extensions: list<string>, osVersion: string} $requires
     */
    public function withRequires(array $requires): self
    {
        $this->requires = $requires;
        return $this;
    }

    public function withFile(
        string $path,
        string $content,
        string $mode = 'replace',
        ?string $overrideSha256 = null,
        ?int $overrideSize = null,
    ): self {
        $this->files[] = [
            'path' => $path,
            'content' => $content,
            'mode' => $mode,
            'sha256' => $overrideSha256 ?? hash('sha256', $content),
            'size' => $overrideSize ?? strlen($content),
        ];
        return $this;
    }

    public function withExtraZipFile(string $path, string $content): self
    {
        $this->extraZipFiles[] = [
            'path' => $path,
            'content' => $content,
        ];
        return $this;
    }

    public function withMissingDiskFile(
        string $path,
        ?string $sha256 = null,
        int $size = 42,
        string $mode = 'replace',
    ): self {
        $this->missingDiskFiles[] = [
            'path' => $path,
            'sha256' => $sha256 ?? str_repeat('a', 64),
            'size' => $size,
            'mode' => $mode,
        ];
        return $this;
    }

    public function withSymlink(string $entryName, string $target = '/etc/passwd'): self
    {
        $this->symlinks[] = [
            'entryName' => $entryName,
            'target' => $target,
        ];
        return $this;
    }

    /**
     * @param list<string> $removed
     */
    public function withRemoved(array $removed): self
    {
        $this->removed = $removed;
        return $this;
    }

    public function withMigration(
        string $scope,
        string $name,
        string $content,
        string $type = 'additive',
        ?string $overrideSha256 = null,
        bool $includeMigrationFile = true,
    ): self {
        $relPath = $scope === '__core__'
            ? 'src/Core/Database/migrations/' . $name . '.php'
            : 'src/Plugins/' . $scope . '/migrations/' . $name . '.php';

        $actualSha = hash('sha256', $content);

        if ($includeMigrationFile) {
            $this->withFile($relPath, $content);
        }

        $this->migrations[] = [
            'scope' => $scope,
            'name' => $name,
            'sha256' => $overrideSha256 ?? $actualSha,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * @param array<string, list<string>> $expects
     */
    public function withExpects(array $expects): self
    {
        $this->expects = $expects;
        return $this;
    }

    /**
     * @param array<string, mixed>|string $rawManifest
     */
    public function withRawManifest(array|string $rawManifest): self
    {
        $this->rawManifestOverride = $rawManifest;
        return $this;
    }

    public function withoutManifestFile(): self
    {
        $this->omitManifestFile = true;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toManifestArray(): array
    {
        $manifestFiles = [];
        foreach ($this->files as $f) {
            $manifestFiles[] = [
                'path' => $f['path'],
                'sha256' => $f['sha256'],
                'size' => $f['size'],
                'mode' => $f['mode'],
            ];
        }
        foreach ($this->missingDiskFiles as $mf) {
            $manifestFiles[] = [
                'path' => $mf['path'],
                'sha256' => $mf['sha256'],
                'size' => $mf['size'],
                'mode' => $mf['mode'],
            ];
        }

        return [
            'protocol' => $this->protocol,
            'minUpdaterProtocol' => $this->minUpdaterProtocol,
            'kind' => $this->kind->value,
            'slug' => $this->slug,
            'version' => $this->version,
            'requires' => $this->requires,
            'files' => $manifestFiles,
            'removed' => $this->removed,
            'migrations' => $this->migrations,
            'expects' => (object)$this->expects,
        ];
    }

    public function build(
        string $targetDir,
        ?string $overrideReleaseSha256 = null,
        ?string $overrideReleaseVersion = null,
    ): DownloadedPackage {
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Não foi possível criar diretório temporário: ' . $targetDir);
        }

        $zipPath = rtrim($targetDir, '/\\')
            . DIRECTORY_SEPARATOR
            . 'domain-system-' . $this->version . '-' . bin2hex(random_bytes(4)) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Falha ao criar arquivo ZIP em ' . $zipPath);
        }

        if (!$this->omitManifestFile) {
            if (is_string($this->rawManifestOverride)) {
                $manifestJson = $this->rawManifestOverride;
            } elseif (is_array($this->rawManifestOverride)) {
                $manifestJson = (string)json_encode($this->rawManifestOverride, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                $manifestJson = (string)json_encode($this->toManifestArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            $zip->addFromString('manifest.json', $manifestJson);
        }

        foreach ($this->files as $file) {
            $zip->addFromString($file['path'], $file['content']);
        }

        foreach ($this->extraZipFiles as $extra) {
            $zip->addFromString($extra['path'], $extra['content']);
        }

        foreach ($this->symlinks as $symlink) {
            $zip->addFromString($symlink['entryName'], $symlink['target']);
            // Atributo Unix S_IFLNK (0120000 = 0xA000) + 0777 (0x01FF) = 0xA1FF nos 16 bits superiores
            $zip->setExternalAttributesName(
                $symlink['entryName'],
                ZipArchive::OPSYS_UNIX,
                (0xA1FF << 16)
            );
        }

        $zip->close();

        $actualZipSha = (string)hash_file('sha256', $zipPath);
        $sizeBytes = (int)filesize($zipPath);
        $releaseVersion = $overrideReleaseVersion ?? $this->version;

        $release = new ReleaseInfo(
            version: $releaseVersion,
            tag: 'v' . $releaseVersion,
            publishedAt: '2026-10-19T12:00:00Z',
            changelog: 'Release de teste ' . $releaseVersion,
            assetUrl: 'https://github.com/hadelrachid/domain-system/releases/download/v' . $releaseVersion . '/domain-system-' . $releaseVersion . '.zip',
            assetName: 'domain-system-' . $releaseVersion . '.zip',
            sizeBytes: $sizeBytes,
            sha256: $overrideReleaseSha256 ?? $actualZipSha,
            etag: '"etag-' . $releaseVersion . '"',
        );

        return new DownloadedPackage(
            release: $release,
            archivePath: $zipPath,
            sizeBytes: $sizeBytes,
        );
    }
}
