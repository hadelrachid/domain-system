<?php

namespace DomainSystem\Plugins\appointments;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\appointments\Controllers\AppointmentController;
use DomainSystem\Plugins\appointments\Controllers\ApiController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->runMigrations();

        /** @var EventDispatcher $events */
        $events = $this->container->make(EventDispatcher::class);

        // Registrar item no menu lateral
        $events->addListener('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin' || $role === 'receptionist') {
                $menus[] = [
                    'title' => 'Agendamentos',
                    'url' => '/admin/appointments',
                    'icon' => '📅'
                ];
            }
            // Histórico (médicos, admin, recepção)
            $menus[] = [
                'title' => 'Histórico',
                'url' => '/admin/appointments/history',
                'icon' => '🗄️'
            ];
            return $menus;
        });

        // Registrar rotas
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/appointments', [AppointmentController::class, 'index'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/appointments', [AppointmentController::class, 'store'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/appointments/status', [AppointmentController::class, 'updateStatus'], 'appointments', ['admin', 'receptionist', 'doctor']);
            
            $router->addRoute('GET', '/admin/appointments/history', [AppointmentController::class, 'history'], 'appointments', ['admin', 'receptionist', 'doctor']);
            // API Routes
            $router->addRoute('POST', '/api/agendamentos', [ApiController::class, 'receiveBooking'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('GET', '/api/test', [ApiController::class, 'testConnection'], 'appointments', ['admin', 'receptionist', 'doctor']);
        });

        // Registrar Shortcodes
        $events->addListener('init', function() {
            if (function_exists('add_shortcode')) {
                add_shortcode('agendamento_form', [AppointmentController::class, 'renderShortcodeBooking'], 'Formulário completo de agendamento.', [
                    'doctor_id' => 'Pré-seleciona um médico (opcional)'
                ]);
            }
        });
    }

    private function runMigrations(): void
    {
        /** @var \DomainSystem\Plugins\Database\Connection $connection */
        $connection = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
        $db = $connection->getPdo();
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time VARCHAR(5) NOT NULL,
                status VARCHAR(50) DEFAULT 'Pendente',
                reception_notes TEXT NULL,
                medical_record TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(patient_id) REFERENCES patients(id),
                FOREIGN KEY(doctor_id) REFERENCES doctors(id)
            )
        ");

        try { $db->exec("ALTER TABLE appointments ADD COLUMN attendance_type VARCHAR(50) DEFAULT 'particular'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE appointments ADD COLUMN health_insurance VARCHAR(100) NULL"); } catch (\Exception $e) {}
    }
}
