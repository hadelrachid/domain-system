<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Contracts;

/**
 * Contrato para descoberta e resolução de temas instalados, ativos e seus templates.
 */
interface ThemeDiscoveryServiceInterface
{
    /**
     * Descobre os temas instalados no sistema (raiz /themes e plugins).
     *
     * @return array<int, array<string, string>>
     */
    public function getAvailableThemes(): array;

    /**
     * Obtém o identificador da pasta do tema ativo no sistema.
     */
    public function getActiveThemeFolder(): string;

    /**
     * Descobre os arquivos de template disponíveis em um tema (ex: home.php, sobre.php).
     *
     * @return array<string, string> [slug => label]
     */
    public function getThemeTemplates(string $themeFolder): array;
}
