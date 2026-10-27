<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Bloco Semântico para o Motor Visual (Padrão Elementor / Gutenberg / Academy).
 */
interface BlockInterface
{
    /**
     * Tipo único do bloco (ex: 'heading', 'paragraph', 'image', 'video', 'nav_menu', 'cta_button', 'shortcode').
     */
    public function getType(): string;

    /**
     * Nome amigável de exibição no painel.
     */
    public function getName(): string;

    /**
     * Ícone do bloco (emoji ou classe de ícone).
     */
    public function getIcon(): string;

    /**
     * Categoria do bloco ('typography', 'media', 'navigation', 'action', 'system', 'layout', 'advanced').
     */
    public function getCategory(): string;

    /**
     * Renderiza o bloco compilando para HTML5 semântico limpo.
     *
     * @param array<string, mixed> $data Dados e propriedades específicas do bloco.
     * @param array<string, mixed> $context Contexto de execução (tema ativo, dispositivo, etc.).
     */
    public function render(array $data = [], array $context = []): string;

    /**
     * Retorna o esquema JSON com as propriedades e controles suportados pelo bloco.
     * @return array<string, mixed>
     */
    public function getSchema(): array;

    /**
     * Retorna os tipos de partes estruturais onde o bloco é permitido (ex: ['header', 'footer', 'section']).
     * Permite segregação estrita (ISP/SRP): blocos de conteúdo central não poluem headers ou footers.
     * Se retornar array vazio, o bloco é permitido universalmente.
     *
     * @return array<int, string>
     */
    public function getAllowedPartTypes(): array;
}
