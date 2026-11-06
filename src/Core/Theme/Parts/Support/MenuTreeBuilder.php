<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Support;

use DomainSystem\Core\Theme\Parts\Contracts\MenuTreeBuilderInterface;

/**
 * Construtor de Árvore Hierárquica de Menus e Submenus (Walker SOLID).
 *
 * Suporta tanto hierarquia por `parent_id` (banco relacional) quanto por
 * `depth` sequencial (estilo wpNavMenu drag-and-drop) ou arrays `children` pré-aninhados.
 */
final class MenuTreeBuilder implements MenuTreeBuilderInterface
{
    public function buildTree(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        // Normaliza cada item garantindo chaves padrões
        $normalized = [];
        $hasExplicitParentIds = false;
        $hasExplicitDepths = false;

        foreach (array_values($items) as $idx => $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $id = isset($raw['id']) && (int)$raw['id'] > 0 ? (int)$raw['id'] : ($idx + 1);
            $parentId = isset($raw['parent_id']) && (int)$raw['parent_id'] > 0 ? (int)$raw['parent_id'] : null;
            $depth = isset($raw['depth']) ? max(0, (int)$raw['depth']) : 0;

            if ($parentId !== null) {
                $hasExplicitParentIds = true;
            }
            if ($depth > 0) {
                $hasExplicitDepths = true;
            }

            $children = [];
            if (!empty($raw['children']) && is_array($raw['children'])) {
                $children = $this->buildTree($raw['children']);
            }

            $normalized[] = [
                'id'          => $id,
                'parent_id'   => $parentId,
                'depth'       => $depth,
                'title'       => (string)($raw['title'] ?? ''),
                'url'         => (string)($raw['url'] ?? '#'),
                'page_id'     => !empty($raw['page_id']) ? (int)$raw['page_id'] : null,
                'target'      => (string)($raw['target'] ?? '_self'),
                'css_classes' => (string)($raw['css_classes'] ?? ''),
                'order_index' => isset($raw['order_index']) ? (int)$raw['order_index'] : $idx,
                'children'    => $children,
            ];
        }

        // Se existir parent_id explícito, monta a árvore por referência de ID pai
        if ($hasExplicitParentIds) {
            return $this->buildByParentId($normalized);
        }

        // Se existir depth sequencial (> 0), monta a árvore por pilha de profundidade (wpNavMenu)
        if ($hasExplicitDepths) {
            return $this->buildByDepthStack($normalized);
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildByParentId(array $items): array
    {
        $byId = [];
        foreach ($items as $item) {
            $byId[(int)$item['id']] = $item;
        }

        $tree = [];
        foreach ($byId as $id => &$node) {
            $parentId = $node['parent_id'];
            if ($parentId !== null && $parentId !== $id && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildByDepthStack(array $items): array
    {
        $root = [];
        /** @var array<int, array<string, mixed>> $stack */
        $stack = [];

        foreach ($items as $item) {
            $depth = (int)$item['depth'];
            if ($depth <= 0 || empty($stack)) {
                $item['depth'] = 0;
                $root[] = $item;
                $stack = [0 => &$root[ count($root) - 1 ]];
                continue;
            }

            // Garante que a profundidade não salte mais que 1 nível em relação ao último pai válido
            $parentDepth = min($depth - 1, max(array_keys($stack)));
            while (count($stack) > $parentDepth + 1) {
                array_pop($stack);
            }

            $parent = &$stack[$parentDepth];
            $parent['children'][] = $item;
            $newChildIdx = count($parent['children']) - 1;
            $stack[$parentDepth + 1] = &$parent['children'][$newChildIdx];
            unset($parent);
        }

        return $root;
    }
}
