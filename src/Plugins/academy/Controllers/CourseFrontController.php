<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\EnvSiteState;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Services\CourseCatalogRenderer;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;

class CourseFrontController
{
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private CourseNavigationInterface $navService;
    private CourseCatalogRenderer $catalogRenderer;
    private ?SeoManagerInterface $seo;
    private ?ThemeManagerInterface $themeManager;
    private LessonBlockRenderer $blockRenderer;

    public function __construct(
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        CourseNavigationInterface $navService,
        CourseCatalogRenderer $catalogRenderer,
        ?SeoManagerInterface $seo = null,
        ?ThemeManagerInterface $themeManager = null,
        ?LessonBlockRenderer $blockRenderer = null
    ) {
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->navService = $navService;
        $this->catalogRenderer = $catalogRenderer;
        $this->seo = $seo;
        $this->themeManager = $themeManager;
        $this->blockRenderer = $blockRenderer ?? new LessonBlockRenderer();
    }

    /**
     * Catálogo geral de cursos: GET /cursos
     */
    public function index(Request $request): Response
    {
        if ($this->seo !== null) {
            $this->seo->setTitle('Cursos & Tutoriais | Rachid')
                ->setDescription('Explore os cursos e tutoriais de arquitetura de software, PHP e engenharia de sistemas.');
        }

        $catalogHtml = $this->catalogRenderer->render();

        $themePath = $this->resolveActiveThemePath();
        $academyTemplateCandidate = $themePath . '/templates/academy.php';

        if ($this->themeManager !== null && file_exists($academyTemplateCandidate)) {
            try {
                $html = $this->themeManager->render('templates/academy', [
                    'page' => [
                        'title'   => 'Cursos & Tutoriais',
                        'content' => $catalogHtml,
                    ],
                ]);
                return new Response($html);
            } catch (\Throwable $e) {
                // Fallback para renderização direta
            }
        }

        $html = "
        <section class='ds-academy-catalog-section' style='padding: 60px 20px; min-height: 70vh;'>
            <div class='container' style='max-width: 1200px; margin: 0 auto;'>
                <header style='text-align: center; margin-bottom: 50px;'>
                    <div style='display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary, #45f3ff); padding: 0.5rem 1.2rem; border-radius: 50px; font-size: 0.8rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid rgba(69, 243, 255, 0.3); margin-bottom: 1.5rem;'>
                        Academy
                    </div>
                    <h1 style='font-size: 3rem; font-weight: 800; margin-bottom: 1rem; color: #fff; letter-spacing: -1.5px;'>
                        Cursos &amp; Tutoriais
                    </h1>
                    <p style='font-size: 1.15rem; color: var(--text-muted, #8c8f94); max-width: 680px; margin: 0 auto; line-height: 1.7;'>
                        Aprenda engenharia de software avançada, padrões arquiteturais e práticas modernas de desenvolvimento.
                    </p>
                </header>
                {$catalogHtml}
            </div>
        </section>";

        return new Response($html);
    }

