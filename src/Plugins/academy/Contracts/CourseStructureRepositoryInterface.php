<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de persistência para módulos e aulas de cursos (SOLID - DIP).
 */
interface CourseStructureRepositoryInterface
{
    /**
     * @return array<int, array{id: int, course_id: int, title: string, position: int, created_at?: string}>
     */
    public function getModules(int $courseId): array;

    public function createModule(int $courseId, string $title, int $position = 0): int;

    /**
     * @param array<string, mixed> $data
     */
    public function updateModule(int $moduleId, array $data): bool;

    public function deleteModule(int $moduleId): bool;

    /**
     * @return array<int, array{
     *     id: int,
     *     course_id: int,
     *     module_id: ?int,
     *     title: string,
     *     slug: string,
     *     source_type: string,
     *     source: ?string,
     *     content: ?string,
     *     position: int,
     *     status: string,
     *     created_at?: string
     * }>
     */
    public function getLessons(int $courseId, ?int $moduleId = null): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getLessonById(int $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getLessonBySlug(int $courseId, string $slug): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getPublishedLessonBySlug(int $courseId, string $slug): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function createLesson(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function updateLesson(int $id, array $data): bool;

    public function deleteLesson(int $id): bool;

    /**
     * Substitui a estrutura completa do curso de forma atômica e transacional (com rollback em erros).
     *
     * @param int $courseId
     * @param array<int, array{
     *     id?: int,
     *     title: string,
     *     position?: int,
     *     lessons: array<int, array{
     *         id?: int,
     *         title: string,
     *         slug?: string,
     *         source_type?: string,
     *         source?: ?string,
     *         content?: ?string,
     *         status?: string
     *     }>
     * }> $structure
     */
    public function replaceCourseStructure(int $courseId, array $structure): bool;

    /**
     * Garante a criação resiliente das tabelas 'academy_modules' e 'academy_lessons'.
     */
    public function ensureTables(): void;
}
