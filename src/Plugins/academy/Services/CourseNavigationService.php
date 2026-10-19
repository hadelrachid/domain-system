<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\CourseNavigationInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;

class CourseNavigationService implements CourseNavigationInterface
{
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private string $baseUrl;

    public function __construct(
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        ?string $baseUrl = null
    ) {
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->baseUrl = $baseUrl ?? (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '');
    }

    private function resolveCourse(string|int $courseIdentifier): ?array
    {
        if (is_numeric($courseIdentifier)) {
            return $this->courseRepo->findById((int)$courseIdentifier);
        }
        return $this->courseRepo->findBySlug((string)$courseIdentifier);
    }

    public function getCourseTree(string|int $courseIdentifier): array
    {
        $course = $this->resolveCourse($courseIdentifier);
        if (!$course) {
            return [];
        }

        $courseId = (int)$course['id'];
        $courseSlug = (string)$course['slug'];

        $modules = $this->structureRepo->getModules($courseId);
        $allLessons = $this->structureRepo->getLessons($courseId);

        // Agrupa apenas aulas publicadas por module_id
        $lessonsByModule = [];
        $standaloneLessons = [];

        foreach ($allLessons as $lesson) {
            if (($lesson['status'] ?? 'published') !== 'published') {
                continue;
            }
            $lessonSlug = (string)$lesson['slug'];
            $lesson['url'] = $this->baseUrl . '/cursos/' . urlencode($courseSlug) . '/' . urlencode($lessonSlug);

            $modId = $lesson['module_id'] !== null ? (int)$lesson['module_id'] : null;
            if ($modId !== null && $modId > 0) {
                $lessonsByModule[$modId][] = $lesson;
            } else {
                $standaloneLessons[] = $lesson;
            }
        }

        $tree = [];

        foreach ($modules as $mod) {
            $mId = (int)$mod['id'];
            $tree[] = [
                'id'       => $mId,
                'title'    => (string)$mod['title'],
                'position' => (int)$mod['position'],
                'lessons'  => $lessonsByModule[$mId] ?? [],
            ];
        }

        // Se houver aulas sem módulo cadastrado, agrupa num módulo geral
        if (!empty($standaloneLessons)) {
            $tree[] = [
                'id'       => 0,
                'title'    => 'Aulas Gerais',
                'position' => 999,
                'lessons'  => $standaloneLessons,
            ];
        }

        return $tree;
    }

    public function renderCourseNav(string|int $courseIdentifier, string $currentLessonSlug = '', array $options = []): string
    {
        $course = $this->resolveCourse($courseIdentifier);
        if (!$course) {
            return '';
        }

        $tree = $this->getCourseTree($courseIdentifier);
        if (empty($tree)) {
            return '';
        }

        $courseTitle = htmlspecialchars((string)$course['title'], ENT_QUOTES, 'UTF-8');
        $courseSlug = (string)$course['slug'];
        $courseUrl = $this->baseUrl . '/cursos/' . urlencode($courseSlug);

        $html = '<nav class="course-nav ds-academy-nav" aria-label="Navegação do Curso ' . $courseTitle . '">';
        $html .= '<div class="course-nav-header">';
        $html .= '<a href="' . $courseUrl . '" class="course-nav-home-link">🎓 ' . $courseTitle . '</a>';
        $html .= '</div>';

        $html .= '<div class="course-nav-body">';

        $globalLessonCounter = 1;

        foreach ($tree as $mod) {
            $modTitle = htmlspecialchars($mod['title'], ENT_QUOTES, 'UTF-8');
            $lessons = $mod['lessons'] ?? [];

            $html .= '<div class="course-nav-module">';
            $html .= '<h4 class="course-nav-module-title">' . $modTitle . '</h4>';
            $html .= '<ul class="course-nav-lessons">';

            foreach ($lessons as $lesson) {
                $lTitle = htmlspecialchars($lesson['title'], ENT_QUOTES, 'UTF-8');
                $lSlug = (string)$lesson['slug'];
                $lUrl = htmlspecialchars($lesson['url'], ENT_QUOTES, 'UTF-8');
                $isActive = ($lSlug === $currentLessonSlug);

                $activeClass = $isActive ? ' is-active current-lesson' : '';
                $ariaCurrent = $isActive ? ' aria-current="page"' : '';

                $html .= '<li class="course-nav-lesson-item' . $activeClass . '">';
                $html .= '<a href="' . $lUrl . '"' . $ariaCurrent . ' class="course-nav-lesson-link">';
                $html .= '<span class="lesson-index">' . sprintf('%02d', $globalLessonCounter++) . '</span> ';
                $html .= '<span class="lesson-title">' . $lTitle . '</span>';
                $html .= '</a>';
                $html .= '</li>';
            }

            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    public function getAdjacentLessons(string|int $courseIdentifier, string $currentLessonSlug): array
    {
        $course = $this->resolveCourse($courseIdentifier);
        if (!$course) {
            return ['prev' => null, 'next' => null];
        }

        $courseSlug = (string)$course['slug'];
        $allLessons = $this->structureRepo->getLessons((int)$course['id']);

        if (empty($allLessons)) {
            return ['prev' => null, 'next' => null];
        }

        // Filtra apenas aulas publicadas e normaliza
        $flat = [];
        foreach ($allLessons as $l) {
            if (($l['status'] ?? 'published') !== 'published') {
                continue;
            }
            $slug = (string)$l['slug'];
            $flat[] = [
                'id'    => (int)$l['id'],
                'title' => (string)$l['title'],
                'slug'  => $slug,
                'url'   => $this->baseUrl . '/cursos/' . urlencode($courseSlug) . '/' . urlencode($slug),
            ];
        }

        $currentIndex = -1;
        foreach ($flat as $index => $item) {
            if ($item['slug'] === $currentLessonSlug) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === -1) {
            return ['prev' => null, 'next' => null];
        }

        $prev = ($currentIndex > 0) ? $flat[$currentIndex - 1] : null;
        $next = ($currentIndex < count($flat) - 1) ? $flat[$currentIndex + 1] : null;

        return ['prev' => $prev, 'next' => $next];
    }
}