    /**
     * Página de apresentação e índice do curso: GET /cursos/{courseSlug}
     */
    public function showCourse(string $courseSlug): Response
    {
        $course = $this->courseRepo->findBySlug($courseSlug);
        if (!$course || ($course['status'] ?? '') === 'draft') {
            return new Response("<h1>404 - Curso não encontrado</h1><p>Não encontramos o curso solicitado: [" . htmlspecialchars($courseSlug, ENT_QUOTES, 'UTF-8') . "]</p>", 404);
        }

        $title = htmlspecialchars((string)$course['title'], ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars((string)($course['description'] ?? ''), ENT_QUOTES, 'UTF-8');

        if ($this->seo !== null) {
            $this->seo->setTitle($title . ' | Academy Rachid')
                ->setDescription(mb_substr(strip_tags((string)($course['description'] ?? '')), 0, 150) . '...');
        }

        $navHtml = $this->navService->renderCourseNav($courseSlug);
        $tree = $this->navService->getCourseTree($courseSlug);

        // Identifica a primeira aula para o botão "Começar Agora" apenas se o curso estiver publicado
        $firstLessonUrl = null;
        if (($course['status'] ?? '') === 'published') {
            foreach ($tree as $mod) {
                if (!empty($mod['lessons'])) {
                    $firstLessonUrl = $mod['lessons'][0]['url'] ?? null;
                    break;
                }
            }
        }

        $ctaButton = '';
        if ($firstLessonUrl) {
            $ctaButton = "<a href='{$firstLessonUrl}' class='btn btn-primary' style='display:inline-block; margin-top:20px; text-decoration:none;'>🚀 Começar Agora &rarr;</a>";
        }

        $html = "
        <section class='ds-academy-course-overview' style='padding: 60px 20px; min-height: 75vh;'>
            <div class='container' style='max-width: 1100px; margin: 0 auto;'>
                <div class='glass-panel' style='padding: 40px; margin-bottom: 40px; border: 1px solid rgba(69, 243, 255, 0.2);'>
                    <div style='display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary, #45f3ff); padding: 0.35rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 1rem;'>
                        Curso Academy
                    </div>
                    <h1 style='color: #fff; font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;'>{$title}</h1>
                    <p style='color: var(--text-muted, #8c8f94); font-size: 1.15rem; line-height: 1.7; max-width: 800px; margin-bottom: 20px;'>{$desc}</p>
                    {$ctaButton}
                </div>

                <div class='glass-panel' style='padding: 30px; border: 1px solid rgba(69, 243, 255, 0.15);'>
                    <h3 style='margin-bottom: 20px; color: #fff; font-size: 1.4rem;'>Índice de Módulos &amp; Aulas</h3>
                    {$navHtml}
                </div>
            </div>
        </section>";

        return new Response($html);
    }

    /**
     * Exibição de aula específica com sidebar navegável: GET /cursos/{courseSlug}/{lessonSlug}
     */
    public function showLesson(string $courseSlug, string $lessonSlug): Response
    {
        $course = $this->courseRepo->findBySlug($courseSlug);
        if (!$course) {
            return new Response("<h1>404 - Curso não encontrado</h1>", 404);
        }
        if (($course['status'] ?? '') !== 'published') {
            $statusLabel = ($course['status'] ?? '') === 'soon' ? 'Em Breve' : 'Rascunho';
            return new Response(
                "<h1>404 - Curso não encontrado</h1>" .
                "<p>O curso <strong>" . htmlspecialchars((string)$course['title'], ENT_QUOTES, 'UTF-8') . "</strong> está com o status <strong>{$statusLabel}</strong>. Altere o status do curso para <strong>Publicado</strong> no painel Academy para liberar o acesso às aulas.</p>",
                404
            );
        }

        $lesson = $this->structureRepo->getPublishedLessonBySlug((int)$course['id'], $lessonSlug);
        if (!$lesson || ($lesson['status'] ?? 'published') !== 'published') {
            return new Response("<h1>404 - Aula não encontrada</h1><p>A aula solicitada não existe ou está em rascunho neste curso.</p>", 404);
        }

        $lessonTitle = htmlspecialchars((string)$lesson['title'], ENT_QUOTES, 'UTF-8');
        $courseTitle = htmlspecialchars((string)$course['title'], ENT_QUOTES, 'UTF-8');

        if ($this->seo !== null) {
            $plainDesc = $this->blockRenderer->extractPlainText((string)($lesson['content'] ?? ''));
            if ($plainDesc === '') {
                $plainDesc = strip_tags((string)($course['description'] ?? ''));
            }
            $this->seo->setTitle($lessonTitle . ' - ' . $courseTitle . ' | Academy Rachid')
                ->setDescription(mb_substr($plainDesc, 0, 150) . '...');
        }

        $navHtml = $this->navService->renderCourseNav($courseSlug, $lessonSlug);
        $adjacent = $this->navService->getAdjacentLessons($courseSlug, $lessonSlug);

        // Resolução híbrida do conteúdo da aula (Arquivo de Tema vs Editor em Blocos / Conteúdo)
        $bodyContent = '';
        $themeFile = $lesson['source'] ?? $lesson['theme_file'] ?? null;
        if (!empty($themeFile) && ($lesson['source_type'] ?? '') !== 'content') {
            $bodyContent = $this->renderThemeFile((string)$themeFile, [
                'course'   => $course,
                'lesson'   => $lesson,
                'adjacent' => $adjacent,
                'navHtml'  => $navHtml,
            ]);
        } elseif (!empty($lesson['content'])) {
            $bodyContent = $this->blockRenderer->render((string)$lesson['content']);
        } else {
            $bodyContent = "<div class='ds-academy-empty-lesson' style='padding:40px; text-align:center; color:var(--text-muted);'><p>O conteúdo desta aula está sendo preparado.</p></div>";
        }

        // Paginação (Anterior / Próxima)
        $paginationHtml = $this->renderPagination($adjacent);

        $courseUrl = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/cursos/' . urlencode($courseSlug);

        $html = "
        <section class='ds-academy-classroom' style='padding: 40px 20px; min-height: 80vh;'>
            <div class='container' style='max-width: 1300px; margin: 0 auto;'>
                
                <!-- Barra Mobile Interativa de Índice de Aulas (visível apenas em telas <= 900px) -->
                <div class='ds-academy-mobile-bar'>
                    <button type='button' class='ds-academy-drawer-toggle' onclick='window.toggleAcademyMobileDrawer(true)' aria-label='Abrir Índice de Aulas'>
                        <span>📚 Índice de Módulos &amp; Aulas</span>
                        <span class='ds-drawer-pill'>Ver Aulas &#9776;</span>
                    </button>
                </div>

                <!-- Backdrop do Drawer Mobile -->
                <div id='ds-academy-drawer-backdrop' class='ds-academy-drawer-backdrop' onclick='window.toggleAcademyMobileDrawer(false)'></div>

                <div class='ds-academy-grid' style='display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start;'>
                    
                    <!-- Sidebar / Drawer com Navegação do Curso -->
                    <aside id='ds-academy-sidebar' class='ds-academy-sidebar glass-panel' style='padding: 24px; position: sticky; top: 100px; max-height: calc(100vh - 120px); overflow-y: auto;'>
                        <div class='ds-academy-drawer-close-bar'>
                            <span style='font-size:0.8rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--primary, #45f3ff);'>Navegação do Curso</span>
                            <button type='button' onclick='window.toggleAcademyMobileDrawer(false)' class='ds-academy-drawer-close-btn' aria-label='Fechar menu'>✕</button>
                        </div>
                        {$navHtml}
                    </aside>

                    <!-- Conteúdo Principal da Aula -->
                    <article class='ds-academy-main-article glass-panel' style='padding: 40px; min-width: 0;'>
                        <header style='margin-bottom: 30px; border-bottom: 1px solid rgba(69, 243, 255, 0.15); padding-bottom: 20px;'>
                            <div style='font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;'>
                                <a href='{$courseUrl}' style='color: var(--primary, #45f3ff); text-decoration: none;'>&larr; {$courseTitle}</a>
                            </div>
                            <h1 class='ds-academy-lesson-h1' style='color: #fff; font-size: 2.2rem; font-weight: 800; margin: 0;'>{$lessonTitle}</h1>
                        </header>

                        <div class='ds-academy-lesson-body' style='line-height: 1.8; color: var(--text-main, #c5c6c7);'>
                            {$bodyContent}
                        </div>

                        {$paginationHtml}
                    </article>

                </div>

            </div>
        </section>

        <script>
            window.toggleAcademyMobileDrawer = function(open) {
                var sidebar = document.getElementById('ds-academy-sidebar');
                var backdrop = document.getElementById('ds-academy-drawer-backdrop');
                if (!sidebar || !backdrop) return;
                if (open) {
                    sidebar.classList.add('is-open');
                    backdrop.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                } else {
                    sidebar.classList.remove('is-open');
                    backdrop.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
            };
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') window.toggleAcademyMobileDrawer(false);
            });
        </script>

        <style>
            .ds-academy-mobile-bar {
                display: none;
                margin-bottom: 18px;
            }
            .ds-academy-drawer-toggle {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 13px 18px;
                border-radius: 10px;
                background: rgba(20, 25, 28, 0.92);
                border: 1px solid rgba(69, 243, 255, 0.3);
                color: #fff;
                font-weight: 700;
                font-size: 0.95rem;
                cursor: pointer;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
            }
            .ds-drawer-pill {
                background: rgba(69, 243, 255, 0.15);
                color: var(--primary, #45f3ff);
                padding: 4px 10px;
                border-radius: 50px;
                font-size: 0.78rem;
                font-weight: 800;
            }
            .ds-academy-drawer-backdrop {
                display: none;
            }
            .ds-academy-drawer-close-bar {
                display: none;
                justify-content: space-between;
                align-items: center;
                padding-bottom: 14px;
                margin-bottom: 14px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }
            .ds-academy-drawer-close-btn {
                background: rgba(255, 255, 255, 0.08);
                border: none;
                color: #fff;
                width: 32px;
                height: 32px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 1rem;
            }
            .course-nav {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .course-nav-header {
                font-weight: 800;
                font-size: 1.1rem;
                padding-bottom: 12px;
                border-bottom: 1px solid rgba(69, 243, 255, 0.15);
            }
            .course-nav-home-link {
                color: #fff;
                text-decoration: none;
                transition: color 0.2s;
            }
            .course-nav-home-link:hover {
                color: var(--primary, #45f3ff);
            }
            .course-nav-module-title {
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: var(--primary, #45f3ff);
                margin: 12px 0 8px 0;
            }
            .course-nav-lessons {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .course-nav-lesson-link {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                border-radius: 6px;
                text-decoration: none;
                color: var(--text-muted, #8c8f94);
                font-size: 0.92rem;
                transition: all 0.2s;
            }
            .course-nav-lesson-link:hover {
                background: rgba(69, 243, 255, 0.08);
                color: #fff;
            }
            .course-nav-lesson-item.is-active .course-nav-lesson-link {
                background: rgba(69, 243, 255, 0.15);
                color: var(--primary, #45f3ff);
                font-weight: 600;
                border-left: 3px solid var(--primary, #45f3ff);
            }
            .lesson-index {
                font-family: monospace;
                font-size: 0.8rem;
                opacity: 0.7;
            }
            @media (max-width: 900px) {
                .ds-academy-classroom {
                    padding: 20px 12px !important;
                }
                .ds-academy-mobile-bar {
                    display: block;
                    position: sticky;
                    top: 72px;
                    z-index: 80;
                }
                .ds-academy-grid {
                    grid-template-columns: 1fr !important;
                }
                .ds-academy-sidebar {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    bottom: 0 !important;
                    width: 84vw !important;
                    max-width: 340px !important;
                    max-height: 100vh !important;
                    z-index: 1200 !important;
                    border-radius: 0 16px 16px 0 !important;
                    background: #11161b !important;
                    transform: translateX(-105%);
                    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
                    box-shadow: 15px 0 40px rgba(0, 0, 0, 0.75);
                }
                .ds-academy-sidebar.is-open {
                    transform: translateX(0);
                }
                .ds-academy-drawer-backdrop.is-open {
                    display: block;
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.65);
                    backdrop-filter: blur(4px);
                    z-index: 1150;
                }
                .ds-academy-drawer-close-bar {
                    display: flex;
                }
                .ds-academy-main-article {
                    padding: 22px 16px !important;
                }
                .ds-academy-lesson-h1 {
                    font-size: 1.55rem !important;
                }
                .ds-academy-pagination {
                    flex-direction: column !important;
                }
                .ds-academy-pagination > div,
                .ds-academy-pagination a {
                    width: 100% !important;
                    justify-content: center !important;
                    box-sizing: border-box !important;
                }
            }
        </style>";

        return new Response($html);
    }

    private function renderPagination(array $adjacent): string
    {
        $prev = $adjacent['prev'] ?? null;
        $next = $adjacent['next'] ?? null;

        if (!$prev && !$next) {
            return '';
        }

        $prevHtml = '';
        if ($prev) {
            $prevTitle = htmlspecialchars((string)$prev['title'], ENT_QUOTES, 'UTF-8');
            $prevUrl = htmlspecialchars((string)$prev['url'], ENT_QUOTES, 'UTF-8');
            $prevHtml = "<a href='{$prevUrl}' class='btn btn-outline' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px;'>
                &larr; Anterior: {$prevTitle}
            </a>";
        }

        $nextHtml = '';
        if ($next) {
            $nextTitle = htmlspecialchars((string)$next['title'], ENT_QUOTES, 'UTF-8');
            $nextUrl = htmlspecialchars((string)$next['url'], ENT_QUOTES, 'UTF-8');
            $nextHtml = "<a href='{$nextUrl}' class='btn btn-primary' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px;'>
                Próxima Aula: {$nextTitle} &rarr;
            </a>";
        }

        return "
        <nav class='ds-academy-pagination' aria-label='Navegação entre Aulas' style='display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-top:50px; padding-top:30px; border-top:1px solid rgba(69, 243, 255, 0.15);'>
            <div>{$prevHtml}</div>
            <div>{$nextHtml}</div>
        </nav>";
    }

    private function renderThemeFile(string $themeFile, array $context): string
    {
        $themePath = $this->resolveActiveThemePath();
        $target = $themePath . '/' . ltrim($themeFile, '/\\');

        $realTarget = realpath($target);
        $realTheme = realpath($themePath);
        $realThemePrefix = $realTheme !== false ? rtrim($realTheme, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';

        if ($realTarget === false || $realTheme === false || !str_starts_with($realTarget, $realThemePrefix) || !is_file($realTarget)) {
            return "<div class='alert alert-warning' style='padding:20px; color:#ffb703; background:rgba(255, 183, 3, 0.1); border-radius:8px;'>Arquivo do tema não encontrado ou inacessível: " . htmlspecialchars($themeFile, ENT_QUOTES, 'UTF-8') . "</div>";
        }

        ob_start();
        extract($context, EXTR_SKIP);
        include $realTarget;
        return (string)ob_get_clean();
    }

    private function resolveActiveThemePath(): string
    {
        if ($this->themeManager !== null) {
            $activePath = $this->themeManager->getActiveThemePath();
            if (!empty($activePath) && is_dir($activePath)) {
                return $activePath;
            }
        }

        $baseRoot = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);
        $resolver = new SafeThemeResolver([
            $baseRoot . '/themes',
            $baseRoot . '/src/Plugins/flextheme/themes',
        ]);

        $siteState = new EnvSiteState();
        return $resolver->resolveThemePath($siteState->getActiveTheme());
    }
}
