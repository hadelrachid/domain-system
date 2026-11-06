<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato formal do gerenciador de Shortcodes (SOLID - DIP / ISP).
 *
 * Desacopla controllers, widgets, blocos e temas da implementação concreta ShortcodeManager.
 */
interface ShortcodeManagerInterface
{
    /**
     * Registra um novo shortcode.
     *
     * @param string $tag
     * @param callable|array<int, mixed> $callback
     * @param string $description
     * @param array<string, string> $attributes
     * @param string $category
     */
    public function add(
        string $tag,
        callable|array $callback,
        string $description = '',
        array $attributes = [],
        string $category = 'Geral'
    ): void;

    /**
     * Retorna a lista de todos os shortcodes registrados e suas documentações.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRegisteredShortcodes(): array;

    /**
     * Procura shortcodes em uma string e os substitui pelo resultado de seus callbacks.
     */
    public function parse(string $content): string;
}
