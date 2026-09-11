<?php

namespace DomainSystem\Plugins\appointments;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\appointments\Controllers\AppointmentController;
use DomainSystem\Plugins\appointments\Controllers\ApiController;
use DomainSystem\Plugins\appointments\Controllers\BookingController;
use DomainSystem\Plugins\appointments\Controllers\ScheduleController;

class Plugin extends AbstractPlugin
{
    public function register(): void { 
        /** @var EventDispatcher $events */
        $events = $this->events();

        // Registro de Contratos
        $this->container->bind(
            \DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface::class,
            \DomainSystem\Plugins\appointments\Repositories\AppointmentRepository::class
        );
        $this->container->bind(
            \DomainSystem\Plugins\appointments\Contracts\InsuranceRepositoryInterface::class,
            \DomainSystem\Plugins\appointments\Repositories\InsuranceRepository::class
        );

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
                'icon' => '🕒'
            ];
            return $menus;
        });

        // Registrar rotas
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/appointments', [AppointmentController::class, 'index'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/appointments', [AppointmentController::class, 'store'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/appointments/status', [AppointmentController::class, 'updateStatus'], 'appointments', ['admin', 'receptionist', 'doctor']);
            
            $router->addRoute('GET', '/admin/appointments/history', [AppointmentController::class, 'history'], 'appointments', ['admin', 'receptionist', 'doctor']);
            
            // Admin: Gestão de Horários do Médico
            $router->addRoute('GET', '/admin/doctors/schedule', [ScheduleController::class, 'editSchedule'], 'doctors', ['admin', 'doctor']);
            $router->addRoute('POST', '/admin/doctors/schedule/save', [ScheduleController::class, 'saveSchedule'], 'doctors', ['admin', 'doctor']);
            
            // API Routes
            $router->addRoute('POST', '/cockpit/appointments/status', [\DomainSystem\Plugins\clinic_pack\Controllers\CockpitController::class, 'updateAppointmentStatus'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/api/agendamentos', [ApiController::class, 'receiveBooking'], 'appointments', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('GET', '/api/test', [ApiController::class, 'testConnection'], 'appointments', ['admin', 'receptionist', 'doctor']);

            // Public Routes
            $router->addRoute('GET', '/agendamento', [BookingController::class, 'showBookingForm'], 'public', []);
            $router->addRoute('GET', '/api/agendamento/slots', [ScheduleController::class, 'getAvailableSlots'], 'public', []);
            $router->addRoute('GET', '/api/doctors/schedules', [ScheduleController::class, 'getDoctorSchedulesApi'], 'public', []);
            $router->addRoute('POST', '/api/agendamento/submit', [BookingController::class, 'submitBooking'], 'public', []);
        });

        // Registrar Shortcodes
        $events->addListener('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $shortcodes) {
            $shortcodes->add('agendamento_form', [AppointmentController::class, 'renderShortcodeBooking'], 'Formulário completo de agendamento.', [
                'doctor_id' => 'Pré-seleciona um médico (opcional)'
            ]);
        });
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('appointments', function ($table) {
            $table->id();
            $table->integer('patient_id');
            $table->integer('doctor_id');
            $table->string('appointment_date', 10);
            $table->string('appointment_time', 5);
            $table->string('status', 50)->default('Pendente');
            $table->string('reception_notes', 255)->nullable();
            $table->string('medical_record', 255)->nullable();
            $table->string('attendance_type', 50)->default('particular');
            $table->string('health_insurance', 100)->nullable();
            $table->timestamps();
            $table->foreign('patient_id', 'id', 'patients');
            $table->foreign('doctor_id', 'id', 'doctors');
        });

        $schema->create('health_insurances', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('active')->default(1);
        });
    }
}



