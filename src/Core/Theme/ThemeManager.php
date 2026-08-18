<?php

namespace DomainSystem\Core\Theme;

use Exception;

class ThemeManager
{
    private string $activeThemePath;

    public function __construct(string $activeThemePath)
    {
        $this->activeThemePath = rtrim($activeThemePath, '/\\');
    }

    /**
     * Renders a specific template file from the active theme.
     * Extracts variables so they are available in the template scope.
     * 
     * @param string $template The name of the template file (without .php)
     * @param array $args Data to be passed to the template
     * @return string The rendered HTML
     * @throws Exception If template not found
     */
    public function render(string $template, array $args = []): string
    {
        $file = $this->activeThemePath . '/' . $template . '.php';

        if (!file_exists($file)) {
            throw new Exception("Template '{$template}' not found in theme.");
        }

        // Extract variables to the current scope
        extract($args);

        // Start output buffering
        ob_start();
        
        // Include the file (it will use the variables extracted and $this)
        include $file;
        
        // Return the buffered content
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
