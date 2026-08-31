<?php

namespace DomainSystem\Plugins\medical_records\Contracts;

interface RecordRepositoryInterface
{
    /**
     * Busca os dados essenciais de um agendamento com junção de Paciente e Médico.
     */
    public function getAppointmentDetails(int $appointmentId): ?array;

    /**
     * Retorna o prontuário preenchido de um agendamento, se houver.
     */
    public function findByAppointment(int $appointmentId): ?array;

    /**
     * Retorna o histórico de prontuários de um paciente (excluindo um agendamento atual).
     */
    public function getPatientHistory(int $patientId, int $excludeAppointmentId): array;

    /**
     * Retorna todos os exames anexados a um agendamento.
     */
    public function getExamsByAppointment(int $appointmentId): array;

    /**
     * Retorna um exame específico.
     */
    public function getExamById(int $examId): ?array;

    /**
     * Salva um prontuário (cria ou atualiza).
     */
    public function saveRecord(int $appointmentId, int $patientId, int $doctorId, array $data): void;

    /**
     * Anexa um exame ao agendamento.
     */
    public function attachExam(int $appointmentId, string $fileName, string $filePath): void;

    /**
     * Deleta um exame pelo ID.
     */
    public function deleteExam(int $examId): void;

    /**
     * Atualiza o status do agendamento (Ex: para 'Atendido').
     */
    public function updateAppointmentStatus(int $appointmentId, string $status): void;
}
