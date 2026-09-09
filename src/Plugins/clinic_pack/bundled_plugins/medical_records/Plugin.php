<?php

namespace DomainSystem\Plugins\medical_records;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\Database\Connection;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        $this->container->bind(
            \DomainSystem\Plugins\medical_records\Contracts\RecordRepositoryInterface::class,
            \DomainSystem\Plugins\medical_records\Repositories\SqliteRecordRepository::class
        );

        /** @var EventDispatcher $events */
        $events = $this->events();

        // Registrar Rota do Pronturio
        $events->addListener('router.register', function(Router $router) {
            // Rota para o Mdico acessar o Pronturio de um Agendamento
            $router->addRoute('GET', '/admin/appointments/record/{id}', [\DomainSystem\Plugins\medical_records\Controllers\RecordController::class, 'view']);
            $router->addRoute('POST', '/admin/appointments/record/{id}', [\DomainSystem\Plugins\medical_records\Controllers\RecordController::class, 'save']);
            
            // PDF/Impresso
            $router->addRoute('GET', '/admin/appointments/record/{id}/print', [\DomainSystem\Plugins\medical_records\Controllers\RecordController::class, 'printPdf']);
            $router->addRoute('POST', '/admin/appointments/record/{id}/upload-exam', [\DomainSystem\Plugins\medical_records\Controllers\RecordController::class, 'uploadExam']);
            $router->addRoute('POST', '/admin/appointments/record/{id}/delete-exam', [\DomainSystem\Plugins\medical_records\Controllers\RecordController::class, 'deleteExam']);
        });
        
        // Em "appointments" nós temos a fila. Podemos injetar um boto de "Atender" via javascript depois,
        // ou adicionar o boto diretamente na view do appointments (isso requer um hook na view de appointments,
        // mas por hora, se o cara for Médico, mudaremos a fila para apontar para /admin/appointments/record/{id}).
    }

    public function activate(): void
    {
        $schema = $this->container->make(\DomainSystem\Plugins\Database\Schema\SchemaBuilder::class);
        
        $schema->create('medical_records', function ($table) {
            $table->id();
            $table->integer('appointment_id');
            $table->integer('patient_id');
            $table->integer('doctor_id');
            $table->text('anamnese')->nullable();
            $table->text('exame_fisico')->nullable();
            $table->text('cid_10')->nullable();
            $table->text('prescricao')->nullable();
            $table->text('evolucao')->nullable();
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            $table->foreign('appointment_id', 'id', 'appointments');
            $table->foreign('patient_id', 'id', 'patients');
            $table->foreign('doctor_id', 'id', 'doctors');
        });

        $schema->create('medical_exams', function ($table) {
            $table->id();
            $table->integer('appointment_id');
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->datetime('uploaded_at')->nullable()->default('CURRENT_TIMESTAMP');
            $table->foreign('appointment_id', 'id', 'appointments');
        });
    }
}
