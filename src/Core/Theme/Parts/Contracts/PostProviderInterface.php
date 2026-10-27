<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts;

/**
 * Contrato para Provedores de Posts / Artigos de Blog (DIP / SOLID).
 * 
 * Desacopla o bloco PostCardsGridBlock da camada de persistência direta (BD, plugins, APIs).
 */
interface PostProviderInterface
{
    /**
     * Retorna a lista de artigos formatados para exibição em cards.
     * 
     * @param array<string, mixed> $options Ex: ['limit' => 6, 'category' => null, 'order' => 'DESC']
     * @return array<int, array{
     *     id: string|int,
     *     title: string,
     *     slug: string,
     *     excerpt?: string,
     *     featured_image?: string,
     *     published_at?: string,
     *     author?: string,
     *     category?: string,
     *     url?: string,
     *     reading_time?: string
     * }>
     */
    public function getPosts(array $options = []): array;
}
