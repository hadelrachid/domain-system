<?php

namespace DomainSystem\Plugins\appointments\Repositories;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;

class AppointmentRepository implements AppointmentRepositoryInterface
{
    private \PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function getPendingQueue(?string $doctorId = null): array
    {
        $query = "SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id LEFT JOIN patients p ON a.patient_id = p.id WHERE a.status NOT IN ('Atendido', 'Finalizado', 'Cancelado', 'Confirmado')";
        $params = [];

        if ($doctorId !== null) {
            $query .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHistory(?string $doctorId = null, string $searchQuery = '', ?string $date = null): array
    {
        $query = "SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id LEFT JOIN patients p ON a.patient_id = p.id WHERE a.status IN ('Concluído', 'Concluido', 'Cancelado', 'Cancelada', 'Atendido', 'Finalizado')";
        $params = [];

        if ($date !== 'all') {
            $targetDate = $date ?? date('Y-m-d');
            $query .= " AND a.appointment_date = :target_date";
            $params[':target_date'] = $targetDate;
        }

        if ($doctorId !== null) {
            $query .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        if ($date !== 'all') {
            $query .= " ORDER BY a.appointment_time DESC";
        } else {
            $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        // As filtering by patient name/phone requires joins or mapping, 
        // the Controller or Service layer will handle the string searching for now, 
        // OR we just return all matching appointments and let the layer above filter them.
        // Returning all history for this doctor (or all), and letting Controller filter.

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createAppointment(array $data): void
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO appointments ($fields) VALUES ($placeholders)";
        $stmt = $this->db->prepare($query);
        
        $params = [];
        foreach ($data as $key => $val) {
            $params[':' . $key] = $val;
        }
        
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE appointments SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function isSlotOccupied(int $doctorId, string $date, string $time): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
              AND status NOT IN ('Cancelada', 'Cancelado')
        ");
        $stmt->execute([$doctorId, $date, $time]);
        return $stmt->fetchColumn() > 0;
    }

    public function getBookedSlots(int $doctorId, string $date, array $candidateSlots): array
    {
        if (empty($candidateSlots)) return [];
        
        $placeholders = implode(',', array_fill(0, count($candidateSlots), '?'));
        $stmt = $this->db->prepare("
            SELECT appointment_time 
            FROM appointments 
            WHERE doctor_id = ? 
              AND appointment_date = ? 
              AND appointment_time IN ($placeholders)
              AND status NOT IN ('Cancelada', 'Cancelado')
        ");
        $params = [$doctorId, $date];
        foreach ($candidateSlots as $slot) {
            $params[] = substr($slot, 0, 5); // Ensure it's just HH:MM
        }
        $stmt->execute($params);
        $rawBooked = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        return array_map(function($time) {
            return substr($time, 0, 5); // Just in case any old records have HH:MM:SS
        }, $rawBooked);
    }

    public function getConfirmedAppointmentsByDoctor(int $doctorId): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id LEFT JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.status = 'Confirmado' AND a.appointment_date <= CURRENT_DATE
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllActiveAppointments(): array
    {
        // Secretária vê apenas Pendentes na fila. Se cancelar, continua vendo no dia. Confirmados somem da fila.
        $stmt = $this->db->query("
            SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id LEFT JOIN patients p ON a.patient_id = p.id
            WHERE (a.status = 'Pendente' AND a.appointment_date <= CURRENT_DATE)
               OR (a.status IN ('Cancelado', 'Cancelada') AND a.appointment_date = CURRENT_DATE)
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllConfirmedAppointments(): array
    {
        $stmt = $this->db->query("
            SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.status = 'Confirmado' AND a.appointment_date = CURRENT_DATE
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email FROM appointments a LEFT JOIN doctors d ON a.doctor_id = d.id LEFT JOIN patients p ON a.patient_id = p.id WHERE a.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

