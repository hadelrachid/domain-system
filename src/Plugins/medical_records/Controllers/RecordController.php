<?php

namespace DomainSystem\Plugins\medical_records\Controllers;

use DomainSystem\Plugins\Database\Connection;

class RecordController
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function view($appointmentId)
    {
        if (!$appointmentId) die("ID Inválido");

        // Buscar dados do agendamento
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT a.*, p.name as patient_name, p.birthdate as patient_dob, p.cpf, d.name as doctor_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id WHERE a.id = ?");
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$appointment) die("Agendamento no encontrado.");

        // Buscar registro mdico (se j existir)
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Se no existir, preparamos vazio
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

        require __DIR__ . '/../views/record.php';
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

        $anamnese = $_POST['anamnese'] ?? '';
        $exame = $_POST['exame_fisico'] ?? '';
        $cid = $_POST['cid_10'] ?? '';
        $prescricao = $_POST['prescricao'] ?? '';
        $evolucao = $_POST['evolucao'] ?? '';

        // Checar se j existe
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
             header("Location: " . BASE_URL . "/admin/appointments?success=Atendimento Finalizado");
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

        require __DIR__ . '/../views/print.php';
    }
}
