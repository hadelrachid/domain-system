<?php

namespace DomainSystem\Core\Contracts;

interface ThemeManagerInterface
{
    public function setActiveThemePath(string $path): void;
    public function getActiveThemePath(): string;
    public function render(string $template, array $args = [], ?string $pluginViewsDir = null): string;
    public function get_header(array $args = []): string;
    public function get_footer(array $args = []): string;
    
    /**
     * Retorna a lista de temas válidos que respeitam o Contrato OS 2.0 (possuem theme.json, assets, templates)
     * @return array
     */
    public function getAvailableThemes(): array;

    /**
     * Retorna a lista de arquivos de template PHP nativos disponíveis em um tema.
     * @param string|null $themeName Se nulo, usa o tema ativo.
     * @return array
     */
    public function getAvailableTemplates(?string $themeName = null): array;
}
