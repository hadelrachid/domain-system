<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;

/**
 * Contrato para o Serviço de Sincronização e Integridade de Mídia (SRP / DIP).
 * 
 * Garante que produtos salvos no catálogo nunca sofram de dessincronização entre
 * o título/metadados e as imagens reais de exibição.
 */
interface ProductMediaSyncInterface
{
    /**
     * Sincroniza em tempo real as imagens de um produto específico através da sua URL/origem oficial.
     * Atualiza o repositório e retorna o DTO atualizado.
     * 
     * @param int $productId ID interno no catálogo
     * @return DropshipProductDto
     */
    public function syncProductMedia(int $productId): DropshipProductDto;

    /**
     * Varre todos os produtos do catálogo com identificador oficial e sincroniza as imagens reais de cada um.
     * 
     * @return array{total: int, synced: int, failed: int, errors: array<int, string>}
     */
    public function syncAllCatalogMedia(): array;
}
