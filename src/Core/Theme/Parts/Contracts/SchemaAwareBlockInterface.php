<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Subcontrato focado nos metadados visuais e schema do Inspetor no Studio (SOLID - ISP).
 */
interface SchemaAwareBlockInterface
{
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
     * Retorna o esquema JSON com as propriedades e controles suportados pelo bloco.
     * @return array<string, mixed>
     */
    public function getSchema(): array;
}
