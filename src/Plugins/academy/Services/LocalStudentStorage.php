<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\StudentStorageInterface;
use InvalidArgumentException;

/**
 * Implementação Local Segura do Armazenamento de Arquivos e Anotações do Aluno (SRP / LGPD).
 */
class LocalStudentStorage implements StudentStorageInterface
{
    private string $baseStudentsDir;

    public function __construct(?string $baseStudentsDir = null)
    {
        if ($baseStudentsDir !== null) {
            $this->baseStudentsDir = rtrim($baseStudentsDir, '/\\');
        } elseif (defined('DOMAIN_SYSTEM_ROOT')) {
            $this->baseStudentsDir = DOMAIN_SYSTEM_ROOT . '/storage/students';
        } else {
            $this->baseStudentsDir = sys_get_temp_dir() . '/domain_system_students';
        }

        if (!is_dir($this->baseStudentsDir)) {
            @mkdir($this->baseStudentsDir, 0755, true);
        }
    }

    public function getStudentDirectory(int $studentId): string
    {
        if ($studentId <= 0) {
            throw new InvalidArgumentException('ID de aluno inválido.');
        }

        $dir = $this->baseStudentsDir . DIRECTORY_SEPARATOR . $studentId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function saveStudentNote(int $studentId, int $courseId, int $lessonId, string $content): bool
    {
        if ($studentId <= 0 || $courseId <= 0 || $lessonId <= 0) {
            return false;
        }

        $studentDir = $this->getStudentDirectory($studentId);
        $notesDir = $studentDir . DIRECTORY_SEPARATOR . 'notes';
        if (!is_dir($notesDir)) {
            @mkdir($notesDir, 0755, true);
        }

        $noteFile = $notesDir . DIRECTORY_SEPARATOR . "note_c{$courseId}_l{$lessonId}.txt";
        return file_put_contents($noteFile, $content, LOCK_EX) !== false;
    }

    public function getStudentNote(int $studentId, int $courseId, int $lessonId): string
    {
        if ($studentId <= 0 || $courseId <= 0 || $lessonId <= 0) {
            return '';
        }

        $studentDir = $this->getStudentDirectory($studentId);
        $noteFile = $studentDir . DIRECTORY_SEPARATOR . 'notes' . DIRECTORY_SEPARATOR . "note_c{$courseId}_l{$lessonId}.txt";

        if (is_file($noteFile)) {
            $content = file_get_contents($noteFile);
            return is_string($content) ? $content : '';
        }

        return '';
    }

    public function listStudentFiles(int $studentId): array
    {
        if ($studentId <= 0) {
            return [];
        }

        $studentDir = $this->getStudentDirectory($studentId);
        $filesDir = $studentDir . DIRECTORY_SEPARATOR . 'files';
        if (!is_dir($filesDir)) {
            return [];
        }

        $results = [];
        $items = scandir($filesDir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $fullPath = $filesDir . DIRECTORY_SEPARATOR . $item;
            if (is_file($fullPath)) {
                $results[] = [
                    'name'        => $item,
                    'size'        => (int)filesize($fullPath),
                    'modified_at' => (int)filemtime($fullPath),
                ];
            }
        }

        return $results;
    }

    public function deleteStudentFile(int $studentId, string $filename): bool
    {
        if ($studentId <= 0) {
            return false;
        }

        // Blindagem contra Path Traversal: extrai estritamente o basename
        $safeName = basename($filename);
        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            return false;
        }

        $studentDir = $this->getStudentDirectory($studentId);
        $filePath = $studentDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $safeName;

        if (is_file($filePath)) {
            return @unlink($filePath);
        }

        return false;
    }
}
