<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Hierarquia e Árvore de Camadas (Layers / Navigator).
 * 
 * Permite extrair e manipular a estrutura em árvore de qualquer ThemePartInterface
 * para alimentar a interface do construtor visual (Elementor Navigator),
 * auditorias de acessibilidade e renderização de layouts hierárquicos.
 */
interface LayerTreeInterface
{
    /**
     * Limites de seções verticais por tipo de parte.
     */
    public const MAX_SECTIONS_HEADER = 3;
    public const MAX_SECTIONS_FOOTER = 3;
    public const MAX_SECTIONS_DEFAULT = 12;

    /**
     * Constrói a árvore hierárquica completa da parte do tema.
     *
     * @param ThemePartInterface $part
     * @return array<string, mixed> Estrutura em árvore padronizada contendo root, seções, colunas e blocos.
     */
    public function buildTree(ThemePartInterface $part): array;

    /**
     * Retorna a contagem total de nós na hierarquia (seções, colunas, blocos).
     *
     * @param ThemePartInterface $part
     * @return array{sections: int, columns: int, blocks: int}
     */
    public function getCounts(ThemePartInterface $part): array;

    /**
     * Valida se uma contagem de seções respeita o limite do tipo da parte.
     *
     * @param string $partType (header, footer, section)
     * @param int $sectionCount
     * @return bool
     */
    public function validateDepth(string $partType, int $sectionCount): bool;
}
