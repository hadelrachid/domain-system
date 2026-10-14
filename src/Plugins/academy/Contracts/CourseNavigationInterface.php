<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato estrito para a navegação de cursos e aulas no Domain-System OS.
 * 
 * Segue os princípios SOLID (DIP e ISP):
 * Permite que qualquer template ou workspace consuma e renderize a árvore
 * de navegação de um curso (módulos, aulas e links) sem acoplamento a tabelas SQL.
 */
interface CourseNavigationInterface
{
    /**
     * Retorna a árvore hierárquica completa do curso (módulos e suas respectivas aulas).
     *
     * @param string|int $courseIdentifier Slug ou ID do curso
     * @return array<int, array{
     *     id: int,
     *     title: string,
     *     position: int,
     *     lessons: array<int, array{
     *         id: int,
     *         title: string,
     *         slug: string,
     *         url: string,
     *         source_type: string,
     *         source?: ?string,
     *         position: int,
     *         status: string
     *     }>
     * }>
     */
    public function getCourseTree(string|int $courseIdentifier): array;

    /**
     * Renderiza o container semântico <nav class="course-nav"> com a árvore de módulos e aulas.
     *
     * @param string|int $courseIdentifier Slug ou ID do curso
     * @param string $currentLessonSlug Slug da aula atualmente ativa para destaque
     * @param array<string, mixed> $options Opções visuais customizadas
     * @return string HTML seguro do container <nav>
     */
    public function renderCourseNav(string|int $courseIdentifier, string $currentLessonSlug = '', array $options = []): string;

    /**
     * Retorna as aulas adjacentes (anterior e próxima) para paginação de navegação linear.
     *
     * @param string|int $courseIdentifier Slug ou ID do curso
     * @param string $currentLessonSlug Slug da aula atual
     * @return array{
     *     prev: ?array{id: int, title: string, slug: string, url: string},
     *     next: ?array{id: int, title: string, slug: string, url: string}
     * }
     */
    public function getAdjacentLessons(string|int $courseIdentifier, string $currentLessonSlug): array;
}
