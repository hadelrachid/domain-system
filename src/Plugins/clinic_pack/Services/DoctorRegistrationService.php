<?php
namespace DomainSystem\Plugins\clinic_pack\Services;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class DoctorRegistrationService
{
    private Connection $db;
    private EventDispatcher $events;
    private UserRepositoryInterface $userRepo;
    private DoctorRepositoryInterface $doctorRepo;

    public function __construct(
        Connection $db, 
        EventDispatcher $events, 
        UserRepositoryInterface $userRepo, 
        DoctorRepositoryInterface $doctorRepo
    ) {
        $this->db = $db;
        $this->events = $events;
        $this->userRepo = $userRepo;
        $this->doctorRepo = $doctorRepo;
    }

    public function registerDoctor(string $name, string $specialty, string $email, string $crm = null, string $password = null): array
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            // 1. Create the Doctor Profile via Repository
            $doctorId = $this->doctorRepo->save([
                'name' => $name,
                'specialty' => $specialty,
                'crm' => $crm,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Create the User Account via Repository
            $rawPassword = $password ?? bin2hex(random_bytes(4));
            $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
            
            $userId = $this->userRepo->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => 'doctor',
                'linked_doctor_id' => $doctorId,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 3. Link User back to Doctor Profile
            $this->doctorRepo->update($doctorId, ['user_id' => $userId]);

            // 4. Create standard schedule (Mon-Fri 09:00 to 18:00)
            $this->createDefaultSchedule($doctorId);

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

    private function createDefaultSchedule(int $doctorId)
    {
        $schedules = [];
        // Segunda a Sexta (1 a 5)
        for ($i = 1; $i <= 5; $i++) {
            $schedules[] = [
                'day_of_week' => $i,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'slot_duration' => 30
            ];
        }
        $this->doctorRepo->saveSchedules($doctorId, $schedules);
    }
}
