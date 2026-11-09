<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressRepositoryInterface;
use PDO;
use Throwable;

/**
 * Repositório PDO para persistência de progresso de aulas do aluno (ANSI SQL compatível com SQLite e MySQL).
 */
class PdoStudentProgressRepository implements StudentProgressRepositoryInterface
{
    private PDO $pdo;
    private bool $tableEnsured = false;

    public function __construct(DatabaseConnectionInterface|PDO $connection, bool $autoEnsureSchema = true)
    {
        $this->pdo = $connection instanceof DatabaseConnectionInterface
            ? $connection->getPdo()
            : $connection;

        if ($autoEnsureSchema) {
            $this->ensureTable();
        }
    }

    public function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }
        $this->tableEnsured = true;

        $sql = "
            CREATE TABLE IF NOT EXISTS academy_lesson_progress (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL,
                lesson_id INTEGER NOT NULL,
                completed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS academy_lesson_progress (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    course_id INT NOT NULL,
                    lesson_id INT NOT NULL,
                    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_lesson (user_id, lesson_id),
                    INDEX idx_user_course (user_id, course_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        }

        try {
            $this->pdo->exec($sql);
            if ($driver === 'sqlite') {
                $this->pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_academy_progress_ul ON academy_lesson_progress(user_id, lesson_id)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_academy_progress_uc ON academy_lesson_progress(user_id, course_id)");
            }
            $this->tableEnsured = true;
        } catch (Throwable $e) {
            // Silencia falha se já existir
        }
    }

    public function saveProgress(int $studentId, int $courseId, int $lessonId): bool
    {
        $this->ensureTable();
        if ($this->hasCompleted($studentId, $lessonId)) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO academy_lesson_progress (user_id, course_id, lesson_id, completed_at)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$studentId, $courseId, $lessonId, date('Y-m-d H:i:s')]);
    }

    public function deleteProgress(int $studentId, int $lessonId): bool
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            DELETE FROM academy_lesson_progress 
            WHERE user_id = ? AND lesson_id = ?
        ");
        return $stmt->execute([$studentId, $lessonId]);
    }

    public function hasCompleted(int $studentId, int $lessonId): bool
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT id FROM academy_lesson_progress 
            WHERE user_id = ? AND lesson_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$studentId, $lessonId]);
        return (bool)$stmt->fetchColumn();
    }

    public function findCompletedLessonIds(int $studentId, int $courseId): array
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT lesson_id FROM academy_lesson_progress 
            WHERE user_id = ? AND course_id = ? 
            ORDER BY lesson_id ASC
        ");
        $stmt->execute([$studentId, $courseId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $ids = [];
        if (is_array($rows)) {
            foreach ($rows as $val) {
                $ids[] = (int)$val;
            }
        }
        return $ids;
    }
}
