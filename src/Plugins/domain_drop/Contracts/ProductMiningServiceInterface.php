<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Contracts;

use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;

/**
 * Contrato do Orquestrador de Mineração de Produtos (SRP / DIP).
 */
interface ProductMiningServiceInterface
{
    /**
     * Minera produtos em alta no AliExpress, executa a conversão cambial,
     * enriquece títulos e descrições com I.A. e retorna os DTOs processados.
     * 
     * @param string $category Categoria ou palavra-chave de busca
     * @param int $limit Quantidade de produtos para minerar
     * @param bool $autoPersist Se deve persistir automaticamente no catálogo
     * @return array<int, DropshipProductDto>
     */
    public function mineTrendingProducts(string $category = 'Eletrônicos', int $limit = 10, bool $autoPersist = true): array;

    /**
     * Importa um produto específico através da URL ou ID do AliExpress.
     * 
     * @param string|int $productIdOrUrl
     * @return DropshipProductDto
     */
    public function importSingleProduct(string|int $productIdOrUrl): DropshipProductDto;

    /**
     * Importa um produto a partir do código-fonte bruto (HTML ou JSON) copiado diretamente
     * do navegador autenticado do lojista, contornando bloqueios anti-robô/slider captcha.
     * Utiliza buffer transitório por item com isolamento de memória.
     * 
     * @param string $rawSource HTML completo (Ctrl+U) ou JSON do anúncio
     * @param string|null $category Categoria opcional para sobrepor a inferida
     * @return DropshipProductDto
     */
    public function importFromBrowserSource(string $rawSource, ?string $category = null): DropshipProductDto;

    /**
     * Executa o ciclo de rotação diária automatizada de 24 horas.
     * Minera produtos virais aleatórios para todas as 6 categorias oficiais:
     * - Eletrônicos & Gadget
     * - Casa Inteligente & Organização
     * - Beleza, Cuidados & Estilo
     * - Ferramentas & Utilidades
     * - Pet Shop & Pets Virais
     * - Acessórios para Veículos
     * 
     * Atualiza o catálogo, registra a data/hora de conclusão e retorna o resumo estatístico.
     * 
     * @param int $itemsPerCategory Quantidade de produtos frescos minerados por categoria
     * @return array<string, mixed> Resumo da rodada executada
     */
    public function runDailyAutomatedRotation(int $itemsPerCategory = 4): array;

    /**
     * Retorna os metadados do ciclo diário de 24 horas:
     * timestamp da última execução, próxima execução, segundos restantes, tempo formatado (HH:MM:SS),
     * status se expirado (is_due) e estatísticas de produtos por categoria.
     * 
     * @return array<string, mixed>
     */
    public function getDailyCycleInfo(): array;
}
