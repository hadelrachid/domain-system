<?php

namespace DomainSystem\Core\Theme;

use DomainSystem\Core\Contracts\NavigationManagerInterface;

class NavigationManager implements NavigationManagerInterface
{
    private array $locations = [];

    public function registerLocation(string $locationId, string $description): void
    {
        $this->locations[$locationId] = $description;
    }

    public function getRegisteredLocations(): array
    {
        return $this->locations;
    }

    public function getMenuItems(string $locationId): array
    {
        // Aqui o futuro Builder-Flex interceptará ou injetará os itens do banco de dados.
        // Como o Builder ainda não assumiu, retornamos um array vazio ou fallback.
        return [];
    }

    public function renderMenu(string $locationId, array $options = []): string
    {
        $items = $this->getMenuItems($locationId);
        
        // Fallback nativo: Se o Builder-Flex não preencheu este menu no BD, mostramos um aviso visual
        if (empty($items)) {
            if (isset($options['fallback_html'])) {
                return $options['fallback_html'];
            }
            return '<!-- Menu Slot Vazio: ' . htmlspecialchars($locationId) . ' (Aguardando Builder-Flex) -->';
        }

        $listClass = $options['list_class'] ?? 'nav-list';
        $itemClass = $options['item_class'] ?? 'nav-item';
        $linkClass = $options['link_class'] ?? 'nav-link';

        $html = "<ul class=\"{$listClass}\">\n";
        foreach ($items as $item) {
            $url = htmlspecialchars($item['url']);
            $label = htmlspecialchars($item['label']);
            $html .= "  <li class=\"{$itemClass}\"><a href=\"{$url}\" class=\"{$linkClass}\">{$label}</a></li>\n";
        }
        $html .= "</ul>\n";

        return $html;
    }
}
