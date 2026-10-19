<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\academy\Contracts\CourseCatalogRendererInterface;
use DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\LessonBlockRendererInterface;
use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;
use Throwable;

class CourseFrontController
{
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private CourseNavigationInterface $navService;
    private CourseCatalogRendererInterface $catalogRenderer;
    private ?SeoManagerInterface $seo;
    private ?ThemeManagerInterface $themeManager;
    private LessonBlockRendererInterface $blockRenderer;
    private ?ThemePageResolverInterface $pageResolver;

    public function __construct(
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        CourseNavigationInterface $navService,
        CourseCatalogRendererInterface $catalogRenderer,
        ?SeoManagerInterface $seo = null,
        ?ThemeManagerInterface $themeManager = null,
        LessonBlockRendererInterface $blockRenderer = new LessonBlockRenderer(),
        ?ThemePageResolverInterface $pageResolver = null
    ) {
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->navService = $navService;
        $this->catalogRenderer = $catalogRenderer;
        $this->seo = $seo;
        $this->themeManager = $themeManager;
        $this->blockRenderer = $blockRenderer;
        $this->pageResolver = $pageResolver;
    }

    /**
     * Catálogo geral de cursos: GET /cursos
     */
    public function index(Request $request): Response
    {
        if ($this->seo !== null) {
            $this->seo->setTitle('Cursos & Tutoriais | Academy')
                ->setDescription('Explore os cursos e tutoriais disponíveis na plataforma Academy.');
        }

        $catalogHtml = $this->catalogRenderer->render();

        $themePath = $this->resolveActiveThemePath();
        $academyTemplateCandidate = $themePath !== '' ? ($themePath . '/templates/academy.php') : '';

        if ($this->themeManager !== null && $academyTemplateCandidate !== '' && file_exists($academyTemplateCandidate)) {
            try {
                $html = $this->themeManager->render('templates/academy', [
                    'page' => [
                        'title'   => 'Cursos & Tutoriais',
                        'content' => $catalogHtml,
                    ],
                ]);
                return new Response($html);
            } catch (Throwable $e) {
                // Fallback para renderização direta
            }
        }

        $html = $this->renderView('front/catalog', [
            'catalogHtml' => $catalogHtml,
        ]);

        return new Response($html);
    }

