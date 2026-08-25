<?php

namespace DomainSystem\Core\Theme;

use Exception;
use DomainSystem\Core\Events\EventDispatcher;

class ThemeManager
{
    private string $activeThemePath;
    public ?EventDispatcher $dispatcher = null;

    public function __construct(string $activeThemePath)
    {
        $this->activeThemePath = rtrim($activeThemePath, '/\\');
    }

    public function setDispatcher(EventDispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Renders a template file
     *
     * @param string $template The template name (e.g., 'admin/dashboard')
     * @param array $args Data to extract into the view
     * @param string|null $pluginViewsDir Optional fallback directory for plugin views
     * @return string The rendered HTML
     * @throws Exception If template not found
     */
    public function render(string $template, array $args = [], ?string $pluginViewsDir = null): string
    {
        $file = $this->activeThemePath . '/' . $template . '.php';

        // Tenta achar na pasta do tema primeiro
        if (!file_exists($file)) {
            // Fallback para a pasta da view do plugin (se fornecida)
            if ($pluginViewsDir !== null) {
                $file = $pluginViewsDir . '/' . basename($template) . '.php';
            }
            
            if (!file_exists($file)) {
                throw new Exception("Template '{$template}' not found in theme or plugin.");
            }
        }

        extract($args);
        
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Simulates WordPress's get_header()
     */
    public function get_header(array $args = []): string
    {
        try {
            $html = $this->render('header', $args);
            echo $html; // Echo directly when used inside templates, but also return for testing
            return $html;
        } catch (Exception $e) {
            return ''; // Fallback or empty if not found
        }
    }

    /**
     * Simulates WordPress's get_footer()
     */
    public function get_footer(array $args = []): string
    {
        try {
            $html = $this->render('footer', $args);
            echo $html;
            return $html;
        } catch (Exception $e) {
            return '';
        }
    }
}
