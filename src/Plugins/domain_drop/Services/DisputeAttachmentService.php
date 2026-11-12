<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

/**
 * Valida e persiste anexos (PDF e imagens) de reclamações de clientes fora da pasta pública.
 */
class DisputeAttachmentService
{
    public const MAX_FILE_SIZE_BYTES = 5242880; // 5 MB
    public const MAX_FILES_PER_DISPUTE = 4;

    private const ALLOWED_MIME_MAP = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    private string $storageDir;

    public function __construct(?string $basePath = null, private readonly bool $requireUploadedFile = true)
    {
        $root = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->storageDir = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'domain_drop' . DIRECTORY_SEPARATOR . 'disputes';
    }

    public function getStorageDir(): string
    {
        return $this->storageDir;
    }

    /**
     * Valida e armazena arquivos enviados via $request->files['evidences'].
     *
     * @param array<string, mixed>|null $fileInput
     * @return array{attachments: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function storeUploadedFiles(?array $fileInput): array
    {
        if (empty($fileInput) || empty($fileInput['name'])) {
            return ['attachments' => [], 'errors' => []];
        }

        $normalized = $this->normalizeFiles($fileInput);
        if (count($normalized) > self::MAX_FILES_PER_DISPUTE) {
            return [
                'attachments' => [],
                'errors'      => ['Envie no máximo ' . self::MAX_FILES_PER_DISPUTE . ' arquivos (PDF ou imagem).'],
            ];
        }

        $attachments = [];
        $errors = [];

        foreach ($normalized as $file) {
            $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = 'Falha no upload do arquivo "' . (string)($file['name'] ?? 'anexo') . '".';
                continue;
            }

            $originalName = basename((string)($file['name'] ?? ''));
            $tmpPath = (string)($file['tmp_name'] ?? '');
            $size = (int)($file['size'] ?? 0);

            if ($tmpPath === '' || !is_file($tmpPath)) {
                $errors[] = 'Arquivo temporário inválido.';
                continue;
            }
            if ($this->requireUploadedFile && !is_uploaded_file($tmpPath)) {
                $errors[] = 'Origem de arquivo não permitida.';
                continue;
            }
            if ($size <= 0 || $size > self::MAX_FILE_SIZE_BYTES) {
                $errors[] = "O arquivo '{$originalName}' excede o limite de 5 MB.";
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!isset(self::ALLOWED_MIME_MAP[$ext])) {
                $errors[] = "Formato não permitido para '{$originalName}'. Envie apenas PDF, JPG, PNG ou WEBP.";
                continue;
            }

            $detectedMime = $this->detectMime($tmpPath);
            if (!in_array($detectedMime, self::ALLOWED_MIME_MAP[$ext], true)) {
                $errors[] = "Conteúdo incompatível para '{$originalName}' (MIME detectado: {$detectedMime}).";
                continue;
            }

            if (!is_dir($this->storageDir)) {
                @mkdir($this->storageDir, 0775, true);
            }

            $storedFilename = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetPath = $this->storageDir . DIRECTORY_SEPARATOR . $storedFilename;

            $moved = $this->requireUploadedFile
                ? @move_uploaded_file($tmpPath, $targetPath)
                : @copy($tmpPath, $targetPath);

            if (!$moved) {
                $errors[] = "Não foi possível salvar '{$originalName}'.";
                continue;
            }

            $attachments[] = [
                'filename'      => $storedFilename,
                'original_name' => $originalName,
                'mime'          => $detectedMime,
                'size'          => $size,
            ];
        }

        return [
            'attachments' => $attachments,
            'errors'      => $errors,
        ];
    }

    /**
     * Resolve caminho absoluto seguro de um anexo já armazenado.
     */
    public function resolveSafePath(string $filename): ?string
    {
        $clean = basename($filename);
        if ($clean === '' || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|jpeg|png|webp)$/i', $clean)) {
            return null;
        }

        $candidate = $this->storageDir . DIRECTORY_SEPARATOR . $clean;
        if (!is_file($candidate)) {
            return null;
        }

        $realBase = realpath($this->storageDir);
        $realFile = realpath($candidate);
        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase)) {
            return null;
        }

        return $realFile;
    }

    /**
     * @param array<string, mixed> $fileInput
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFiles(array $fileInput): array
    {
        if (!is_array($fileInput['name'])) {
            return [$fileInput];
        }

        $items = [];
        $count = count($fileInput['name']);
        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'name'     => $fileInput['name'][$i] ?? '',
                'type'     => $fileInput['type'][$i] ?? '',
                'tmp_name' => $fileInput['tmp_name'][$i] ?? '',
                'error'    => $fileInput['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $fileInput['size'][$i] ?? 0,
            ];
        }

        return $items;
    }

    private function detectMime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = (string)@finfo_file($finfo, $path);
                @finfo_close($finfo);
                if ($mime !== '') {
                    return strtolower(trim($mime));
                }
            }
        }

        return 'application/octet-stream';
    }
}
