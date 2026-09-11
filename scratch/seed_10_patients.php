<?php
require_once __DIR__ . '/src/bootstrap.php';

use ClinicPack\Appointments\Repositories\AppointmentRepository;

// Get database connection
\ = \->get(Database\Connection::class)->getPdo();
\ = new AppointmentRepository(\);

// Try to get a valid doctor ID
\ = \->query('SELECT id FROM doctors LIMIT 1');
\ = \->fetch();
\ = \ ? \['id'] : null;

\ = ['João Silva', 'Maria Fernandes', 'Carlos Pereira', 'Ana Beatriz', 'Roberto Costa', 'Fernanda Lima', 'Paulo Souza', 'Juliana Alves', 'Marcos Rocha', 'Camila Santos'];
\ = ['Dor de cabeça forte', 'Check-up anual', 'Dores nas costas', 'Febre há 3 dias', 'Renovação de receita', 'Exame de rotina', 'Dificuldade para dormir', 'Pressão alta', 'Tosse persistente', 'Retorno com exames'];

echo "Gerando 10 pacientes de teste...\n";

for (\ = 0; \ < 10; \++) {
    \ = date('Y-m-d', strtotime('+' . rand(0, 5) . ' days'));
    \ = sprintf('%02d:00', rand(8, 17));
    
    \->createAppointment([
        'patient_name' => \[\],
        'patient_email' => 'teste' . \ . '@email.com',
        'patient_phone' => '1199999888' . \,
        'patient_cpf' => '123456789' . sprintf('%02d', \),
        'attendance_type' => rand(0, 1) ? 'particular' : 'convenio',
        'health_insurance' => rand(0, 1) ? 'Unimed' : '',
        'appointment_date' => \,
        'appointment_time' => \,
        'doctor_id' => \,
        'notes' => \[\],
        'status' => 'Pendente',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo "- " . \[\] . " agendado para " . \ . " às " . \ . "\n";
}

echo "Concluído! Abra o Cockpit da Secretária para ver a fila.\n";
