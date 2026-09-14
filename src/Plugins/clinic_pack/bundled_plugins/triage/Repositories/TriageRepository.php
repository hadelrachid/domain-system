<?php
namespace DomainSystem\Plugins\triage\Repositories;

use DomainSystem\Plugins\triage\Contracts\TriageRepositoryInterface;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;

class TriageRepository implements TriageRepositoryInterface
{
    private \PDO $pdo;
    private AppointmentRepositoryInterface $appointmentRepo;

    public function __construct(Connection $connection, AppointmentRepositoryInterface $appointmentRepo)
    {
        $this->pdo = $connection->getPdo();
        $this->appointmentRepo = $appointmentRepo;
    }

    public function getAwaitingTriage(): array
    {
        return $this->appointmentRepo->getAwaitingTriage();
    }

    public function getAppointmentData(int $appointmentId): ?array
    {
        return $this->appointmentRepo->getAppointmentDetails($appointmentId);
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
        $this->appointmentRepo->updateStatus($appointmentId, 'Aguardando Médico');
    }
}
