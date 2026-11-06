<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Contracts\PublicStorageInterface;
use DomainSystem\Core\Storage\LocalPublicStorage;
use DomainSystem\Plugins\academy\Contracts\LessonPdfStorageInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Implementação de armazenamento local e resolução híbrida (Local Hostinger + Nuvem HTTP/HTTPS)
 * de documentos PDF para o Academy LMS (SOLID - SRP / DIP).
 */
class LocalLessonPdfStorage implements LessonPdfStorageInterface
{
    private PublicStorageInterface $storage;
    private const MAX_BYTES = 134217728; // 128 MB para apostilas, livros e gráficos pesados

    public function __construct(PublicStorageInterface|string $storage)
    {
        if (is_string($storage)) {
            $this->storage = new LocalPublicStorage($storage);
        } else {
            $this->storage = $storage;
        }
    }

    public function storePdfUpload(array $fileInfo): array
    {
        $error = (int)($fileInfo['error'] ?? UPLOAD_ERR_OK);
        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                $maxIni = (string)ini_get('upload_max_filesize');
                throw new InvalidArgumentException("O arquivo PDF excede o limite máximo de upload configurado no PHP ({$maxIni}).");
            }
            if ($error === UPLOAD_ERR_PARTIAL) {
                throw new InvalidArgumentException('O envio do arquivo PDF foi interrompido antes de concluir.');
            }
            if ($error === UPLOAD_ERR_NO_FILE) {
                throw new InvalidArgumentException('Nenhum arquivo PDF foi selecionado.');
            }
            throw new InvalidArgumentException('Erro no envio do arquivo PDF (código: ' . $error . ').');
        }

        $tmpName = (string)($fileInfo['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new InvalidArgumentException('Nenhum arquivo PDF válido foi recebido.');
        }

        $size = (int)($fileInfo['size'] ?? filesize($tmpName));
        if ($size <= 0) {
            throw new InvalidArgumentException('O arquivo PDF enviado está vazio.');
        }
        if ($size > self::MAX_BYTES) {
            throw new InvalidArgumentException('O arquivo PDF excede o limite máximo de 128 MB.');
        }

        $originalName = (string)($fileInfo['name'] ?? 'documento.pdf');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new InvalidArgumentException('Formato inválido. Apenas arquivos com extensão .pdf são permitidos.');
        }

        // Validação de assinatura mágica (%PDF-) nos primeiros bytes para impedir upload de scripts disfarçados
        $handle = @fopen($tmpName, 'rb');
        $headerBytes = $handle !== false ? (string)@fread($handle, 8) : '';
        if ($handle !== false) {
            @fclose($handle);
        }

        if (!str_starts_with($headerBytes, '%PDF-')) {
            throw new InvalidArgumentException('O arquivo enviado não possui cabeçalho binário PDF válido (%PDF-).');
        }

        // Validação adicional de MIME quando finfo estiver disponível
        if (class_exists('\finfo')) {
            try {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = (string)$finfo->file($tmpName);
                if ($mime !== '' && !in_array($mime, ['application/pdf', 'application/x-pdf', 'application/octet-stream'], true)) {
                    throw new InvalidArgumentException("Tipo MIME incompatível com PDF: {$mime}");
                }
            } catch (InvalidArgumentException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Prossegue se finfo falhar no ambiente mas %PDF- já foi validado
            }
        }

        $baseSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
        $baseSlug = preg_replace('/-+/', '-', (string)$baseSlug);
        $baseSlug = trim((string)$baseSlug, '-');
        if ($baseSlug === '') {
            $baseSlug = 'aula';
        }
        $baseSlug = substr(strtolower($baseSlug), 0, 48);

        $safeName = 'pdf_' . $baseSlug . '_' . bin2hex(random_bytes(6)) . '.pdf';
        $relativeSubpath = 'academy/pdfs/' . $safeName;
        $destination = $this->storage->getUploadPath($relativeSubpath);

        $destDir = dirname($destination);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $moved = false;
        if (is_uploaded_file($tmpName)) {
            $moved = @move_uploaded_file($tmpName, $destination);
        }
        if (!$moved) {
            $moved = @copy($tmpName, $destination);
        }

        if (!$moved) {
            throw new RuntimeException('Falha ao gravar o arquivo PDF no diretório público de armazenamento.');
        }

        $relativePath = 'uploads/' . $relativeSubpath;
        $publicUrl = $this->storage->getPublicUrl($relativePath);

        return [
            'url'           => $publicUrl,
            'relative_path' => $relativePath,
            'file_name'     => $safeName,
            'size'          => $size,
        ];
    }

    public function resolvePublicUrl(string $rawUrl): string
    {
        $trimmed = trim($rawUrl);
        if ($trimmed === '') {
            return '';
        }

        // Bloqueia esquemas perigosos (XSS)
        if (preg_match('/^\s*(?:javascript|data|vbscript|file):/i', $trimmed)) {
            return '';
        }

        // URLs externas na nuvem (HTTP / HTTPS)
        if (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        // Caminhos relativos locais (ex: uploads/academy/pdfs/arquivo.pdf ou /storage/...)
        if (str_starts_with($trimmed, 'uploads/')) {
            return $this->storage->getPublicUrl($trimmed);
        }

        return $trimmed;
    }

    public function deletePdf(string $relativePath): void
    {
        $this->storage->delete($relativePath);
    }
}