    /**
     * Página de apresentação do curso ou acesso direto a uma aula/página fabricada: GET /cursos/{courseSlug}
     */
    public function showCourse(string $courseSlug): Response
    {
        $course = $this->courseRepo->findBySlug($courseSlug);

        // Suporte a link direto /cursos/{lessonSlug} (ex: /cursos/estrutura-de-dados -> pages/estrutura-de-dados.php)
        if (!$course) {
            if (method_exists($this->structureRepo, 'findPublishedLessonGloballyBySlug')) {
                $directLesson = $this->structureRepo->findPublishedLessonGloballyBySlug($courseSlug);
                if ($directLesson) {
                    $parentCourse = $this->courseRepo->findById((int)$directLesson['course_id']);
                    if ($parentCourse && ($parentCourse['status'] ?? '') === 'published') {
                        return $this->showLesson((string)$parentCourse['slug'], (string)$directLesson['slug']);
                    }
                }
            }

            $candidateThemeFile = 'pages/' . ltrim($courseSlug, '/\\') . '.php';
            if ($this->themeFileExists($candidateThemeFile)) {
                $renderedFile = $this->renderThemeFile($candidateThemeFile, [
                    'course'   => ['title' => 'Academy', 'slug' => 'cursos'],
                    'lesson'   => ['title' => ucwords(str_replace('-', ' ', $courseSlug)), 'slug' => $courseSlug],
                    'adjacent' => ['prev' => null, 'next' => null],
                    'navHtml'  => '',
                ]);
                return new Response($renderedFile);
            }

            return new Response("<h1>404 - Curso não encontrado</h1><p>Não encontramos o curso solicitado: [" . htmlspecialchars($courseSlug, ENT_QUOTES, 'UTF-8') . "]</p>", 404);
        }

        if (($course['status'] ?? '') === 'draft') {
            return new Response("<h1>404 - Curso não encontrado</h1><p>Não encontramos o curso solicitado: [" . htmlspecialchars($courseSlug, ENT_QUOTES, 'UTF-8') . "]</p>", 404);
        }

        $title = htmlspecialchars((string)$course['title'], ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars((string)($course['description'] ?? ''), ENT_QUOTES, 'UTF-8');

        if ($this->seo !== null) {
            $this->seo->setTitle($title . ' | Academy')
                ->setDescription(mb_substr(strip_tags((string)($course['description'] ?? '')), 0, 150) . '...');
        }

        $navHtml = $this->navService->renderCourseNav($courseSlug);
        $tree = $this->navService->getCourseTree($courseSlug);

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

        $html = $this->renderView('front/course', [
            'title'     => $title,
            'desc'      => $desc,
            'ctaButton' => $ctaButton,
            'navHtml'   => $navHtml,
        ]);

        return new Response($html);
    }

    /**
     * Exibição de aula específica com sidebar navegável e 3 Perfis Mobile: GET /cursos/{courseSlug}/{lessonSlug}
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
            $this->seo->setTitle($lessonTitle . ' - ' . $courseTitle . ' | Academy')
                ->setDescription(mb_substr($plainDesc, 0, 150) . '...');
        }

        $navHtml = $this->navService->renderCourseNav($courseSlug, $lessonSlug);
        $adjacent = $this->navService->getAdjacentLessons($courseSlug, $lessonSlug);

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
        } elseif (!empty($themeFile)) {
            $bodyContent = $this->renderThemeFile((string)$themeFile, [
                'course'   => $course,
                'lesson'   => $lesson,
                'adjacent' => $adjacent,
                'navHtml'  => $navHtml,
            ]);
        } else {
            $bodyContent = "<div class='ds-academy-empty-lesson' style='padding:40px; text-align:center; color:var(--text-muted);'><p>O conteúdo desta aula está sendo preparado.</p></div>";
        }

        $paginationHtml = $this->renderPagination($adjacent);
        $courseUrl = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/cursos/' . urlencode($courseSlug);
        $mobileProfile = (string)($lesson['mobile_profile'] ?? 'drawer');

        $html = $this->renderView('front/lesson', [
            'courseUrl'      => $courseUrl,
            'courseTitle'    => $courseTitle,
            'lessonTitle'    => $lessonTitle,
            'bodyContent'    => $bodyContent,
            'navHtml'        => $navHtml,
            'paginationHtml' => $paginationHtml,
            'adjacent'       => $adjacent,
            'mobileProfile'  => $mobileProfile,
        ]);

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

    private function themeFileExists(string $themeFile): bool
    {
        $themePath = $this->resolveActiveThemePath();
        if ($themePath === '') {
            return false;
        }
        $target = $themePath . '/' . ltrim($themeFile, '/\\');
        $realTarget = realpath($target);
        $realTheme = realpath($themePath);
        if ($realTarget === false || $realTheme === false) {
            return false;
        }
        $prefix = rtrim($realTheme, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return str_starts_with($realTarget, $prefix) && is_file($realTarget);
    }

    private function renderThemeFile(string $themeFile, array $context): string
    {
        $themePath = $this->resolveActiveThemePath();
        if ($themePath === '') {
            return "<div class='alert alert-warning' style='padding:20px; color:#ffb703; background:rgba(255, 183, 3, 0.1); border-radius:8px;'>Arquivo do tema não encontrado ou inacessível: " . htmlspecialchars($themeFile, ENT_QUOTES, 'UTF-8') . "</div>";
        }

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
        if ($this->pageResolver !== null) {
            return $this->pageResolver->resolveActiveThemePath();
        }

        if ($this->themeManager !== null) {
            $activePath = $this->themeManager->getActiveThemePath();
            if (!empty($activePath) && is_dir($activePath)) {
                return $activePath;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $viewName, array $data = []): string
    {
        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewName, '/\\') . '.php';
        if (!is_file($viewFile)) {
            return '';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewFile;
        return (string)ob_get_clean();
    }
}
