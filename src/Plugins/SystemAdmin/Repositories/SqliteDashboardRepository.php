<?php

namespace DomainSystem\Plugins\SystemAdmin\Repositories;

use DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface;
use DomainSystem\Plugins\Database\Connection;
use PDO;

class SqliteDashboardRepository implements DashboardRepositoryInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getGlobalStats(string $date): array
    {
        $pdo = $this->db->getPdo();
        $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn() ?: 0;
        $totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn() ?: 0;
        $appointmentsToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$date}'")->fetchColumn() ?: 0;

        return [
            'totalPatients' => $totalPatients,
            'totalDoctors' => $totalDoctors,
            'appointmentsToday' => $appointmentsToday
        ];
    }

    public function getDoctorStats(int $doctorId, string $date): array
    {
        $pdo = $this->db->getPdo();
        $appointmentsToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$date}' AND doctor_id = {$doctorId}")->fetchColumn() ?: 0;
        $patientsServed = $pdo->query("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = {$doctorId}")->fetchColumn() ?: 0;
        $pendingQueue = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$date}' AND doctor_id = {$doctorId} AND status NOT IN ('Finalizado', 'Cancelado')")->fetchColumn() ?: 0;

        return [
            'appointmentsToday' => $appointmentsToday,
            'patientsServed' => $patientsServed,
            'pendingQueue' => $pendingQueue
        ];
    }

    public function getGlobalQueue(string $date): array
    {
        $pdo = $this->db->getPdo();
        return $pdo->query("
            SELECT a.id, a.appointment_time, p.name as patient_name, d.name as doctor_name, a.status 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.appointment_date = '{$date}'
            ORDER BY a.appointment_time ASC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDoctorQueue(int $doctorId, string $date): array
    {
        $pdo = $this->db->getPdo();
        return $pdo->query("
            SELECT a.id, a.appointment_time, p.name as patient_name, a.status 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE a.doctor_id = {$doctorId}
              AND (a.appointment_date = '{$date}' OR a.status IN ('Aguardando Médico', 'Em Atendimento'))
            ORDER BY CASE a.status 
                WHEN 'Em Atendimento' THEN 1 
                WHEN 'Aguardando Médico' THEN 2 
                ELSE 3 END, a.appointment_time ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWaitingRoom(): array
    {
        $pdo = $this->db->getPdo();
        $today = date('Y-m-d');
        return $pdo->query("
            SELECT a.id, a.appointment_date, a.appointment_time, p.name as patient_name, d.name as doctor_name, a.status 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.status IN ('Aguardando Triagem', 'Aguardando Médico', 'Em Atendimento')
            ORDER BY CASE a.status 
                WHEN 'Em Atendimento' THEN 1 
                WHEN 'Aguardando Médico' THEN 2 
                WHEN 'Aguardando Triagem' THEN 3 
                ELSE 4 END, a.appointment_date ASC, a.appointment_time ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAppointmentsChartData(int $days, ?int $doctorId = null): array
    {
        $pdo = $this->db->getPdo();
        $chartData = [];
        
        // SQLite date formatting varies, we'll generate the last N days in PHP and query for each
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $displayDate = date('d/m', strtotime($date));
            
            $query = "SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$date}'";
            if ($doctorId) {
                $query .= " AND doctor_id = {$doctorId}";
            }
            
            $count = $pdo->query($query)->fetchColumn() ?: 0;
            
            $chartData['labels'][] = $displayDate;
            $chartData['data'][] = (int)$count;
        }

        return $chartData;
    }
}
