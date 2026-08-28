<?php

namespace DomainSystem\Plugins\appointments\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface;
use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;

class AppointmentRepository
{
    private QueryBuilder $db;
    private PatientReaderInterface $patientReader;
    private DoctorReaderInterface $doctorReader;

    public function __construct(QueryBuilder $db, PatientReaderInterface $patientReader, DoctorReaderInterface $doctorReader)
    {
        $this->db = $db;
        $this->patientReader = $patientReader;
        $this->doctorReader = $doctorReader;
    }

    /**
     * Retorna os agendamentos pendentes (Fila) com dados do paciente e médico injetados
     */
    public function getPendingQueue(string $userRole, ?string $doctorId): array
    {
        $appointmentsRaw = $this->db->table('appointments')->get();
        $patientsMap = $this->patientReader->getPatientsMap();
        $doctorsMap = $this->doctorReader->getDoctorsMap();

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
        $patientsMap = $this->patientReader->getPatientsMap();
        $doctorsMap = $this->doctorReader->getDoctorsMap();

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

        usort($appointments, function($a, $b) {
            return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) <=> strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
        });

        return array_slice($appointments, 0, 20);
    }

}
