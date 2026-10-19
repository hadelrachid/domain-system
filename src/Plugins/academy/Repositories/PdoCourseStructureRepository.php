<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use InvalidArgumentException;
use PDO;
use Throwable;

class PdoCourseStructureRepository implements CourseStructureRepositoryInterface
{
    private const VALID_STATUSES = ['draft', 'published'];
    private const VALID_SOURCE_TYPES = ['theme_file', 'content'];
    private const VALID_MOBILE_PROFILES = ['drawer', 'bottom_bar', 'cinema'];

    private static array $accentMap = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a',
        'É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
        'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
        'Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ö'=>'o',
        'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u',
        'Ç'=>'c','Ñ'=>'n'
    ];

    private PDO $pdo;
    private bool $tablesEnsured = false;

    public function __construct(DatabaseConnectionInterface|PDO $connection, bool $autoEnsureSchema = true)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;

        if ($autoEnsureSchema) {
            $this->ensureTables();
        }
    }

    public function getModules(int $courseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_modules 
            WHERE course_id = ? 
            ORDER BY position ASC, id ASC
        ");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createModule(int $courseId, string $title, int $position = 0, ?string $folderSlug = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_modules (course_id, title, position, folder_slug) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$courseId, $title, $position, $folderSlug]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateModule(int $moduleId, array $data): bool
    {
        $fields = [];
        $values = [];

        if (isset($data['title'])) {
            $fields[] = 'title = ?';
            $values[] = $data['title'];
        }
        if (isset($data['position'])) {
            $fields[] = 'position = ?';
            $values[] = (int)$data['position'];
        }
        if (array_key_exists('folder_slug', $data)) {
            $fields[] = 'folder_slug = ?';
            $values[] = $data['folder_slug'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $moduleId;
        $sql = "UPDATE academy_modules SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function deleteModule(int $moduleId): bool
    {
        // Ao excluir módulo, desvincula as aulas para não perdê-las (ou exclui dependendo da regra)
        $stmt = $this->pdo->prepare("UPDATE academy_lessons SET module_id = NULL WHERE module_id = ?");
        $stmt->execute([$moduleId]);

        $del = $this->pdo->prepare("DELETE FROM academy_modules WHERE id = ?");
        return $del->execute([$moduleId]);
    }

    public function getLessons(int $courseId, ?int $moduleId = null): array
    {
        if ($moduleId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM academy_lessons 
                WHERE course_id = ? AND module_id = ? 
                ORDER BY position ASC, id ASC
            ");
            $stmt->execute([$courseId, $moduleId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT * FROM academy_lessons 
                WHERE course_id = ? 
                ORDER BY position ASC, id ASC
            ");
            $stmt->execute([$courseId]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getLessonById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_lessons WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getLessonBySlug(int $courseId, string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_lessons WHERE course_id = ? AND slug = ?");
        $stmt->execute([$courseId, $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPublishedLessonBySlug(int $courseId, string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM academy_lessons WHERE course_id = ? AND slug = ? AND status = 'published'");
        $stmt->execute([$courseId, $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPublishedLessonGloballyBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons 
            WHERE slug = ? AND status = 'published' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createLesson(array $data): int
    {
        $sourceType = (string)($data['source_type'] ?? 'theme_file');
        $status = (string)($data['status'] ?? 'published');
        $mobileProfile = (string)($data['mobile_profile'] ?? 'drawer');
        if (!in_array($mobileProfile, self::VALID_MOBILE_PROFILES, true)) {
            $mobileProfile = 'drawer';
        }

        $this->assertValidLessonEnums($sourceType, $status);

        $rawSlug = trim((string)($data['slug'] ?? ''));
        $rawTitle = trim((string)($data['title'] ?? 'Aula'));
        $slug = $this->sanitizeSlug($rawSlug !== '' ? $rawSlug : $rawTitle, (int)($data['position'] ?? 1));

        $stmt = $this->pdo->prepare("
            INSERT INTO academy_lessons (
                course_id, module_id, title, slug, source_type, source, content, mobile_profile, position, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['course_id'],
            $data['module_id'] ?? null,
            $rawTitle !== '' ? $rawTitle : 'Aula',
            $slug,
            $sourceType,
            $data['source'] ?? null,
            $data['content'] ?? null,
            $mobileProfile,
            $data['position'] ?? 0,
            $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateLesson(int $id, array $data): bool
    {
        if (array_key_exists('source_type', $data) || array_key_exists('status', $data)) {
            $sourceType = array_key_exists('source_type', $data) ? (string)$data['source_type'] : 'theme_file';
            $status = array_key_exists('status', $data) ? (string)$data['status'] : 'published';
            $this->assertValidLessonEnums($sourceType, $status);
        }

        if (array_key_exists('mobile_profile', $data)) {
            $mp = (string)$data['mobile_profile'];
            $data['mobile_profile'] = in_array($mp, self::VALID_MOBILE_PROFILES, true) ? $mp : 'drawer';
        }

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $this->sanitizeSlug((string)$data['slug'], (int)($data['position'] ?? 1));
        }

        $allowed = ['title', 'slug', 'module_id', 'source_type', 'source', 'content', 'mobile_profile', 'position', 'status'];
        $fields = [];
        $values = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $values[] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE academy_lessons SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function deleteLesson(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM academy_lessons WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Sincroniza a estrutura do curso por diff (H-04), preservando os IDs existentes de módulos
     * e aulas quando correspondem por ID (ou por slug do curso) e validando os campos (M-10).
     */
    public function replaceCourseStructure(int $courseId, array $structure): bool
    {
        return $this->syncCourseStructure($courseId, $structure);
    }

    public function syncCourseStructure(int $courseId, array $structure): bool
    {
        $this->ensureTables();
        $this->pdo->beginTransaction();
        try {
            $existingModules = [];
            foreach ($this->getModules($courseId) as $m) {
                $existingModules[(int)$m['id']] = $m;
            }

            $existingLessonsById = [];
            $existingLessonsBySlug = [];
            foreach ($this->getLessons($courseId) as $l) {
                $lId = (int)$l['id'];
                $existingLessonsById[$lId] = $l;
                $existingLessonsBySlug[(string)$l['slug']] = $l;
            }

            $keptModuleIds = [];
            $keptLessonIds = [];
            $usedSlugs = [];

            $modulePos = 1;
            $lessonPosGlobal = 1;

            foreach ($structure as $modData) {
                $modTitle = trim((string)($modData['title'] ?? 'Módulo Geral'));
                if ($modTitle === '') {
                    $modTitle = 'Módulo Geral';
                }
                $folderSlug = isset($modData['folder_slug']) && trim((string)$modData['folder_slug']) !== ''
                    ? trim((string)$modData['folder_slug'])
                    : null;

                $candidateModId = isset($modData['id']) ? (int)$modData['id'] : 0;
                if ($candidateModId > 0 && isset($existingModules[$candidateModId]) && !in_array($candidateModId, $keptModuleIds, true)) {
                    $this->updateModule($candidateModId, [
                        'title'       => $modTitle,
                        'position'    => $modulePos++,
                        'folder_slug' => $folderSlug ?? ($existingModules[$candidateModId]['folder_slug'] ?? null),
                    ]);
                    $moduleId = $candidateModId;
                } else {
                    $moduleId = $this->createModule($courseId, $modTitle, $modulePos++, $folderSlug);
                }
                $keptModuleIds[] = $moduleId;

                $lessons = $modData['lessons'] ?? [];
                if (!is_array($lessons)) {
                    continue;
                }

                foreach ($lessons as $lesson) {
                    $pos = $lessonPosGlobal++;
                    $title = trim((string)($lesson['title'] ?? 'Aula'));
                    if ($title === '') {
                        $title = 'Aula ' . $pos;
                    }

                    $sourceType = (string)($lesson['source_type'] ?? 'theme_file');
                    $status = (string)($lesson['status'] ?? 'published');
                    $this->assertValidLessonEnums($sourceType, $status);

                    $rawSlug = trim((string)($lesson['slug'] ?? ''));
                    $baseSlug = $this->sanitizeSlug($rawSlug !== '' ? $rawSlug : $title, $pos);
                    $slug = $this->deduplicateSlugInBatch($baseSlug, $usedSlugs);
                    $usedSlugs[$slug] = true;

                    $matchedLessonId = 0;
                    $candidateLessonId = isset($lesson['id']) ? (int)$lesson['id'] : 0;
                    if ($candidateLessonId > 0 && isset($existingLessonsById[$candidateLessonId]) && !in_array($candidateLessonId, $keptLessonIds, true)) {
                        $matchedLessonId = $candidateLessonId;
                    } elseif (isset($existingLessonsBySlug[$baseSlug])) {
                        $bySlugId = (int)$existingLessonsBySlug[$baseSlug]['id'];
                        if (!in_array($bySlugId, $keptLessonIds, true)) {
                            $matchedLessonId = $bySlugId;
                        }
                    }

                    // Se vai atualizar ou criar com um slug que já pertence a outra aula do curso ainda no BD,
                    // limpa temporariamente o slug conflitante caso a outra aula vá ser removida/renomeada.
                    if (isset($existingLessonsBySlug[$slug]) && (int)$existingLessonsBySlug[$slug]['id'] !== $matchedLessonId) {
                        $conflictId = (int)$existingLessonsBySlug[$slug]['id'];
                        $tmpStmt = $this->pdo->prepare("UPDATE academy_lessons SET slug = ? WHERE id = ?");
                        $tmpStmt->execute(['__tmp_' . $conflictId . '_' . bin2hex(random_bytes(3)), $conflictId]);
                    }

                    $mobileProfile = (string)($lesson['mobile_profile'] ?? ($matchedLessonId > 0 ? ($existingLessonsById[$matchedLessonId]['mobile_profile'] ?? 'drawer') : 'drawer'));

                    $lessonPayload = [
                        'course_id'      => $courseId,
                        'module_id'      => $moduleId,
                        'title'          => $title,
                        'slug'           => $slug,
                        'source_type'    => $sourceType,
                        'source'         => $lesson['source'] ?? $lesson['theme_file'] ?? $lesson['file_path'] ?? null,
                        'content'        => $lesson['content'] ?? ($matchedLessonId > 0 ? ($existingLessonsById[$matchedLessonId]['content'] ?? null) : null),
                        'mobile_profile' => $mobileProfile,
                        'position'       => $pos,
                        'status'         => $status,
                    ];

                    if ($matchedLessonId > 0) {
                        $this->updateLesson($matchedLessonId, $lessonPayload);
                        $keptLessonIds[] = $matchedLessonId;
                    } else {
                        $newLessonId = $this->createLesson($lessonPayload);
                        $keptLessonIds[] = $newLessonId;
                    }
                }
            }

            // Remove apenas aulas que não fazem mais parte da estrutura
            if (empty($keptLessonIds)) {
                $delLessons = $this->pdo->prepare("DELETE FROM academy_lessons WHERE course_id = ?");
                $delLessons->execute([$courseId]);
            } else {
                $placeholders = implode(',', array_fill(0, count($keptLessonIds), '?'));
                $delLessons = $this->pdo->prepare("DELETE FROM academy_lessons WHERE course_id = ? AND id NOT IN ({$placeholders})");
                $delLessons->execute(array_merge([$courseId], $keptLessonIds));
            }

            // Remove apenas módulos que não fazem mais parte da estrutura
            if (empty($keptModuleIds)) {
                $delModules = $this->pdo->prepare("DELETE FROM academy_modules WHERE course_id = ?");
                $delModules->execute([$courseId]);
            } else {
                $placeholders = implode(',', array_fill(0, count($keptModuleIds), '?'));
                $delModules = $this->pdo->prepare("DELETE FROM academy_modules WHERE course_id = ? AND id NOT IN ({$placeholders})");
                $delModules->execute(array_merge([$courseId], $keptModuleIds));
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function ensureTables(): void
    {
        if ($this->tablesEnsured) {
            return;
        }

        $migrationFile = dirname(__DIR__) . '/migrations/2026_10_15_000002_create_academy_structure.php';
        if (is_file($migrationFile)) {
            $migration = require $migrationFile;
            if (is_array($migration) && isset($migration['up']) && is_callable($migration['up'])) {
                ($migration['up'])($this->pdo);
            }
        }

        $this->tablesEnsured = true;
    }

    private function assertValidLessonEnums(string $sourceType, string $status): void
    {
        if (!in_array($sourceType, self::VALID_SOURCE_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de origem de aula inválido: {$sourceType}");
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Status de aula inválido: {$status}");
        }
    }

    private function sanitizeSlug(string $raw, int $fallbackPos = 1): string
    {
        $text = strtr($raw, self::$accentMap);
        if (function_exists('transliterator_transliterate')) {
            $trans = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if ($trans !== false) {
                $text = $trans;
            }
        }

        $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
        return $slug !== '' ? $slug : ('aula-' . $fallbackPos);
    }

    /**
     * @param array<string, bool> $usedSlugs
     */
    private function deduplicateSlugInBatch(string $baseSlug, array $usedSlugs): string
    {
        $slug = $baseSlug;
        $counter = 1;
        while (isset($usedSlugs[$slug])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        return $slug;
    }
}
