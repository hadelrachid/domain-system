<?php

/**
 * Domain-System Helper Functions
 * (Inspirado no WordPress e Laravel)
 */

if (!function_exists('add_shortcode')) {
    /**
     * Registra um novo shortcode.
     *
     * @param string $tag A tag do shortcode (ex: 'agendamento_form')
     * @param callable $callback A função que irá processar e retornar o HTML
     * @param string $description Descrição do que o shortcode faz
     * @param array $attributes Atributos que ele aceita
     */
    function add_shortcode(string $tag, $callback, string $description = '', array $attributes = []): void
    {
        $app = \DomainSystem\Core\Application::getInstance();
        if ($app) {
            $app->getShortcodeManager()->add($tag, $callback, $description, $attributes);
        }
    }
}

if (!function_exists('do_shortcode')) {
    /**
     * Processa uma string procurando e executando os shortcodes registrados.
     *
     * @param string $content Conteúdo bruto, geralmente HTML
     * @return string O conteúdo com os shortcodes processados
     */
    function do_shortcode(string $content): string
    {
        $app = \DomainSystem\Core\Application::getInstance();
        if ($app) {
            return $app->getShortcodeManager()->parse($content);
        }
        return $content; // Se não tiver kernel, retorna sem processar
    }
}
