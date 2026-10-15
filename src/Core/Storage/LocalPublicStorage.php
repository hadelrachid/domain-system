<?php

declare(strict_types=1);

namespace DomainSystem\Core\Storage;

use DomainSystem\Core\Contracts\PublicStorageInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Implementação local e agnóstica do armazenamento público.
 *
 * Resolve dinamicamente a raiz de arquivos e URLs web sem acoplar
 * o sistema ou plugins a servidores específicos (XAMPP, Apache, Nginx, Hostinger, etc).
 */
class LocalPublicStorage implements PublicStorageInterface
{
    private string $basePath;
    /** @var callable|string */
    private $baseUrlProvider;
    private string $uploadSubdir;

    public function __construct(
        string $basePath,
        callable|string $baseUrlProvider = '',
        string $uploadSubdir = 'uploads'
    ) {
        $this->basePath = rtrim($basePath, '/\\');
        $this->baseUrlProvider = $baseUrlProvider;
        $this->uploadSubdir = trim($uploadSubdir, '/\\');
    }

    public function getUploadPath(string $subpath = ''): string
    {
        $cleanSubpath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subpath), DIRECTORY_SEPARATOR);
        $this->assertSafePath($cleanSubpath);

        $normalizedUploads = $this->uploadSubdir . DIRECTORY_SEPARATOR;
        if (str_starts_with($cleanSubpath, $normalizedUploads)) {
            $cleanSubpath = substr($cleanSubpath, strlen($normalizedUploads));
        }

        $publicDir = is_dir($this->basePath . DIRECTORY_SEPARATOR . 'public')
            ? $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $this->uploadSubdir
            : $this->basePath . DIRECTORY_SEPARATOR . $this->uploadSubdir;

        $target = $cleanSubpath === '' 
            ? $publicDir 
            : $publicDir . DIRECTORY_SEPARATOR . $cleanSubpath;

        $dirToEnsure = (is_dir($target) || pathinfo($target, PATHINFO_EXTENSION) === '')
            ? $target
            : dirname($target);

        if (!is_dir($dirToEnsure)) {
            @mkdir($dirToEnsure, 0755, true);
        }

        return $target;
    }

    public function getPublicUrl(string $relativePath): string
    {
        $trimmed = trim($relativePath);
        if ($trimmed === '') {
            return '';
        }

        if (str_starts_with($trimmed, 'http://') || 
            str_starts_with($trimmed, 'https://') || 
            str_starts_with($trimmed, 'data:') || 
            str_starts_with($trimmed, '//')) {
            return $trimmed;
        }

        $baseUrl = is_callable($this->baseUrlProvider) 
            ? (string)($this->baseUrlProvider)() 
            : (string)$this->baseUrlProvider;

        $baseUrl = rtrim($baseUrl, '/');
        $cleanRelative = ltrim(str_replace('\\', '/', $trimmed), '/');

        if (!str_starts_with($cleanRelative, $this->uploadSubdir . '/') && $cleanRelative !== $this->uploadSubdir) {
            $cleanRelative = $this->uploadSubdir . '/' . $cleanRelative;
        }

        return $baseUrl !== '' ? $baseUrl . '/' . $cleanRelative : '/' . $cleanRelative;
    }

    public function put(string $relativePath, string $contents): string
    {
        $cleanRelative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $this->assertSafePath($cleanRelative);

        $normalizedUploads = $this->uploadSubdir . DIRECTORY_SEPARATOR;
        if (str_starts_with($cleanRelative, $normalizedUploads)) {
            $cleanRelative = substr($cleanRelative, strlen($normalizedUploads));
        }

        $fullPath = $this->getUploadPath($cleanRelative);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (@file_put_contents($fullPath, $contents) === false) {
            throw new RuntimeException("Falha ao gravar arquivo no armazenamento público: {$fullPath}");
        }

        return $this->uploadSubdir . '/' . str_replace('\\', '/', $cleanRelative);
    }

    public function delete(string $relativePath): bool
    {
        $cleanRelative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $this->assertSafePath($cleanRelative);

        $normalizedUploads = $this->uploadSubdir . DIRECTORY_SEPARATOR;
        if (str_starts_with($cleanRelative, $normalizedUploads)) {
            $cleanRelative = substr($cleanRelative, strlen($normalizedUploads));
        }

        $fullPath = $this->getUploadPath($cleanRelative);

        $deleted = false;
        if (file_exists($fullPath) && is_file($fullPath)) {
            $deleted = @unlink($fullPath);
        }

        $legacyDir = $this->basePath . DIRECTORY_SEPARATOR . $this->uploadSubdir . DIRECTORY_SEPARATOR . $cleanRelative;
        if ($legacyDir !== $fullPath && file_exists($legacyDir) && is_file($legacyDir)) {
            @unlink($legacyDir);
            $deleted = true;
        }

        return $deleted;
    }

    public function exists(string $relativePath): bool
    {
        $cleanRelative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $this->assertSafePath($cleanRelative);

        $normalizedUploads = $this->uploadSubdir . DIRECTORY_SEPARATOR;
        if (str_starts_with($cleanRelative, $normalizedUploads)) {
            $cleanRelative = substr($cleanRelative, strlen($normalizedUploads));
        }

        $fullPath = $this->getUploadPath($cleanRelative);
        return file_exists($fullPath) && is_file($fullPath);
    }

    private function assertSafePath(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        if (str_contains($normalized, '../') || str_contains($normalized, '/..') || $normalized === '..') {
            throw new InvalidArgumentException("Tentativa de travessia de diretório detectada: {$path}");
        }
    }
}
