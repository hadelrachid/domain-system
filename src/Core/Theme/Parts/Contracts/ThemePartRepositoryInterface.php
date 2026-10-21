<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato de Repositório de Persistência de Partes de Tema.
 */
interface ThemePartRepositoryInterface
{
    /**
     * Retorna todas as partes de um determinado tipo ('header', 'footer', 'section').
     * @return ThemePartInterface[]
     */
    public function findByType(string $type): array;

    /**
     * Busca uma parte pelo seu ID único.
     */
    public function findById(string $id): ?ThemePartInterface;

    /**
     * Salva ou atualiza uma parte de tema.
     */
    public function save(ThemePartInterface $part): bool;

    /**
     * Remove uma parte de tema pelo ID.
     */
    public function delete(string $id): bool;

    /**
     * Retorna todas as partes cadastradas no sistema.
     * @return ThemePartInterface[]
     */
    public function all(): array;
}
