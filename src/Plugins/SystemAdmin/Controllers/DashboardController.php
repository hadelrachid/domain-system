<?php
namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\Connection;
use PDO;
use Exception;

class DashboardController
{
    private ThemeManager $theme;
    private Connection $db;

    public function __construct(ThemeManager $theme, Connection $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctorId = $_SESSION['linked_doctor_id'] ?? null;
        
        try {
            $pdo = $this->db->getPdo();
            $today = date('Y-m-d');

            if ($role === 'doctor') {
                $appointmentsToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$today}' AND doctor_id = " . (int)$doctorId)->fetchColumn() ?: 0;
                $patientsServed = $pdo->query("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = " . (int)$doctorId)->fetchColumn() ?: 0;
                $pendingQueue = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$today}' AND doctor_id = " . (int)$doctorId . " AND status NOT IN ('Finalizado', 'Cancelado')")->fetchColumn() ?: 0;
                
                $queue = $pdo->query("
                    SELECT a.id, a.appointment_time, p.name as patient_name, a.status 
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    WHERE a.doctor_id = " . (int)$doctorId . " 
                      AND (a.appointment_date = '{$today}' OR a.status IN ('Aguardando Médico', 'Em Atendimento'))
                    ORDER BY CASE a.status 
                        WHEN 'Em Atendimento' THEN 1 
                        WHEN 'Aguardando Médico' THEN 2 
                        ELSE 3 END, a.appointment_time ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

                return $this->theme->render('admin/dashboard_doctor', [
                    'appointmentsToday' => $appointmentsToday,
                    'patientsServed' => $patientsServed,
                    'pendingQueue' => $pendingQueue,
                    'queue' => $queue
                ]);
            } else {
                $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn() ?: 0;
                $totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn() ?: 0;
                $appointmentsToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$today}'")->fetchColumn() ?: 0;

                $queue = $pdo->query("
                    SELECT a.id, a.appointment_time, p.name as patient_name, d.name as doctor_name, a.status 
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    JOIN doctors d ON a.doctor_id = d.id
                    WHERE a.appointment_date = '{$today}'
                    ORDER BY a.appointment_time ASC
                    LIMIT 10
                ")->fetchAll(PDO::FETCH_ASSOC);

                $waitingRoom = $pdo->query("
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

                return $this->theme->render('admin/dashboard', [
                    'theme' => $this->theme,
                    'totalPatients' => $totalPatients,
                    'totalDoctors' => $totalDoctors,
                    'appointmentsToday' => $appointmentsToday,
                    'queue' => $queue,
                    'waitingRoom' => $waitingRoom,
                    'role' => $role
                ]);
            }
        } catch (Exception $e) {
            return "Erro ao renderizar dashboard: " . $e->getMessage();
        }
    }
}
