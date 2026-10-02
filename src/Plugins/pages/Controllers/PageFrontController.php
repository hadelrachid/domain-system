<?php

namespace DomainSystem\Plugins\pages\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;

class PageFrontController
{
    private PageRepositoryInterface $pageRepo;
    private ShortcodeManager $shortcodes;
    private SeoManagerInterface $seo;
    private ThemeManagerInterface $themeManager;

    public function __construct(PageRepositoryInterface $pageRepo, ShortcodeManager $shortcodes, SeoManagerInterface $seo, ThemeManagerInterface $themeManager)
    {
        $this->pageRepo = $pageRepo;
        $this->shortcodes = $shortcodes;
        $this->seo = $seo;
        $this->themeManager = $themeManager;
    }

    public function show(string $slug)
    {
        $page = $this->pageRepo->findBySlug($slug);

        if (!$page) {
            return new \DomainSystem\Core\Http\Response("<h1>404 - Página não encontrada</h1>", 404);
        }

        $this->seo->setTitle($page['title'] . ' | Rachid');
        $this->seo->setDescription(mb_substr(strip_tags($page['content'] ?? ''), 0, 150) . '...');

        // Se a página tem um template PHP ou página estática linkada
        if (!empty($page['theme']) && !empty($page['template_file'])) {
            $themeRoot = DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes/' . basename($page['theme']);
            $templateFile = $page['template_file'];
            
            // Backward compatibility: se não tiver prefixo, assume templates/
            if (!str_starts_with($templateFile, 'templates/') && !str_starts_with($templateFile, 'pages/')) {
                $templateFile = 'templates/' . $templateFile;
            }

            if (str_starts_with($templateFile, 'pages/')) {
                // MODO PÁGINA ESTÁTICA (Bypassa o renderizador de templates)
                $filePath = $themeRoot . '/' . $templateFile;
                $realPath = realpath($filePath);
                $realThemeRoot = realpath($themeRoot);
                
                if ($realPath === false || $realThemeRoot === false || !str_starts_with($realPath, $realThemeRoot)) {
                    return new \DomainSystem\Core\Http\Response("<h1>403 - Tentativa de acesso inválido</h1>", 403);
                }
                
                if (!file_exists($realPath)) {
                    return new \DomainSystem\Core\Http\Response("<h1>404 - Arquivo estático não encontrado no tema</h1>", 404);
                }
                
                ob_start();
                extract(['page' => $page], EXTR_SKIP);
                include $realPath;
                $html = ob_get_clean();
            } else {
                // MODO TEMPLATE (Usa o ThemeManager)
                // Remove o prefixo 'templates/' e '.php' para o render()
                $templateName = str_replace('templates/', '', preg_replace('/\.php$/', '', $templateFile));
                
                // Força o ThemeManager a procurar na pasta templates/ do tema selecionado
                $html = $this->themeManager->render($templateName, ['page' => $page], $themeRoot . '/templates');
            }
        } else {
            // Renderiza o fallback de conteúdo de banco de dados
            $safeTitle = htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8');
            $html = "<div class='container' style='padding: 60px 20px; min-height: 70vh;'>
                        <article class='glass-panel' style='padding: 40px; margin-top: 20px;'>
                            <h1 class='text-primary' style='font-size: 2.5rem; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 20px; margin-bottom: 30px;'>{$safeTitle}</h1>
                            <div class='page-content' style='font-size: 1.1rem; line-height: 1.8; color: var(--text-main);'>
                                {$page['content']}
                            </div>
                        </article>
                     </div>";
        }

        // Processa os shortcodes no HTML final!
        $html = $this->shortcodes->parse($html);

        return new \DomainSystem\Core\Http\Response($html);
    }
}
