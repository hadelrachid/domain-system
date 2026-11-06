<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de armazenamento e resolução de URLs de arquivos PDF para aulas do Academy (SOLID - DIP / ISP).
 *
 * Suporta tanto upload local (ex: pasta na Hostinger / XAMPP via PublicStorageInterface)
 * quanto resolução e higienização de URLs externas na nuvem (HTTP/HTTPS, S3, Cloudflare R2, CDN, Google Drive).
 */
interface LessonPdfStorageInterface
{
    /**
     * Armazena um arquivo PDF enviado via formulário/AJAX validando assinatura mágica (%PDF-) e limites.
     *
     * @param array<string, mixed> $fileInfo Estrutura de arquivo do Request::file()
     * @return array{url: string, relative_path: string, file_name: string, size: int}
     */
    public function storePdfUpload(array $fileInfo): array;

    /**
     * Resolve e higieniza uma URL ou caminho relativo de PDF (local ou nuvem HTTP/HTTPS).
     */
    public function resolvePublicUrl(string $rawUrl): string;

    /**
     * Remove um arquivo PDF armazenado localmente.
     */
    public function deletePdf(string $relativePath): void;
}
