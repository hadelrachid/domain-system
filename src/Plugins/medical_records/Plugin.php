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
        $events = $this->container->make(EventDispatcher::class);

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
        /** @var Connection $connection */
        $connection = $this->container->make(Connection::class);
        $db = $connection->getPdo();
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS medical_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                anamnese TEXT NULL,
                exame_fisico TEXT NULL,
                cid_10 TEXT NULL,
                prescricao TEXT NULL,
                evolucao TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (appointment_id) REFERENCES appointments(id),
                FOREIGN KEY (patient_id) REFERENCES patients(id),
                FOREIGN KEY (doctor_id) REFERENCES doctors(id)
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS medical_exams (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments(id)
            )
        ");
    }
}
