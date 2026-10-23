<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Layout;

/**
 * Contrato para Presets de Grade / Layout Responsivo no Flex Builder (SOLID - OCP / Ring 0).
 * 
 * Permite adicionar novos formatos de layout e divisórias sem modificar o motor principal.
 */
interface LayoutPresetInterface
{
    /** Identificador único do preset (ex: 'cards_grid_4', '2_cols_equal') */
    public function getId(): string;

    /** Nome legível para o usuário (ex: 'Grid de Cards / Blog (4 Colunas)') */
    public function getName(): string;

    /** Categoria estrutural (ex: 'grid', 'header', 'footer', 'content', 'hero') */
    public function getCategory(): string;

    /** Quantidade de colunas / células lógicas da estrutura */
    public function getColumns(): int;

    /** Template CSS Grid para Desktop (ex: 'repeat(4, 1fr)') */
    public function getDesktopTemplate(): string;

    /** Template CSS Grid para Tablet (ex: 'repeat(2, 1fr)') */
    public function getTabletTemplate(): string;

    /** Template CSS Grid para Mobile (ex: '1fr') */
    public function getMobileTemplate(): string;

    /** Ícone representativo do preset */
    public function getIcon(): string;

    /** Serializa o preset em array para a API e Views */
    public function toArray(): array;
}
