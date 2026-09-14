<?php

namespace DomainSystem\Plugins\doctors;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\doctors\Controllers\DoctorController;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // 1. Bind da Infraestrutura (Repositório)
        $this->container->bind(
            \DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface::class,
            \DomainSystem\Plugins\doctors\Repositories\DoctorRepository::class
        );

        // 2. Bind do Fornecedor para Agendamentos (DIP)
        if (interface_exists(\DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class)) {
            $this->container->bind(
                \DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class,
                \DomainSystem\Plugins\doctors\Providers\AppointmentDoctorProvider::class
            );
        }
        
        if (interface_exists(\DomainSystem\Plugins\appointments\Contracts\DoctorScheduleWriterInterface::class)) {
            $this->container->bind(
                \DomainSystem\Plugins\appointments\Contracts\DoctorScheduleWriterInterface::class,
                \DomainSystem\Plugins\doctors\Providers\AppointmentDoctorProvider::class
            );
        }

        /** @var EventDispatcher $events */
        $events = $this->events();

        $events->addListener('workspace.register', function (\DomainSystem\Core\Workspace\WorkspaceManager $wm) {
            $theme = $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
            $wm->registerWorkspace('doctor', new \DomainSystem\Plugins\doctors\Workspace\DoctorWorkspace($theme));
        });

        // Registrar item no menu lateral
        $events->addListener('admin.menu', function($menu) {
            $menu[] = [
                'title' => 'Médicos',
                'url' => '/admin/doctors',
                'icon' => '👨‍⚕️'
            ];
            return $menu;
        });

        // Registrar rotas
        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/doctors', [DoctorController::class, 'index'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors', [DoctorController::class, 'store'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('GET', '/admin/doctors/edit', [DoctorController::class, 'edit'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors/update', [DoctorController::class, 'update'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors/delete', [DoctorController::class, 'delete'], 'doctors', ['admin', 'receptionist']);
            $router->addRoute('POST', '/admin/doctors/sync-wp', [DoctorController::class, 'syncWp'], 'doctors', ['admin', 'receptionist']);
        });
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('doctors', function ($table) {
            $table->id();
            $table->integer('wp_id')->nullable();
            $table->string('name', 100);
            $table->string('crm', 50)->nullable();
            $table->string('specialty', 100)->nullable();
            $table->integer('consultation_time')->default(30);
            $table->string('photo_url', 255)->nullable();
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        });

        $schema->create('doctor_schedules', function ($table) {
            $table->id();
            $table->integer('doctor_id');
            $table->integer('day_of_week');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->integer('slot_duration')->default(30);
            $table->boolean('is_active')->default(1);
            $table->foreign('doctor_id', 'id', 'doctors');
        });
        
        $db = $this->container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        try { $db->exec("ALTER TABLE doctors ADD COLUMN user_id INTEGER NULL"); } catch (\Exception $e) {}
    }
}
