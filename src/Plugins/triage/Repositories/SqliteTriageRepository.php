<?php
namespace DomainSystem\Plugins\triage\Repositories;

use DomainSystem\Plugins\triage\Contracts\TriageRepositoryInterface;
use DomainSystem\Plugins\Database\Connection;

class SqliteTriageRepository implements TriageRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPdo();
    }

    public function getAwaitingTriage(): array
    {
        $stmt = $this->pdo->query("
            SELECT a.*, p.name as patient_name, d.name as doctor_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.status = 'Aguardando Triagem'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getAppointmentData(int $appointmentId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, p.name as patient_name, p.birthdate, d.name as doctor_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$appointmentId]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function getTriageData(int $appointmentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM triage WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    public function saveTriage(int $appointmentId, array $data): void
    {
        $weight = $data['weight'] ?? null;
        $height = $data['height'] ?? null;
        $bp = $data['blood_pressure'] ?? null;
        $temp = $data['temperature'] ?? null;
        $hr = $data['heart_rate'] ?? null;
        $sp02 = $data['sp02'] ?? null;
        $sugar = $data['blood_sugar'] ?? null;
        $notes = $data['notes'] ?? null;

        // Verifica se já existe
        $stmt = $this->pdo->prepare("SELECT id FROM triage WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $this->pdo->prepare("
                UPDATE triage 
                SET weight=?, height=?, blood_pressure=?, temperature=?, heart_rate=?, sp02=?, blood_sugar=?, notes=? 
                WHERE id=?
            ");
            $stmt->execute([$weight, $height, $bp, $temp, $hr, $sp02, $sugar, $notes, $exists]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO triage (appointment_id, weight, height, blood_pressure, temperature, heart_rate, sp02, blood_sugar, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$appointmentId, $weight, $height, $bp, $temp, $hr, $sp02, $sugar, $notes]);
        }

        // Atualiza o status do agendamento para Aguardando Médico
        $stmt = $this->pdo->prepare("UPDATE appointments SET status = 'Aguardando Médico' WHERE id = ?");
        $stmt->execute([$appointmentId]);
    }
}
