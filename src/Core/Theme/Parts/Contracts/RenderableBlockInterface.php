<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Subcontrato focado exclusivamente na capacidade de renderização de um bloco (SOLID - ISP).
 */
interface RenderableBlockInterface
{
    /**
     * Tipo único do bloco (ex: 'heading', 'paragraph', 'image', 'video', 'nav_menu', 'cta_button', 'shortcode').
     */
    public function getType(): string;

    /**
     * Renderiza o bloco compilando para HTML5 semântico limpo.
     *
     * @param array<string, mixed> $data Dados e propriedades específicas do bloco.
     * @param array<string, mixed> $context Contexto de execução (tema ativo, dispositivo, etc.).
     */
    public function render(array $data = [], array $context = []): string;

    /**
     * Retorna os tipos de partes estruturais onde o bloco é permitido (ex: ['header', 'footer', 'section']).
     * Se retornar array vazio, o bloco é permitido universalmente.
     *
     * @return array<int, string>
     */
    public function getAllowedPartTypes(): array;
}
