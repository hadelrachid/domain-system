<?php
namespace DomainSystem\Plugins\triage\Contracts;

interface TriageRepositoryInterface
{
    /**
     * Retorna a lista de pacientes aguardando triagem.
     */
    public function getAwaitingTriage(): array;

    /**
     * Retorna os dados completos do agendamento (inclui dados do paciente e médico).
     */
    public function getAppointmentData(int $appointmentId): ?array;

    /**
     * Retorna os dados da triagem já salva para um agendamento.
     */
    public function getTriageData(int $appointmentId): array;

    /**
     * Salva ou atualiza os dados da triagem de um agendamento e muda o status.
     */
    public function saveTriage(int $appointmentId, array $data): void;
}
