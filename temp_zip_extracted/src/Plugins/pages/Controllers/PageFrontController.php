<?php

namespace DomainSystem\Plugins\pages\Controllers;

use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;
use DomainSystem\Core\Theme\ShortcodeManager;

class PageFrontController
{
    private PageRepositoryInterface $pageRepo;
    private ShortcodeManager $shortcodes;
    private \DomainSystem\Plugins\seo\Contracts\SeoManagerInterface $seo;

    public function __construct(PageRepositoryInterface $pageRepo, ShortcodeManager $shortcodes, \DomainSystem\Plugins\seo\Contracts\SeoManagerInterface $seo)
    {
        $this->pageRepo = $pageRepo;
        $this->shortcodes = $shortcodes;
        $this->seo = $seo;
    }

    public function show(string $slug)
    {
        $page = $this->pageRepo->findBySlug(escapeshellcmd($slug));

        if (!$page) {
            return new \DomainSystem\Core\Http\Response("<h1>404 - Página não encontrada</h1>", 404);
        }

        $this->seo->setTitle($page['title'] . ' | Rachid');
        $this->seo->setDescription(mb_substr(strip_tags($page['content']), 0, 150) . '...');

        // Gera o fragmento HTML (O SeoMiddleware envolverá isso no layout do tema atual)
        $html = "<div class='container' style='padding: 60px 20px; min-height: 70vh;'>
                    <article class='glass-panel' style='padding: 40px; margin-top: 20px;'>
                        <h1 class='text-primary' style='font-size: 2.5rem; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 20px; margin-bottom: 30px;'>{$page['title']}</h1>
                        <div class='page-content' style='font-size: 1.1rem; line-height: 1.8; color: var(--text-main);'>
                            {$page['content']}
                        </div>
                    </article>
                 </div>";

        // Aqui é o grande truque do CMS: Ele processa os shortcodes no HTML final!
        $html = $this->shortcodes->parse($html);

        return new \DomainSystem\Core\Http\Response($html);
    }
}
