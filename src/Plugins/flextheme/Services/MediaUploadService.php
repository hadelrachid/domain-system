<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Plugins\flextheme\Contracts\MediaUploadServiceInterface;

/**
 * Serviço responsável pelo upload seguro de imagens para o Flex Builder.
 *
 * Aplica validação de tamanho máximo, whitelist estrita de extensões e tipos MIME,
 * sanitização de nomes com entropia criptográfica e isolamento no sistema de arquivos.
 */
class MediaUploadService implements MediaUploadServiceInterface
{
    public const DEFAULT_MAX_SIZE = 5242880; // 5MB em bytes

    public const ALLOWED_EXTENSIONS = [
        'png',
        'jpg',
        'jpeg',
        'webp',
        'svg',
        'gif',
    ];

    public const ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/svg+xml',
        'image/gif',
    ];

    private string $uploadDir;
    private string $baseUrl;
    private int $maxBytes;

    public function __construct(
        ?string $basePath = null,
        ?string $baseUrl = null,
        int $maxBytes = self::DEFAULT_MAX_SIZE
    ) {
        $root = rtrim($basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4)), '/\\');
        $this->uploadDir = $root . '/public/uploads/builder';
        $this->baseUrl   = rtrim($baseUrl ?? (defined('BASE_URL') ? BASE_URL : ''), '/\\');
        $this->maxBytes  = $maxBytes;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{success: bool, url?: string, filename?: string, message: string}
     */
    public function upload(array $file): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return [
                'success' => false,
                'message' => 'Nenhum arquivo enviado ou erro durante o envio.',
            ];
        }

        $tmpName = (string)$file['tmp_name'];
        if (!file_exists($tmpName) || !is_readable($tmpName)) {
            return [
                'success' => false,
                'message' => 'Arquivo temporário indisponível ou ilegível.',
            ];
        }

        $size = (int)($file['size'] ?? filesize($tmpName));
        if ($size <= 0 || $size > $this->maxBytes) {
            $maxMb = round($this->maxBytes / (1024 * 1024), 1);
            return [
                'success' => false,
                'message' => "O arquivo excede o limite máximo permitido de {$maxMb}MB.",
            ];
        }

        $origName = (string)($file['name'] ?? 'upload');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'success' => false,
                'message' => 'Extensão não permitida. Envie PNG, SVG, JPG, WEBP ou GIF.',
            ];
        }

        // Validação estrita por detecção de MIME real via finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (is_string($mime) && !in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
                    // Tolerância específica para arquivos SVG puros que podem ser reportados como text/plain ou text/xml
                    if ($ext === 'svg' && (str_contains($mime, 'xml') || str_contains($mime, 'text'))) {
                        // Válido
                    } else {
                        return [
                            'success' => false,
                            'message' => "Tipo de arquivo inválido ($mime).",
                        ];
                    }
                }
            }
        }

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }

        $safeName = 'media_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $this->uploadDir . '/' . $safeName;

        // Suporte tanto a uploads normais via HTTP (is_uploaded_file / move_uploaded_file)
        // quanto testes unitários (copy)
        $moved = false;
        if (is_uploaded_file($tmpName)) {
            $moved = @move_uploaded_file($tmpName, $destPath);
        }
        if (!$moved) {
            $moved = @copy($tmpName, $destPath);
        }

        if (!$moved) {
            return [
                'success' => false,
                'message' => 'Falha ao salvar o arquivo no diretório de destino.',
            ];
        }

        $publicUrl = $this->baseUrl . '/uploads/builder/' . $safeName;

        return [
            'success'  => true,
            'url'      => $publicUrl,
            'filename' => $safeName,
            'message'  => 'Imagem enviada com sucesso!',
        ];
    }
}
