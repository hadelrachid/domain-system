<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface;

class ApiController
{
    private AppointmentRepositoryInterface $repo;
    private PatientReaderInterface $patientReader;

    public function __construct(AppointmentRepositoryInterface $repo, PatientReaderInterface $patientReader)
    {
        $this->repo = $repo;
        $this->patientReader = $patientReader;
    }

    public function receiveBooking()
    {
        header('Content-Type: application/json');
        
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

        // Lê o JSON bruto enviado pelo wp_remote_post
        $raw_data = file_get_contents("php://input");
        $data = json_decode($raw_data, true);

        if (!$data) {
            return ['success' => false, 'message' => 'Nenhum dado recebido ou JSON inválido.'];
        }

        $nome = $data['paciente_nome'] ?? '';
        $telefone = $data['paciente_telefone'] ?? '';
        $data_consulta = $data['data_consulta'] ?? ''; // YYYY-MM-DD
        $horario = $data['horario'] ?? ''; // HH:MM
        $queixa = $data['observacoes'] ?? '';
        
        // O theme envia medico_id, vamos usar ou mapear
        $medico_id = !empty($data['medico_id']) ? $data['medico_id'] : null;

        if (empty($nome) || empty($telefone) || empty($data_consulta) || empty($horario) || empty($medico_id)) {
            return ['success' => false, 'message' => 'Campos obrigatórios: Nome, Celular, Data, Horário e Médico.'];
        }

        // Tenta achar paciente pelo telefone (simplificado)
        $patient = $this->patientReader->findPatientByPhone($telefone);
        if (!$patient) {
            $patientId = $this->patientReader->createPatient($nome, $telefone);
        } else {
            $patientId = $patient['id'];
        }

        try {
            $this->repo->createAppointment([
                'patient_id' => $patientId,
                'doctor_id' => $medico_id,
                'appointment_date' => $data_consulta,
                'appointment_time' => $horario,
                'status' => 'Pendente',
                'reception_notes' => $queixa
            ]);
            
            return ['success' => true, 'message' => 'Agendamento salvo com sucesso no Domain System.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao salvar agendamento: ' . $e->getMessage()];
        }
    }

    public function testConnection()
    {
        header('Content-Type: application/json');
        header("Access-Control-Allow-Origin: *");
        return ['success' => true, 'message' => 'Conexão com o Domain System estabelecida com sucesso!'];
    }
}

