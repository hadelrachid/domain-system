<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Contracts;

/**
 * Contrato para compilação ao vivo de pré-visualização (Preview) de partes visuais (SOLID - SRP / DIP).
 */
interface ThemePartPreviewServiceInterface
{
    /**
     * Compila o HTML e CSS de preview a partir do payload bruto enviado pelo Studio,
     * aplicando rigorosamente as mesmas regras de contrato (getAllowedPartTypes),
     * estilos de elemento e classes responsivas do motor de produção.
     *
     * @param array<string, mixed> $payload
     */
    public function renderPreview(array $payload): string;
}
