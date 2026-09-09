<?php
namespace DomainSystem\Plugins\triage;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\Database\Connection;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Vincular Contrato à Implementação (SOLID: Injeção de Dependências)
        $this->container->bind(
            \DomainSystem\Plugins\triage\Contracts\TriageRepositoryInterface::class,
            \DomainSystem\Plugins\triage\Repositories\SqliteTriageRepository::class
        );

        /** @var EventDispatcher $events */
        $events = $this->events();

        $events->addListener('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/triage', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'index'], 'triage', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('GET', '/admin/triage/form/{id}', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'form'], 'triage', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/triage/save/{id}', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'save'], 'triage', ['admin', 'receptionist', 'doctor']);
        });

        $events->addListener('admin.menu', function($menus, $role = 'admin') {
            if ($role === 'admin' || $role === 'receptionist') {
                $menus[] = [
                    'title' => 'Triagem',
                    'url' => '/admin/triage',
                    'icon' => '🩺'
                ];
            }
            return $menus;
        });
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('triage', function ($table) {
            $table->id();
            $table->integer('appointment_id');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('sp02')->nullable();
            $table->integer('blood_sugar')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            $table->foreign('appointment_id', 'id', 'appointments');
        });
    }
}
