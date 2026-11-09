<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

use DomainSystem\Plugins\academy\DTO\StudentCredential;

/**
 * Contrato para persistência e recuperação de credenciais de alunos (DIP / SRP).
 */
interface StudentCredentialRepositoryInterface
{
    /**
     * Localiza credencial de aluno pelo e-mail normalizado.
     */
    public function findByEmail(string $email): ?StudentCredential;

    /**
     * Localiza credencial pelo nome de usuário (slug @username).
     */
    public function findByUsername(string $username): ?StudentCredential;

    /**
     * Cadastra um novo aluno no repositório de usuários.
     * Retorna o ID gerado.
     */
    public function createStudent(string $name, ?string $username, string $email, string $passwordHash): int;
}
