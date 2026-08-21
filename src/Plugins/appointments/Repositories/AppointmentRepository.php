<?php

namespace DomainSystem\Plugins\appointments\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;

class AppointmentRepository
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    /**
     * Retorna os agendamentos pendentes (Fila de Hoje) com dados do paciente e médico injetados
     */
    public function getPendingQueue(string $userRole, ?string $doctorId): array
    {
        $appointmentsRaw = $this->db->table('appointments')->get();
        $patientsMap = $this->getPatientsMap();
        $doctorsMap = $this->getDoctorsMap();

        $appointments = [];
        foreach ($appointmentsRaw as $a) {
            if (in_array($a['status'], ['Atendido', 'Finalizado', 'Cancelado'])) {
                continue;
            }

            if ($userRole === 'doctor' && (string)$a['doctor_id'] !== (string)$doctorId) {
                continue;
            }

            $a['patient_name'] = $patientsMap[$a['patient_id']]['name'] ?? 'Desconhecido';
            $a['patient_phone'] = $patientsMap[$a['patient_id']]['phone'] ?? '';
            $a['doctor_name'] = $doctorsMap[$a['doctor_id']]['name'] ?? 'Desconhecido';
            $a['doctor_specialty'] = $doctorsMap[$a['doctor_id']]['specialty'] ?? '';

            $appointments[] = $a;
        }

        // Ordenar: mais antigos primeiro (fila de chegada)
        usort($appointments, function($a, $b) {
            return strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) <=> strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
        });

        return $appointments;
    }

    /**
     * Retorna o histórico de atendimentos com filtros
     */
    public function getHistory(string $userRole, ?string $doctorId, string $searchQuery = ''): array
    {
        $appointmentsRaw = $this->db->table('appointments')->get();
        $patientsMap = $this->getPatientsMap();
        $doctorsMap = $this->getDoctorsMap();

        $appointments = [];
        foreach ($appointmentsRaw as $a) {
            if (!in_array($a['status'], ['Atendido', 'Finalizado'])) {
                continue;
            }

            if ($userRole === 'doctor' && (string)$a['doctor_id'] !== (string)$doctorId) {
                continue;
            }

            $a['patient_name'] = $patientsMap[$a['patient_id']]['name'] ?? 'Desconhecido';
            $a['patient_phone'] = $patientsMap[$a['patient_id']]['phone'] ?? '';
            $a['doctor_name'] = $doctorsMap[$a['doctor_id']]['name'] ?? 'Desconhecido';

            if (!empty($searchQuery)) {
                $matchName = str_contains(strtolower($a['patient_name']), $searchQuery);
                $matchPhone = str_contains(strtolower($a['patient_phone']), $searchQuery);
                $matchDate = str_contains($a['appointment_date'], $searchQuery);
                
                if (!$matchName && !$matchPhone && !$matchDate) {
                    continue;
                }
            }

            $appointments[] = $a;
        }

        // Ordenar por data mais recente
        usort($appointments, function($a, $b) {
            return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) <=> strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
        });

        // Limite de 20
        return array_slice($appointments, 0, 20);
    }

    /**
     * Retorna um único agendamento detalhado para Prontuário
     */
    public function getRecordDetails(int|string $id): ?array
    {
        $appointment = $this->db->table('appointments')->where('id', '=', $id)->first();
        if (empty($appointment)) return null;

        $patient = $this->db->table('patients')->where('id', '=', $appointment['patient_id'])->first();
        $doctor = $this->db->table('doctors')->where('id', '=', $appointment['doctor_id'])->first();

        $appointment['patient_data'] = $patient; // DTO completo
        $appointment['patient_name'] = $patient['name'] ?? 'Desconhecido';
        $appointment['patient_cpf'] = $patient['cpf'] ?? 'Desconhecido';
        $appointment['patient_birthdate'] = $patient['birthdate'] ?? '1900-01-01';
        $appointment['doctor_name'] = $doctor['name'] ?? 'Desconhecido';

        return $appointment;
    }

    /**
     * Retorna histórico clínico de um paciente específico (excluindo o ID atual)
     */
    public function getPatientClinicalHistory(int|string $patientId, int|string $excludeAppointmentId): array
    {
        $historyRaw = $this->db->table('appointments')->where('patient_id', '=', $patientId)->get();
        $doctorsMap = $this->getDoctorsMap();

        $history = [];
        foreach ($historyRaw as $h) {
            if ((string)$h['id'] !== (string)$excludeAppointmentId) {
                $h['doctor_name'] = $doctorsMap[$h['doctor_id']]['name'] ?? 'Desconhecido';
                $history[] = $h;
            }
        }

        usort($history, function($a, $b) {
            return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) <=> strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
        });

        return $history;
    }

    // --- Helpers Internos para não duplicar chamadas ---

    private function getPatientsMap(): array
    {
        $patients = $this->db->table('patients')->get();
        $map = [];
        foreach ($patients as $p) $map[$p['id']] = $p;
        return $map;
    }

    private function getDoctorsMap(): array
    {
        $doctors = $this->db->table('doctors')->get();
        $map = [];
        foreach ($doctors as $d) $map[$d['id']] = $d;
        return $map;
    }
}
