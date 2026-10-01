<?php

namespace DomainSystem\Core\Contracts;

/**
 * Interface NavigationManagerInterface
 * 
 * Contrato estrito para gerenciamento de menus e navegação no Domain-System OS 2.0.
 * Este contrato garante que desenvolvedores de Temas não façam hardcode de URLs (gambiarras),
 * forçando-os a solicitar a renderização de menus através deste gerenciador. O Builder-Flex
 * será o responsável por fornecer os dados dinâmicos dos menus pelo banco de dados.
 */
interface NavigationManagerInterface
{
    /**
     * Registra um local de menu (slot) que o tema suporta.
     * Exemplo: 'header_primary' => 'Menu Principal do Topo'
     */
    public function registerLocation(string $locationId, string $description): void;

    /**
     * Retorna todos os locais de menu registrados pelo Tema ativo.
     * @return array
     */
    public function getRegisteredLocations(): array;

    /**
     * Retorna os itens (links, páginas, custom) assinalados a um local específico.
     * @param string $locationId
     * @return array
     */
    public function getMenuItems(string $locationId): array;

    /**
     * Renderiza o HTML final de um menu baseado no seu Local ID.
     * 
     * @param string $locationId O ID do slot do menu (ex: 'header_primary').
     * @param array $options Opções de renderização (classes CSS customizadas, wrappers, etc).
     * @return string O HTML do menu montado e blindado.
     */
    public function renderMenu(string $locationId, array $options = []): string;
}
