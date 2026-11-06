<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Contracts;

/**
 * Contrato explícito de persistência de conteúdo de páginas para o BuilderAdminController (SOLID - DIP / ISP).
 *
 * Elimina o uso de ?object e method_exists() (duck-typing) na leitura e gravação de páginas.
 */
interface PageContentRepositoryInterface
{
    /**
     * Busca uma página pelo identificador numérico.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * Atualiza os dados (ex: 'content') de uma página existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;
}
