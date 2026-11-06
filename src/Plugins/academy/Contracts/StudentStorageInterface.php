<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de Armazenamento Seguro e Isolado de Dados Pessoais do Aluno (DIP / LGPD).
 * 
 * Responsabilidade:
 * - Gerenciar pastas, anotações e arquivos privados do aluno.
 * - Garantir isolamento estrito: nenhum aluno pode acessar arquivos de outro aluno (anti-IDOR e anti-Path Traversal).
 */
interface StudentStorageInterface
{
    /**
     * Retorna o caminho absoluto do diretório privado do aluno.
     */
    public function getStudentDirectory(int $studentId): string;

    /**
     * Salva anotação pessoal de uma aula específica de forma isolada e segura.
     */
    public function saveStudentNote(int $studentId, int $courseId, int $lessonId, string $content): bool;

    /**
     * Recupera o texto da anotação pessoal da aula.
     */
    public function getStudentNote(int $studentId, int $courseId, int $lessonId): string;

    /**
     * Lista os arquivos salvos pelo aluno na sua pasta pessoal.
     *
     * @return array<array{name: string, size: int, modified_at: int}>
     */
    public function listStudentFiles(int $studentId): array;

    /**
     * Remove um arquivo da pasta pessoal do aluno de forma segura (com proteção contra traversal).
     */
    public function deleteStudentFile(int $studentId, string $filename): bool;
}
