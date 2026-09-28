<?php
namespace DomainSystem\Plugins\triage;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('router.register');
        $os->listenHook('admin.menu');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        // Vincular Contrato à Implementação (SOLID: Injeção de Dependências)
        $this->container->bind(
            \DomainSystem\Plugins\triage\Contracts\TriageRepositoryInterface::class,
            \DomainSystem\Plugins\triage\Repositories\TriageRepository::class
        );

        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/triage', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'index'], 'triage', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('GET', '/admin/triage/form/{id}', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'form'], 'triage', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/triage/save/{id}', [\DomainSystem\Plugins\triage\Controllers\TriageController::class, 'save'], 'triage', ['admin', 'receptionist', 'doctor']);
        });

        $runtime->onHook('admin.menu', function($menus, $role = 'admin') {
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
