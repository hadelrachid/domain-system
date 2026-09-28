<?php

namespace DomainSystem\Plugins\patients;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\patients\Controllers\PatientController;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function getDependencies(): array
    {
        return ['database'];
    }

    // Ignorado pelo OS 2.0
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        // Exige acesso aos serviços vitais fornecidos pelo Kernel (ou outros plugins)
        $os->requireLink('core.db');

        // Pede permissão para escutar canais de eventos
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
        $os->listenHook('admin.menu');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        // 1. Bind da Infraestrutura (Repositório) - Mantido no Container Local
        $this->container->bind(
            \DomainSystem\Plugins\patients\Contracts\PatientRepositoryInterface::class,
            \DomainSystem\Plugins\patients\Repositories\PatientRepository::class
        );

        // 2. Bind do Fornecedor para Agendamentos (DIP)
        if (interface_exists(\DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface::class)) {
            $this->container->bind(
                \DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface::class,
                \DomainSystem\Plugins\patients\Providers\AppointmentPatientProvider::class
            );
        }
        
        if (interface_exists(\DomainSystem\Plugins\appointments\Contracts\PatientWriterInterface::class)) {
            $this->container->bind(
                \DomainSystem\Plugins\appointments\Contracts\PatientWriterInterface::class,
                \DomainSystem\Plugins\patients\Providers\AppointmentPatientProvider::class
            );
        }

        if (interface_exists(\DomainSystem\Plugins\appointments\Contracts\PatientFinderInterface::class)) {
            $this->container->bind(
                \DomainSystem\Plugins\appointments\Contracts\PatientFinderInterface::class,
                \DomainSystem\Plugins\patients\Providers\AppointmentPatientProvider::class
            );
        }
        
        // 3. Ouvindo Hooks (Segurança validada pelo Runtime)
        
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/patients', [PatientController::class, 'index'], 'patients', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/patients', [PatientController::class, 'store'], 'patients', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('GET', '/admin/patients/edit', [PatientController::class, 'edit'], 'patients', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/patients/update', [PatientController::class, 'update'], 'patients', ['admin', 'receptionist', 'doctor']);
            $router->addRoute('POST', '/admin/patients/delete', [PatientController::class, 'delete'], 'patients', ['admin', 'receptionist', 'doctor']);
        });

        // Registrar Shortcodes
        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $shortcodes) {
            $shortcodes->add('paciente_form', [PatientController::class, 'renderShortcodeForm'], 'Formulário de cadastro de paciente.');
            $shortcodes->add('paciente_lista', [PatientController::class, 'renderShortcodeList'], 'Tabela com a lista de pacientes.', [
                'limit' => 'Número máximo de pacientes exibidos',
                'actions' => 'Mostrar coluna de ações (true/false)'
            ]);
        });

        // Adiciona um link no menu lateral do admin
        $runtime->onHook('admin.menu', function(array $menu) {
            $menu[] = [
                'title' => 'Pacientes',
                'url' => '/admin/patients',
                'icon' => '👥',
                'group' => 'clinic'
            ];
            return $menu;
        });
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        $schema->create('patients', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('cpf', 14)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('address')->nullable();
            $table->string('address_number', 20)->nullable();
            $table->string('address_complement', 100)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('insurance_number', 100)->nullable();
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        });
    }
}
