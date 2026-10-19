<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Contracts;

/**
 * Contrato oficial de todos os Widgets do Builder Flex.
 *
 * Um Widget produz uma estrutura de dados (DTO) via `toDto()`, que é
 * consumida pelo `DtoRenderer` da camada de apresentação.
 */
interface WidgetInterface
{
    public function getName(): string;
    public function getShortcodeTag(): string;
    public function getControls(): array;

    /**
     * Retorna a estrutura de dados (DTO) do widget.
     *
     * @param array $attributes Atributos do widget (props visuais, CSS, etc.)
     * @param array $children   Array de DTOs dos widgets filhos (para containers)
     * @return array DTO estruturado: ['type' => string, 'tag' => string, 'attrs' => array, 'styles' => array, 'content' => string, 'children' => array]
     */
    public function toDto(array $attributes, array $children = []): array;

    public function render(array $attributes, string $content = ''): string;
}
