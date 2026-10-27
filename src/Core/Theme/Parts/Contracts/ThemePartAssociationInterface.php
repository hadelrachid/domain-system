<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Associação e Vínculo: Tema <-> Header / Footer / Seções de Template Ativos.
 */
interface ThemePartAssociationInterface
{
    /**
     * Retorna o ID da parte atribuída a um tema específico.
     * Retorna null se não houver parte customizada (acionando fallback nativo do tema).
     */
    public function getAssignedPartId(string $themeFolder, string $partType): ?string;

    /**
     * Associa uma parte (header, footer ou template:slug) a um tema específico.
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
     * Retorna o mapa completo de todas as atribuições registradas ('themes' e 'globals').
     * @return array<string, mixed>
     */
    public function getAllAssignments(): array;

    /**
     * Retorna as atribuições exclusivas de um tema específico ([partType => partId]).
     * @return array<string, string>
     */
    public function getThemeAssignments(string $themeFolder): array;

    /**
     * Retorna o slug do template (ex: 'home', 'sobre') ao qual uma seção está atribuída em um tema, ou null.
     */
    public function getAssignedTemplateForSection(string $themeFolder, string $sectionId): ?string;

    /**
     * Atribui exclusivamente uma seção a um template do tema (removendo vínculos anteriores da mesma seção).
     * Passar $templateSlug = null ou 'none' desvincula a seção de qualquer template no tema.
     */
    public function assignTemplateToSection(string $themeFolder, string $sectionId, ?string $templateSlug): bool;
}
