<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\pages\Controllers;

use DomainSystem\Core\Contracts\PageTreeRendererInterface;
use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\pages\Contracts\PageRepositoryInterface;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;

class PageFrontController
{
    private PageRepositoryInterface $pageRepo;
    private ShortcodeManager $shortcodes;
    private SeoManagerInterface $seo;
    private ThemeManagerInterface $themeManager;
    private ?PageTreeRendererInterface $treeRenderer;
    private ?SiteStateInterface $siteState;

    public function __construct(
        PageRepositoryInterface $pageRepo,
        ShortcodeManager $shortcodes,
        SeoManagerInterface $seo,
        ThemeManagerInterface $themeManager,
        ?PageTreeRendererInterface $treeRenderer = null,
        ?SiteStateInterface $siteState = null
    ) {
        $this->pageRepo = $pageRepo;
        $this->shortcodes = $shortcodes;
        $this->seo = $seo;
        $this->themeManager = $themeManager;
        $this->treeRenderer = $treeRenderer;
        $this->siteState = $siteState;
    }

    public function show(string $slug)
    {
        $page = $this->pageRepo->findBySlug($slug);

        if (!$page) {
            return new Response("<h1>404 - Página não encontrada</h1><p>Buscando pelo slug: [" . htmlspecialchars($slug) . "]</p>", 404);
        }

        $this->seo->setTitle($page['title'] . ' | Rachid');
        $this->seo->setDescription(mb_substr(strip_tags($page['content'] ?? ''), 0, 150) . '...');

        $html = null;

        // Se a página tem um template PHP ou página estática linkada
        if (!empty($page['theme']) && !empty($page['template_file'])) {
            $themesBaseDir = DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes';
            $resolver = new SafeThemeResolver($themesBaseDir);
            $fallbackThemeRoot = $resolver->resolveThemePath((string)$page['theme']);
            $activeThemePath = $this->themeManager->getActiveThemePath();

            $candidateThemeRoots = [];
            if ($this->siteState !== null) {
                $siteThemeName = trim($this->siteState->getActiveTheme());
                if ($siteThemeName !== '' && $resolver->themeExists($siteThemeName)) {
                    $siteRealRoot = realpath($resolver->resolveThemePath($siteThemeName));
                    if ($siteRealRoot !== false && is_dir($siteRealRoot)) {
                        $candidateThemeRoots[] = $siteRealRoot;
                    }
                }
            }

            $activeRealRoot = ($activeThemePath !== '') ? realpath($activeThemePath) : false;
            if ($activeRealRoot !== false && is_dir($activeRealRoot) && !in_array($activeRealRoot, $candidateThemeRoots, true)) {
                $candidateThemeRoots[] = $activeRealRoot;
            }
            $fallbackRealRoot = ($fallbackThemeRoot !== '') ? realpath($fallbackThemeRoot) : false;
            if ($fallbackRealRoot !== false && is_dir($fallbackRealRoot) && !in_array($fallbackRealRoot, $candidateThemeRoots, true)) {
                $candidateThemeRoots[] = $fallbackRealRoot;
            }

            $templateFile = $page['template_file'];

            // Backward compatibility: se não tiver prefixo, assume templates/
            if (!str_starts_with($templateFile, 'templates/') && !str_starts_with($templateFile, 'pages/')) {
                $templateFile = 'templates/' . $templateFile;
            }

            if (str_starts_with($templateFile, 'pages/')) {
                // MODO PÁGINA ESTÁTICA (Bypassa o renderizador de templates)
                $matchedFile = null;
                $matchedRoot = null;
                foreach ($candidateThemeRoots as $candidateRoot) {
                    $filePath = $candidateRoot . '/' . $templateFile;
                    if (file_exists($filePath)) {
                        $matchedFile = $filePath;
                        $matchedRoot = $candidateRoot;
                        break;
                    }
                }

                if ($matchedFile === null || $matchedRoot === null) {
                    return new Response("<h1>404 - Arquivo estático não encontrado no tema</h1>", 404);
                }

                $realPath = realpath($matchedFile);
                if (
                    $realPath === false
                    || !str_starts_with($realPath, rtrim($matchedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                ) {
                    return new Response("<h1>403 - Tentativa de acesso inválido</h1>", 403);
                }

                ob_start();
                extract(['page' => $page], EXTR_SKIP);
                include $realPath;
                $html = ob_get_clean();
            } else {
                // MODO TEMPLATE (Usa o ThemeManager, priorizando override do tema ativo)
                $templateName = str_replace('templates/', '', preg_replace('/\.php$/', '', $templateFile));
                $safeTemplateFile = basename($templateName) . '.php';

                foreach ($candidateThemeRoots as $candidateRoot) {
                    $templatesDir = $candidateRoot . '/templates';
                    $candidateFile = $templatesDir . '/' . $safeTemplateFile;
                    $realCandidate = file_exists($candidateFile) ? realpath($candidateFile) : false;

                    if (
                        $realCandidate !== false
                        && str_starts_with($realCandidate, rtrim($candidateRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                    ) {
                        try {
                            $html = $this->themeManager->render($templateName, ['page' => $page], $templatesDir);
                        } catch (\Throwable $e) {
                            $html = null;
                        }
                        if ($html !== null) {
                            break;
                        }
                    }
                }
            }
        }

        if ($html === null) {
            // Renderiza o fallback de conteúdo de banco de dados
            if (!empty($page['content']) && str_starts_with(trim($page['content']), '[')) {
                $tree = json_decode($page['content'], true);
                if (is_array($tree)) {
                    if ($this->treeRenderer !== null) {
                        $html = $this->treeRenderer->renderTree($tree);
                    } else {
                        $html = $page['content'];
                    }
                } else {
                    $html = $page['content'];
                }
            } else {
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
        }

        // Processa os shortcodes no HTML final!
        $html = $this->shortcodes->parse($html);

        return new Response($html);
    }
}
