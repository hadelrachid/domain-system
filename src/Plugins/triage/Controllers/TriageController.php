<?php
namespace DomainSystem\Plugins\triage\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\Connection;

class TriageController
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
        
        $pdo = $this->db->getPdo();
        // Buscar pacientes Aguardando Triagem (ordenado por mais antigo primeiro)
        $stmt = $pdo->query("
            SELECT a.*, p.name as patient_name, d.name as doctor_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.status = 'Aguardando Triagem'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $this->theme->render('admin_triage', ['appointments' => $appointments], __DIR__ . '/../views');
    }

    public function form($appointmentId)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name, p.birthdate, d.name as doctor_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$appointment) {
            die("Agendamento não encontrado.");
        }

        $userRole = $_SESSION['user_role'] ?? '';
        $doctorId = $_SESSION['doctor_id'] ?? null;
        if (!in_array($userRole, ['admin', 'receptionist']) && !($userRole === 'doctor' && $appointment['doctor_id'] == $doctorId)) {
            die("Acesso Negado.");
        }

        $stmt = $pdo->prepare("SELECT * FROM triage WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $triage = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        
        return $this->theme->render('admin_triage_form', ['appointment' => $appointment, 'triage' => $triage], __DIR__ . '/../views');
    }

    public function save($appointmentId)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $pdo = $this->db->getPdo();
        
        $stmtApp = $pdo->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
        $stmtApp->execute([$appointmentId]);
        $appointment = $stmtApp->fetch(\PDO::FETCH_ASSOC);
        if (!$appointment) die("Agendamento inválido.");
        
        $userRole = $_SESSION['user_role'] ?? '';
        $doctorId = $_SESSION['doctor_id'] ?? null;
        if (!in_array($userRole, ['admin', 'receptionist']) && !($userRole === 'doctor' && $appointment['doctor_id'] == $doctorId)) {
            die("Acesso Negado.");
        }
        
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $height = !empty($_POST['height']) ? $_POST['height'] : null;
        $bp = $_POST['blood_pressure'] ?? null;
        $temp = !empty($_POST['temperature']) ? $_POST['temperature'] : null;
        $hr = !empty($_POST['heart_rate']) ? $_POST['heart_rate'] : null;
        $sp02 = !empty($_POST['sp02']) ? $_POST['sp02'] : null;
        $sugar = !empty($_POST['blood_sugar']) ? $_POST['blood_sugar'] : null;
        $notes = $_POST['notes'] ?? null;

        // Verifica se já existe
        $stmt = $pdo->prepare("SELECT id FROM triage WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE triage SET weight=?, height=?, blood_pressure=?, temperature=?, heart_rate=?, sp02=?, blood_sugar=?, notes=? WHERE id=?");
            $stmt->execute([$weight, $height, $bp, $temp, $hr, $sp02, $sugar, $notes, $exists]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO triage (appointment_id, weight, height, blood_pressure, temperature, heart_rate, sp02, blood_sugar, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$appointmentId, $weight, $height, $bp, $temp, $hr, $sp02, $sugar, $notes]);
        }

        // Atualiza o status do agendamento para Aguardando Médico
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Aguardando Médico' WHERE id = ?");
        $stmt->execute([$appointmentId]);

        header("Location: " . BASE_URL . "/admin/triage?success=Triagem salva");
        exit;
    }
}



