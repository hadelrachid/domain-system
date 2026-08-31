<?php

namespace DomainSystem\Plugins\appointments\Repositories;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;

class SqliteAppointmentRepository implements AppointmentRepositoryInterface
{
    private \PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function getPendingQueue(?string $doctorId = null): array
    {
        $query = "SELECT * FROM appointments WHERE status NOT IN ('Atendido', 'Finalizado', 'Cancelado')";
        $params = [];

        if ($doctorId !== null) {
            $query .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        $query .= " ORDER BY appointment_date ASC, appointment_time ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHistory(?string $doctorId = null, string $searchQuery = ''): array
    {
        $query = "SELECT * FROM appointments WHERE status IN ('Atendido', 'Finalizado')";
        $params = [];

        if ($doctorId !== null) {
            $query .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        $query .= " ORDER BY appointment_date DESC, appointment_time DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // As filtering by patient name/phone requires joins or mapping, 
        // the Controller or Service layer will handle the string searching for now, 
        // OR we just return all matching appointments and let the layer above filter them.
        // Returning all history for this doctor (or all), and letting Controller filter.
        
        return $appointments;
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
}
