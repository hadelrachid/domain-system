<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Design Tokens Globais (Paleta de Cores e Tipografia).
 */
interface DesignTokensInterface
{
    /**
     * Retorna o array associativo com as cores da paleta.
     * @return array<string, string> ex: ['primary' => '#45f3ff', 'secondary' => '#1f2833', ...]
     */
    public function getColorPalette(): array;

    /**
     * Retorna a configuração tipográfica.
     * @return array<string, mixed> ex: ['primary_font' => 'Inter', 'secondary_font' => 'Fira Code', ...]
     */
    public function getTypography(): array;

    /**
     * Gera o bloco de variáveis CSS :root e escopo específico para injeção inline no layout.
     * @param string|null $scope Seletor CSS alvo (ex: '.ds-part-wrapper, .ds-section-wrapper'). Se nulo, padroniza para encapsulamento das partes.
     */
    public function generateCssVariables(?string $scope = null): string;

    /**
     * Atualiza os tokens e persiste no repositório.
     * @param array<string, mixed> $tokens
     */
    public function updateTokens(array $tokens): bool;
}
