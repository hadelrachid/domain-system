<?php

namespace DomainSystem\Plugins\medical_records\Controllers;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Theme\ThemeManager;

class RecordController
{
    private Connection $db;
    private ThemeManager $theme;

    public function __construct(Connection $db, ThemeManager $theme)
    {
        $this->db = $db;
        $this->theme = $theme;
    }

    public function view($appointmentId)
    {
        if (!$appointmentId) die("ID Inválido");

        // Buscar dados do agendamento
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT a.*, p.name as patient_name, p.birthdate as patient_dob, p.cpf, d.name as doctor_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id WHERE a.id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$appointment) die("Agendamento não encontrado.");

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            die("Acesso Negado: Você não tem permissão para acessar este prontuário.");
        }

        // Buscar registro médico (se já existir)
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Se não existir, preparamos vazio
        if (!$record) {
            $record = [
                'id' => null,
                'anamnese' => '',
                'exame_fisico' => '',
                'cid_10' => '',
                'prescricao' => '',
                'evolucao' => ''
            ];
        }

        // Buscar histórico anterior do paciente (para o médico ver)
        $stmtHistory = $pdo->prepare("
            SELECT mr.*, a.appointment_date 
            FROM medical_records mr
            JOIN appointments a ON mr.appointment_id = a.id
            WHERE mr.patient_id = ? AND mr.appointment_id != ?
            ORDER BY a.appointment_date DESC
        ");
        $stmtHistory->execute([$appointment['patient_id'], $appointmentId]);
        $pastRecords = $stmtHistory->fetchAll(\PDO::FETCH_ASSOC);

        // Renderiza a view do plugin e depois injeta no layout do SO
        $stmtExams = $pdo->prepare("SELECT * FROM medical_exams WHERE appointment_id = ? ORDER BY uploaded_at DESC");
        $stmtExams->execute([$appointmentId]);
        $exams = $stmtExams->fetchAll(\PDO::FETCH_ASSOC);

        return $this->theme->render('record', [
            'appointment' => $appointment, 
            'record' => $record,
            'pastRecords' => $pastRecords,
            'exams' => $exams
        ], __DIR__ . '/../views');
    }

    public function save($appointmentId)
    {
        if (!$appointmentId) {
            header("Location: " . BASE_URL . "/admin/appointments?error=ID_INVALIDO");
            exit;
        }

        $pdo = $this->db->getPdo();

        // Buscar o appointment para pegar patient_id e doctor_id
        $stmt = $pdo->prepare("SELECT patient_id, doctor_id FROM appointments WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$appointment) {
             header("Location: " . BASE_URL . "/admin/appointments?error=AGENDAMENTO_INEXISTENTE");
             exit;
        }

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            die("Acesso Negado: Você não tem permissão para editar este prontuário.");
        }

        $anamnese = $_POST['anamnese'] ?? '';
        $exame = $_POST['exame_fisico'] ?? '';
        $cid = $_POST['cid_10'] ?? '';
        $prescricao = $_POST['prescricao'] ?? '';
        $evolucao = $_POST['evolucao'] ?? '';

        // Checar se já existe
        $stmt = $pdo->prepare("SELECT id FROM medical_records WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Update
            $stmt = $pdo->prepare("UPDATE medical_records SET anamnese = ?, exame_fisico = ?, cid_10 = ?, prescricao = ?, evolucao = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$anamnese, $exame, $cid, $prescricao, $evolucao, $exists]);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO medical_records (appointment_id, patient_id, doctor_id, anamnese, exame_fisico, cid_10, prescricao, evolucao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$appointmentId, $appointment['patient_id'], $appointment['doctor_id'], $anamnese, $exame, $cid, $prescricao, $evolucao]);
        }

        // Atualizar status do agendamento para "Atendido" ou "Em Atendimento"
        if (isset($_POST['finalizar'])) {
             $stmt = $pdo->prepare("UPDATE appointments SET status = 'Finalizado' WHERE id = ?");
             $stmt->execute([$appointmentId]);
             header("Location: " . BASE_URL . "/admin/appointments/history?success=Atendimento Finalizado");
        } else {
             $stmt = $pdo->prepare("UPDATE appointments SET status = 'Em Atendimento' WHERE id = ?");
             $stmt->execute([$appointmentId]);
             header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Salvo");
        }
        exit;
    }

    public function printPdf($appointmentId)
    {
        if (!$appointmentId) die("ID Inválido");

        $pdo = $this->db->getPdo();
        
        $stmt = $pdo->prepare("SELECT a.*, p.name as patient_name, p.cpf, d.name as doctor_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id WHERE a.id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$appointment) die("Agendamento não encontrado.");

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            die("Acesso Negado: Você não tem permissão para imprimir este prontuário.");
        }

        $stmt = $pdo->prepare("SELECT prescricao FROM medical_records WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        $prescricao = $record ? $record['prescricao'] : '';

        // Buscar configuracoes globais (Settings)
        $stmt = $pdo->query("SELECT * FROM settings");
        $settingsRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        // Não vamos usar layout do SO para a impressão, ela é uma pág em branco pro papel
        require __DIR__ . '/../views/print.php';
    }

    public function uploadExam($appointmentId)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_FILES['exam_file']['name'])) {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Nenhum arquivo enviado");
            exit;
        }

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$appointment || (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null))) {
            die("Acesso Negado: Permissão insuficiente.");
        }
        
        $uploadDir = dirname(__DIR__, 4) . '/public/uploads/exams/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['exam_file']['name']));
        $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($fileExt, $allowedExts)) {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Extensao invalida");
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['exam_file']['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Tipo de arquivo invalido");
            exit;
        }

        $fileName = time() . '_' . $originalName;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $targetFile)) {
            $stmt = $pdo->prepare("INSERT INTO medical_exams (appointment_id, file_name, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$appointmentId, $_FILES['exam_file']['name'], '/uploads/exams/' . $fileName]);
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Exame anexado com sucesso");
        } else {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Falha no upload");
        }
        exit;
    }

    public function deleteExam($appointmentId)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $examId = $_POST['exam_id'] ?? null;
        
        if ($examId) {
            $pdo = $this->db->getPdo();

            $stmtApp = $pdo->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
            $stmtApp->execute([$appointmentId]);
            $appointment = $stmtApp->fetch(\PDO::FETCH_ASSOC);
            
            if (!$appointment || (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null))) {
                die("Acesso Negado: Permissão insuficiente.");
            }

            $stmt = $pdo->prepare("SELECT file_path FROM medical_exams WHERE id = ? AND appointment_id = ?");
            $stmt->execute([$examId, $appointmentId]);
            $exam = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($exam) {
                $filePath = dirname(__DIR__, 4) . '/public' . $exam['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $stmt = $pdo->prepare("DELETE FROM medical_exams WHERE id = ?");
                $stmt->execute([$examId]);
            }
        }
        
        header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Exame removido");
        exit;
    }
}

