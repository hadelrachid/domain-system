<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

use DateTimeImmutable;

/**
 * Contrato de Gerenciamento de Matrículas e Acesso a Cursos (DIP / SOLID).
 * 
 * Responsabilidade Única (SRP):
 * - Controlar a autorização de acesso dos alunos aos cursos.
 * - Manter o ciclo de vida das matrículas (ativa, expirada, cancelada).
 * - Totalmente desacoplado de provedores de pagamento ou checkout.
 */
interface CourseEnrollmentInterface
{
    /**
     * Verifica se o aluno possui matrícula ativa no curso informado.
     * Cursos gratuitos/públicos ou administradores podem conceder acesso irrestrito.
     */
    public function hasActiveEnrollment(int $studentId, int $courseId): bool;

    /**
     * Matricula o aluno no curso, opcionalmente com prazo de expiração (para assinaturas)
     * e referência ao pedido financeiro.
     */
    public function enroll(
        int $studentId,
        int $courseId,
        ?DateTimeImmutable $expiresAt = null,
        ?string $orderRef = null
    ): bool;

    /**
     * Revoga ou cancela o acesso do aluno ao curso (ex: estorno, inadimplência ou cancelamento).
     */
    public function revoke(int $studentId, int $courseId): bool;

    /**
     * Retorna a lista de IDs de cursos nos quais o aluno possui matrícula ativa.
     *
     * @return array<int>
     */
    public function getActiveCourseIds(int $studentId): array;

    /**
     * Retorna os detalhes da matrícula de um aluno em um curso específico.
     *
     * @return array<string, mixed>|null
     */
    public function getEnrollment(int $studentId, int $courseId): ?array;
}
