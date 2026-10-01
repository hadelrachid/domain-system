<?php

namespace DomainSystem\Plugins\SystemAdmin\Contracts;

interface DashboardRepositoryInterface
{
    /**
     * Retorna os dados estatísticos globais para o Admin/Recepcionista.
     * Deve conter: totalPatients, totalDoctors, appointmentsToday.
     */
    public function getGlobalStats(string $date): array;

    /**
     * Retorna os dados estatísticos para um médico específico.
     * Deve conter: appointmentsToday, patientsServed, pendingQueue.
     */
    public function getDoctorStats(int $doctorId, string $date): array;

    /**
     * Retorna a fila de próximos atendimentos do dia global.
     */
    public function getGlobalQueue(string $date): array;

    /**
     * Retorna a fila de atendimento específica de um médico.
     */
    public function getDoctorQueue(int $doctorId, string $date): array;

    /**
     * Retorna a sala de espera (pacientes que já chegaram e estão aguardando triagem/médico).
     */
    public function getWaitingRoom(): array;

    /**
     * Retorna os dados dos últimos N dias para renderizar gráficos de performance.
     * Retorna array de datas e suas respectivas quantidades de agendamento.
     */
    public function getAppointmentsChartData(int $days, ?int $doctorId = null): array;
}
