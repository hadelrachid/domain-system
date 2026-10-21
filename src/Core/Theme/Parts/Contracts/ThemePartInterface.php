<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Parte Estrutural de Tema (Header, Footer, Seção ou Template Part).
 */
interface ThemePartInterface
{
    public const TYPE_HEADER  = 'header';
    public const TYPE_FOOTER  = 'footer';
    public const TYPE_SECTION = 'section';

    /** Identificador único (slug ou hash) */
    public function getId(): string;

    /** Tipo da parte: 'header', 'footer' ou 'section' */
    public function getType(): string;

    /** Título ou nome de exibição da parte */
    public function getTitle(): string;

    /** Preset de grade de colunas (ex: '3_cols_header', '4_cols_footer', '1_col') */
    public function getLayoutPreset(): string;

    /** Configurações de container (boxed/full, gap, padding, margin, etc.) */
    public function getContainerSettings(): array;

    /**
     * Lista de blocos organizados por coluna.
     * @return array<int, array<int, array<string, mixed>>> [colIndex => [ [ 'type' => '...', 'data' => [...] ] ]]
     */
    public function getColumnsData(): array;

    /** Estilo do botão de toggle mobile do menu (classic, pill, minimal, dots) */
    public function getMobileToggleStyle(): string;

    /** Se é a parte padrão de fallback global */
    public function isDefault(): bool;

    public function getCreatedAt(): int;

    public function getUpdatedAt(): int;

    /** Serializa a parte para array compatível com JSON */
    public function toArray(): array;
}
