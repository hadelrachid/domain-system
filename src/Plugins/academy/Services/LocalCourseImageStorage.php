<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface;

class LocalCourseImageStorage implements CourseImageStorageInterface
{
    private string $uploadDir;
    private string $publicUrlPrefix;
    private const MAX_BYTES = 2097152; // 2 MB
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(string $publicUploadDir, string $publicUrlPrefix = 'uploads/academy')
    {
        $this->uploadDir = rtrim($publicUploadDir, '/\\') . DIRECTORY_SEPARATOR . 'academy';
        $this->publicUrlPrefix = trim($publicUrlPrefix, '/\\');
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    public function storeUpload(array $fileInfo): string
    {
        if (empty($fileInfo['tmp_name']) || !file_exists($fileInfo['tmp_name'])) {
            throw new \InvalidArgumentException("Nenhum arquivo válido enviado.");
        }

        $size = (int)($fileInfo['size'] ?? filesize($fileInfo['tmp_name']));
        if ($size > self::MAX_BYTES) {
            throw new \InvalidArgumentException("A imagem deve ter no máximo 2MB.");
        }

        // Validação estrita de MIME type real (finfo)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($fileInfo['tmp_name']);
        if (!isset(self::ALLOWED_MIMES[$realMime])) {
            throw new \InvalidArgumentException("Formato inválido. Apenas JPG, PNG ou WEBP são aceitos.");
        }

        // Validação de dimensões e aspect ratio
        $imageSize = @getimagesize($fileInfo['tmp_name']);
        if (!$imageSize) {
            throw new \InvalidArgumentException("O arquivo enviado não é uma imagem válida.");
        }

        $width = (int)$imageSize[0];
        $height = (int)$imageSize[1];
        if ($width < 640 || $height < 360) {
            throw new \InvalidArgumentException("Dimensão mínima exigida: 640x360 pixels (enviado: {$width}x{$height}).");
        }

        $aspectRatio = $width / $height;
        $expectedRatio = 16.0 / 9.0; // ~1.777
        if (abs($aspectRatio - $expectedRatio) > 0.10) {
            throw new \InvalidArgumentException("A imagem do card deve respeitar a proporção 16:9 (horizontal).");
        }

        $extension = self::ALLOWED_MIMES[$realMime];
        $safeName = 'course_' . bin2hex(random_bytes(10)) . '.' . $extension;
        $destination = $this->uploadDir . DIRECTORY_SEPARATOR . $safeName;

        if (is_uploaded_file($fileInfo['tmp_name'])) {
            if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
                throw new \RuntimeException("Falha ao salvar a imagem no diretório de destino.");
            }
        } else {
            if (!copy($fileInfo['tmp_name'], $destination)) {
                throw new \RuntimeException("Falha ao salvar a imagem no diretório de destino.");
            }
        }

        return $this->publicUrlPrefix . '/' . $safeName;
    }

    public function deleteFile(string $relativePath): void
    {
        $basename = basename($relativePath);
        $fullPath = $this->uploadDir . DIRECTORY_SEPARATOR . $basename;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        $legacyDir = dirname($this->uploadDir, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'academy';
        if ($legacyDir !== $this->uploadDir) {
            $legacyFile = $legacyDir . DIRECTORY_SEPARATOR . $basename;
            if (file_exists($legacyFile)) {
                @unlink($legacyFile);
            }
        }
    }

    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }
}
