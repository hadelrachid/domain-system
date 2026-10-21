<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Associação e Vínculo: Tema <-> Header / Footer Ativo.
 */
interface ThemePartAssociationInterface
{
    /**
     * Retorna o ID da parte atribuída a um tema específico.
     * Retorna null se não houver parte customizada (acionando fallback nativo do tema).
     */
    public function getAssignedPartId(string $themeFolder, string $partType): ?string;

    /**
     * Associa uma parte (header ou footer) a um tema específico.
     * Passar $partId = null remove o vínculo customizado (restaura fallback nativo).
     */
    public function assignPart(string $themeFolder, string $partType, ?string $partId): bool;

    /**
     * Define a parte padrão global do sistema para um determinado tipo.
     */
    public function setGlobalDefault(string $partType, ?string $partId): bool;

    /**
     * Retorna o ID da parte padrão global.
     */
    public function getGlobalDefault(string $partType): ?string;

    /**
     * Retorna o mapa completo de todas as atribuições registradas.
     * @return array<string, array<string, string>> [themeFolder => [partType => partId]]
     */
    public function getAllAssignments(): array;
}
