<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Repositories;

use DateTimeImmutable;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentRepositoryInterface;
use PDO;
use Throwable;

/**
 * Repositório PDO para persistência de matrículas (SQLite e MySQL / ANSI SQL).
 */
class PdoCourseEnrollmentRepository implements CourseEnrollmentRepositoryInterface
{
    private PDO $pdo;
    private bool $tableEnsured = false;

    public function __construct(DatabaseConnectionInterface|PDO $connection, bool $autoEnsureSchema = false)
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

        $sql = "
            CREATE TABLE IF NOT EXISTS academy_enrollments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'active',
                expires_at DATETIME NULL,
                order_ref VARCHAR(128) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Ajuste sintático para MySQL/MariaDB caso não seja SQLite
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS academy_enrollments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    course_id INT NOT NULL,
                    status VARCHAR(32) NOT NULL DEFAULT 'active',
                    expires_at DATETIME NULL,
                    order_ref VARCHAR(128) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_course (user_id, course_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        }

        try {
            $this->pdo->exec($sql);
            if ($driver === 'sqlite') {
                $this->pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_academy_enrollments_uc ON academy_enrollments(user_id, course_id)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_academy_enrollments_st ON academy_enrollments(status)");
            }
            $this->tableEnsured = true;
        } catch (Throwable $e) {
            // Registra erro sem silenciar cegamente
            error_log("[Academy] Falha ao verificar/criar tabela academy_enrollments: " . $e->getMessage());
        }
    }

    public function findByStudentAndCourse(int $studentId, int $courseId): ?array
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_enrollments 
            WHERE user_id = ? AND course_id = ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$studentId, $courseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function findActiveByStudent(int $studentId): array
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_enrollments 
            WHERE user_id = ? 
              AND status = 'active'
              AND (expires_at IS NULL OR expires_at > ?)
            ORDER BY id DESC
        ");
        $stmt->execute([$studentId, $now]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveEnrollment(
        int $studentId,
        int $courseId,
        string $status = 'active',
        ?DateTimeImmutable $expiresAt = null,
        ?string $orderRef = null
    ): bool {
        if ($studentId <= 0 || $courseId <= 0) {
            throw new \InvalidArgumentException('Aluno ou curso inválido.');
        }

        $this->ensureTable();
        $existing = $this->findByStudentAndCourse($studentId, $courseId);
        $expiresStr = $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null;
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE academy_enrollments 
                SET status = ?, expires_at = ?, order_ref = ?, updated_at = ?
                WHERE id = ?
            ");
            return $stmt->execute([$status, $expiresStr, $orderRef, $now, (int)$existing['id']]);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO academy_enrollments (user_id, course_id, status, expires_at, order_ref, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        try {
            return $stmt->execute([$studentId, $courseId, $status, $expiresStr, $orderRef, $now, $now]);
        } catch (\PDOException $e) {
            // Trata exclusivamente violação comprovada de unicidade (concorrência)
            if ($this->isUniqueConstraintViolation($e)) {
                return $this->updateExistingEnrollment($studentId, $courseId, $status, $expiresStr, $orderRef, $now);
            }

            // Propaga qualquer outro erro real de banco (conexão, sintaxe, colunas)
            throw $e;
        }
    }

    public function isUniqueConstraintViolation(\PDOException $e): bool
    {
        $sqlState = (string)($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        // MySQL / MariaDB: SQLSTATE 23000 + driver code 1062
        if ($sqlState === '23000' && $driverCode === 1062) {
            return true;
        }

        // SQLite: constraint violation unique
        if (str_contains($message, 'unique constraint') || str_contains($message, 'unique key') || str_contains($message, 'duplicate entry')) {
            return true;
        }

        if ($sqlState === '23000' && ($driverCode === 19 || $driverCode === 2067)) {
            return true;
        }

        return false;
    }

    private function updateExistingEnrollment(
        int $studentId,
        int $courseId,
        string $status,
        ?string $expiresAt,
        ?string $orderRef,
        string $now
    ): bool {
        $stmt = $this->pdo->prepare("
            UPDATE academy_enrollments 
            SET status = ?, expires_at = ?, order_ref = ?, updated_at = ?
            WHERE user_id = ? AND course_id = ?
        ");
        return $stmt->execute([$status, $expiresAt, $orderRef, $now, $studentId, $courseId]);
    }

    public function updateStatus(int $studentId, int $courseId, string $status): bool
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            UPDATE academy_enrollments 
            SET status = ?, updated_at = ?
            WHERE user_id = ? AND course_id = ?
        ");
        return $stmt->execute([$status, $now, $studentId, $courseId]);
    }

    public function countEnrolledInCourse(int $courseId): int
    {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT user_id) as total 
            FROM academy_enrollments 
            WHERE course_id = ? AND status = 'active'
        ");
        $stmt->execute([$courseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function countActiveEnrollments(): int
    {
        $this->ensureTable();
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM academy_enrollments WHERE status = 'active'");
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
