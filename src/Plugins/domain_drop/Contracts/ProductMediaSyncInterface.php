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
     * Varre os produtos do catálogo com identificador oficial e sincroniza as imagens reais de cada um.
     * Por padrão ($skipIfAlreadyMultiple = true), produtos que já possuem galeria completa (> 1 imagem)
     * são ignorados para economizar requisições e evitar sobrecarga de rede.
     * 
     * @param bool $skipIfAlreadyMultiple Se true, pula produtos que já têm mais de 1 imagem
     * @return array{total: int, checked: int, skipped_already_multiple: int, synced: int, failed: int, errors: array<int, string>}
     */
    public function syncAllCatalogMedia(bool $skipIfAlreadyMultiple = true): array;

    /**
     * Sincroniza exclusivamente os produtos que possuem galeria incompleta (apenas 1 imagem ou vazia).
     *
     * @param int $limit Limite máximo de produtos por execução em lote
     * @return array{total_checked: int, needed_sync: int, synced: int, failed: int, errors: array<int, string>}
     */
    public function syncMissingGalleries(int $limit = 50): array;
}
