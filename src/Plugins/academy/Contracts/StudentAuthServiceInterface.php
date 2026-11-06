<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de Autenticação e Sessão de Alunos da Academia (DIP / Segregação de Interface).
 * Isola completamente a identidade do aluno dos privilégios e sessões do Painel Administrativo.
 */
interface StudentAuthServiceInterface
{
    /**
     * Verifica se há um aluno autenticado na sessão atual.
     */
    public function isLoggedIn(): bool;

    /**
     * Retorna o ID único do aluno autenticado ou null se visitante.
     */
    public function getStudentId(): ?int;

    /**
     * Retorna o nome do aluno autenticado ou null.
     */
    public function getStudentName(): ?string;

    /**
     * Retorna o nome de usuário (username/apelido) do aluno ou null.
     */
    public function getStudentUsername(): ?string;

    /**
     * Retorna o e-mail do aluno autenticado ou null.
     */
    public function getStudentEmail(): ?string;

    /**
     * Registra o aluno na sessão isolada da academia.
     */
    public function login(int $studentId, string $name, string $email, ?string $username = null): void;

    /**
     * Encerra a sessão do aluno sem interferir na sessão administrativa do núcleo.
     */
    public function logout(): void;

    /**
     * Define uma mensagem flash exclusiva para o escopo do aluno/academia.
     */
    public function setFlash(string $type, string $message): void;

    /**
     * Recupera e remove a mensagem flash do escopo do aluno.
     */
    public function getFlash(?string $type = null): array|string|null;
}
