<?php

namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\Connection;
use Exception;
use PDO;

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
        try {
            $pdo = $this->db->getPdo();
            $today = date('Y-m-d');

            // Métricas
            $totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn() ?: 0;
            $totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn() ?: 0;
            $appointmentsToday = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = '{$today}'")->fetchColumn() ?: 0;

            // Fila (Próximos 5)
            $queue = $pdo->query("
                SELECT a.id, a.appointment_time, p.name as patient_name, d.name as doctor_name, a.status 
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.appointment_date = '{$today}'
                ORDER BY a.appointment_time ASC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);

            return $this->theme->render('admin/dashboard', [
                'theme' => $this->theme,
                'totalPatients' => $totalPatients,
                'totalDoctors' => $totalDoctors,
                'appointmentsToday' => $appointmentsToday,
                'queue' => $queue
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar dashboard: " . $e->getMessage();
        }
    }
}
