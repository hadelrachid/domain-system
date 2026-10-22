<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Contracts;

/**
 * Contrato para serviços de upload seguro de mídias e ativos do Builder.
 */
interface MediaUploadServiceInterface
{
    /**
     * Processa e armazena com segurança um arquivo de imagem enviado via formulário.
     *
     * @param array<string, mixed> $file Array correspondente a um arquivo ($_FILES item)
     * @return array{success: bool, url?: string, filename?: string, message: string}
     */
    public function upload(array $file): array;
}
