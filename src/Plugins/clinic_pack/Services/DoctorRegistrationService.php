<?php
namespace DomainSystem\Plugins\clinic_pack\Services;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Events\EventDispatcher;

class DoctorRegistrationService
{
    private Connection $db;
    private EventDispatcher $events;

    public function __construct(Connection $db, EventDispatcher $events)
    {
        $this->db = $db;
        $this->events = $events;
    }

    public function registerDoctor(string $name, string $specialty, string $email, string $crm = null, string $password = null): array
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            // 1. Create the Doctor Profile
            $stmt = $pdo->prepare("INSERT INTO doctors (name, specialty, crm, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$name, $specialty, $crm]);
            $doctorId = $pdo->lastInsertId();

            // 2. Create the User Account
            // If no password is provided, generate a random secure one.
            // The admin can change it later in the Users panel, or the doctor can use 'Forgot Password'.
            $rawPassword = $password ?? bin2hex(random_bytes(4));
            $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
            
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, linked_doctor_id, created_at) VALUES (?, ?, ?, 'doctor', ?, CURRENT_TIMESTAMP)");
            $stmtUser->execute([$name, $email, $hashedPassword, $doctorId]);
            $userId = $pdo->lastInsertId();

            // 3. Link User back to Doctor
            $pdo->prepare("UPDATE doctors SET user_id = ? WHERE id = ?")->execute([$userId, $doctorId]);

            // 4. Create standard schedule (Mon-Fri 09:00 to 18:00)
            $this->createDefaultSchedule($pdo, $doctorId);

            $pdo->commit();

            // Dispatch event so other plugins can create folders or send emails
            $doctorData = ['id' => $doctorId, 'user_id' => $userId, 'name' => $name, 'email' => $email];
            $this->events->dispatch('clinic.doctor.created', $doctorData);

            return ['success' => true, 'doctor_id' => $doctorId, 'user_id' => $userId];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createDefaultSchedule(\PDO $pdo, int $doctorId)
    {
        $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        // Segunda a Sexta (1 a 5)
        for ($i = 1; $i <= 5; $i++) {
            $stmt->execute([$doctorId, $i, '09:00', '18:00', 30]);
        }
    }
}
