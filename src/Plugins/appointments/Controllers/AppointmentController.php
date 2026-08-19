<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class AppointmentController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Fetch appointments with patient and doctor names
        // Since QueryBuilder might not support advanced JOINs easily, we fetch via raw PDO or map them in PHP
        $pdo = $this->db->table('appointments')->getPdo();
        $stmt = $pdo->query("
            SELECT a.*, p.name as patient_name, d.name as doctor_name 
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            ORDER BY a.appointment_date DESC, a.appointment_time ASC
        ");
        $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $patients = $this->db->table('patients')->get();
        $doctors = $this->db->table('doctors')->get();

        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_index.php';
        return ob_get_clean();
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $patient_id = $_POST['patient_id'] ?? null;
        $doctor_id = $_POST['doctor_id'] ?? null;
        $appointment_date = $_POST['appointment_date'] ?? null;
        $appointment_time = $_POST['appointment_time'] ?? null;
        $reception_notes = $_POST['reception_notes'] ?? '';

        if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Preencha todos os campos obrigatórios.'];
        } else {
            try {
                $this->db->table('appointments')->insert([
                    'patient_id' => $patient_id,
                    'doctor_id' => $doctor_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'reception_notes' => $reception_notes,
                    'status' => 'Pendente'
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Agendamento criado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/appointments");
        exit;
    }

    public function updateStatus()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;

        if ($id && $status) {
            $allowed_statuses = ['Pendente', 'Confirmado', 'Atendido', 'Cancelado'];
            if (in_array($status, $allowed_statuses)) {
                $this->db->table('appointments')->where('id', '=', $id)->update([
                    'status' => $status
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => "Status atualizado para $status."];
            }
        }

        header("Location: " . BASE_URL . "/admin/appointments");
        exit;
    }

    public function record()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/appointments");
            exit;
        }

        $pdo = $this->db->table('appointments')->getPdo();
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name, p.cpf as patient_cpf, p.birthdate as patient_birthdate, 
                   d.name as doctor_name 
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($appointment)) {
            header("Location: " . BASE_URL . "/admin/appointments");
            exit;
        }

        // Buscar histórico do paciente
        $stmtHistory = $pdo->prepare("
            SELECT a.appointment_date, a.appointment_time, a.status, a.medical_record, d.name as doctor_name
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            WHERE a.patient_id = :patient_id AND a.id != :current_id
            ORDER BY a.appointment_date DESC
        ");
        $stmtHistory->execute([
            'patient_id' => $appointment['patient_id'],
            'current_id' => $id
        ]);
        $history = $stmtHistory->fetchAll(\PDO::FETCH_ASSOC);

        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_record.php';
        return ob_get_clean();
    }

    public function saveRecord()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id = $_POST['id'] ?? null;
        $medical_record = $_POST['medical_record'] ?? '';

        if ($id) {
            $this->db->table('appointments')->where('id', '=', $id)->update([
                'medical_record' => $medical_record,
                'status' => 'Atendido' // Automaticamente muda para Atendido
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Prontuário salvo e consulta marcada como Atendida!'];
        }

        header("Location: " . BASE_URL . "/admin/appointments");
        exit;
    }
}
