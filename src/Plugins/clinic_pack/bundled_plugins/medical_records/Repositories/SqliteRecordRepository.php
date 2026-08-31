<?php

namespace DomainSystem\Plugins\medical_records\Repositories;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\medical_records\Contracts\RecordRepositoryInterface;

class SqliteRecordRepository implements RecordRepositoryInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getAppointmentDetails(int $appointmentId): ?array
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name, p.birthdate as patient_dob, p.cpf, d.name as doctor_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$appointmentId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByAppointment(int $appointmentId): ?array
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getPatientHistory(int $patientId, int $excludeAppointmentId): array
    {
        $pdo = $this->db->getPdo();
        $stmtHistory = $pdo->prepare("
            SELECT mr.*, a.appointment_date 
            FROM medical_records mr
            JOIN appointments a ON mr.appointment_id = a.id
            WHERE mr.patient_id = ? AND mr.appointment_id != ?
            ORDER BY a.appointment_date DESC
        ");
        $stmtHistory->execute([$patientId, $excludeAppointmentId]);
        return $stmtHistory->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getExamsByAppointment(int $appointmentId): array
    {
        $pdo = $this->db->getPdo();
        $stmtExams = $pdo->prepare("SELECT * FROM medical_exams WHERE appointment_id = ? ORDER BY uploaded_at DESC");
        $stmtExams->execute([$appointmentId]);
        return $stmtExams->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getExamById(int $examId): ?array
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM medical_exams WHERE id = ?");
        $stmt->execute([$examId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function saveRecord(int $appointmentId, int $patientId, int $doctorId, array $data): void
    {
        $pdo = $this->db->getPdo();
        
        $stmt = $pdo->prepare("SELECT id FROM medical_records WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $exists = $stmt->fetchColumn();

        $anamnese = $data['anamnese'] ?? '';
        $exame = $data['exame_fisico'] ?? '';
        $cid = $data['cid_10'] ?? '';
        $prescricao = $data['prescricao'] ?? '';
        $evolucao = $data['evolucao'] ?? '';

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE medical_records SET anamnese = ?, exame_fisico = ?, cid_10 = ?, prescricao = ?, evolucao = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$anamnese, $exame, $cid, $prescricao, $evolucao, $exists]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO medical_records (appointment_id, patient_id, doctor_id, anamnese, exame_fisico, cid_10, prescricao, evolucao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$appointmentId, $patientId, $doctorId, $anamnese, $exame, $cid, $prescricao, $evolucao]);
        }
    }

    public function attachExam(int $appointmentId, string $fileName, string $filePath): void
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("INSERT INTO medical_exams (appointment_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$appointmentId, $fileName, $filePath]);
    }

    public function deleteExam(int $examId): void
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("DELETE FROM medical_exams WHERE id = ?");
        $stmt->execute([$examId]);
    }

    public function updateAppointmentStatus(int $appointmentId, string $status): void
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $appointmentId]);
    }
}
