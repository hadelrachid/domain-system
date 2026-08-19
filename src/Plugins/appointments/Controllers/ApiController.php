<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Plugins\Database\QueryBuilder;

class ApiController
{
    private QueryBuilder $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
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
            echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido ou JSON inválido.']);
            exit;
        }

        $nome = $data['paciente_nome'] ?? '';
        $telefone = $data['paciente_telefone'] ?? '';
        $data_consulta = $data['data_consulta'] ?? ''; // YYYY-MM-DD
        $horario = $data['horario'] ?? ''; // HH:MM
        $queixa = $data['observacoes'] ?? '';
        
        // O theme envia medico_id, vamos usar ou mapear
        $medico_id = !empty($data['medico_id']) ? $data['medico_id'] : null;

        if (empty($nome) || empty($telefone) || empty($data_consulta) || empty($horario)) {
            echo json_encode(['success' => false, 'message' => 'Campos obrigatórios: Nome, Celular, Data e Horário.']);
            exit;
        }

        // Tenta achar paciente pelo telefone (simplificado)
        $patient = $this->db->table('patients')->where('phone', '=', $telefone)->first();
        if (!$patient) {
            $patientId = $this->db->table('patients')->insert([
                'name' => $nome,
                'phone' => $telefone,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $patientId = $patient['id'];
        }

        try {
            $this->db->table('appointments')->insert([
                'patient_id' => $patientId,
                'doctor_id' => $medico_id ?: 1, // fallback provisório
                'appointment_date' => $data_consulta,
                'appointment_time' => $horario,
                'status' => 'Pendente',
                'reception_notes' => $queixa
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Agendamento salvo com sucesso no Domain System.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar agendamento: ' . $e->getMessage()]);
        }
        exit;
    }

    public function testConnection()
    {
        header('Content-Type: application/json');
        header("Access-Control-Allow-Origin: *");
        echo json_encode(['success' => true, 'message' => 'Conexão com o Domain System estabelecida com sucesso!']);
        exit;
    }
}
