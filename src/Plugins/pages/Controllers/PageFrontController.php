<?php

namespace DomainSystem\Plugins\pages\Controllers;

use DomainSystem\Plugins\Database\QueryBuilder;

class PageFrontController
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    public function show(string $slug)
    {
        $page = $this->db->table('pages')->where('slug', '=', escapeshellcmd($slug))->first();

        if (!$page) {
            header("HTTP/1.0 404 Not Found");
            echo "<h1>404 - Página não encontrada</h1>";
            exit;
        }

        // Tenta carregar o template do tema (se existir). Se não, usa um fallback.
        $themeFile = \DOMAIN_SYSTEM_ROOT . '/themes/public/page.php';
        
        if (file_exists($themeFile)) {
            // Se o tema tem um template page.php, usa ele
            extract(['page' => $page]);
            
            ob_start();
            include $themeFile;
            $html = ob_get_clean();
        } else {
            // Fallback cru
            $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>{$page['title']}</title></head>
                     <body style='font-family:sans-serif; padding:40px; background:#f8fafc; color:#1e293b; max-width:800px; margin:0 auto;'>
                     <h1 style='border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>{$page['title']}</h1>
                     <div class='page-content'>{$page['content']}</div>
                     </body></html>";
        }

        // Aqui é o grande truque do CMS: Ele processa os shortcodes no HTML final!
        if (function_exists('do_shortcode')) {
            $html = do_shortcode($html);
        }

        echo $html;
    }
}
