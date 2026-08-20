<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class AppointmentController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctor_id = $_SESSION['linked_doctor_id'] ?? null;
        
        $appointmentsRaw = $this->db->table('appointments')->get();
        $patients = $this->db->table('patients')->get();
        $doctors = $this->db->table('doctors')->get();

        $patientsMap = [];
        foreach ($patients as $p) $patientsMap[$p['id']] = $p;

        $doctorsMap = [];
        foreach ($doctors as $d) $doctorsMap[$d['id']] = $d;

        $appointments = [];
        foreach ($appointmentsRaw as $a) {
            if (in_array($a['status'], ['Atendido', 'Finalizado', 'Cancelado'])) {
                continue; // Fica só na fila do Histórico
            }

            if ($role === 'doctor' && (string)$a['doctor_id'] !== (string)$doctor_id) {
                continue; // Médico só vê seus próprios pacientes
            }

            $a['patient_name'] = $patientsMap[$a['patient_id']]['name'] ?? 'Desconhecido';
            $a['patient_phone'] = $patientsMap[$a['patient_id']]['phone'] ?? '';
            $a['doctor_name'] = $doctorsMap[$a['doctor_id']]['name'] ?? 'Desconhecido';
            $appointments[] = $a;
        }

        // Ordenar os agendamentos pela data e hora (os mais próximos primeiro)
        usort($appointments, function($a, $b) {
            return strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) <=> strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
        });

        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_index.php';
        return ob_get_clean();
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $patient_id = $_POST['patient_id'] ?? null;
        $doctor_id = $_POST['doctor_id'] ?? null;
        $appointment_date = $_POST['appointment_date'] ?? null;
        $appointment_time = $_POST['appointment_time'] ?? null;
        $attendance_type = $_POST['attendance_type'] ?? 'particular';
        $health_insurance = $_POST['health_insurance'] ?? '';
        $reception_notes = $_POST['reception_notes'] ?? '';

        if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Preencha todos os campos obrigatórios.'];
        } else {
            try {
                $this->db->table('appointments')->insert([
                    'patient_id' => $patient_id,
                    'doctor_id' => $doctor_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'attendance_type' => $attendance_type,
                    'health_insurance' => $health_insurance,
                    'reception_notes' => $reception_notes,
                    'status' => 'Confirmado' // Agendamento manual da recepção já entra como Confirmado
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Agendamento criado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/appointments");
        exit;
    }

    public function updateStatus()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;

        if ($id && $status) {
            $allowed_statuses = ['Pendente', 'Confirmado', 'Atendido', 'Cancelado'];
            if (in_array($status, $allowed_statuses)) {
                $this->db->table('appointments')->where('id', '=', $id)->update([
                    'status' => $status
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => "Status atualizado para $status."];
            }
        }

        header("Location: " . BASE_URL . "/admin/appointments");
        exit;
    }

    public function history()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctor_id = $_SESSION['linked_doctor_id'] ?? null;

        $search = strtolower($_GET['s'] ?? '');
        
        $appointmentsRaw = $this->db->table('appointments')->get();
        $patients = $this->db->table('patients')->get();
        $doctors = $this->db->table('doctors')->get();

        $patientsMap = [];
        foreach ($patients as $p) $patientsMap[$p['id']] = $p;

        $doctorsMap = [];
        foreach ($doctors as $d) $doctorsMap[$d['id']] = $d;

        $appointments = [];
        foreach ($appointmentsRaw as $a) {
            if (!in_array($a['status'], ['Atendido', 'Finalizado', 'Cancelado'])) {
                continue;
            }

            if ($role === 'doctor' && (string)$a['doctor_id'] !== (string)$doctor_id) {
                continue; // Médico só vê seus próprios pacientes no histórico
            }

            $a['patient_name'] = $patientsMap[$a['patient_id']]['name'] ?? 'Desconhecido';
            $a['patient_phone'] = $patientsMap[$a['patient_id']]['phone'] ?? '';
            $a['doctor_name'] = $doctorsMap[$a['doctor_id']]['name'] ?? 'Desconhecido';

            // Filter by search
            if (!empty($search)) {
                $matchName = str_contains(strtolower($a['patient_name']), $search);
                $matchPhone = str_contains(strtolower($a['patient_phone']), $search);
                $matchDate = str_contains($a['appointment_date'], $search);
                
                if (!$matchName && !$matchPhone && !$matchDate) {
                    continue;
                }
            }

            $appointments[] = $a;
        }

        // Ordenar por data mais recente
        usort($appointments, function($a, $b) {
            return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) <=> strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
        });

        // Limite de 20
        $appointments = array_slice($appointments, 0, 20);

        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_history.php';
        return ob_get_clean();
    }

    public function record()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/appointments");
            exit;
        }

        $appointment = $this->db->table('appointments')->where('id', '=', $id)->first();
        if (empty($appointment)) {
            header("Location: " . BASE_URL . "/admin/appointments");
            exit;
        }

        $patient = $this->db->table('patients')->where('id', '=', $appointment['patient_id'])->first();
        $doctor = $this->db->table('doctors')->where('id', '=', $appointment['doctor_id'])->first();

        $patient_data = $patient; // para usar na view

        $appointment['patient_name'] = $patient['name'] ?? 'Desconhecido';
        $appointment['patient_cpf'] = $patient['cpf'] ?? 'Desconhecido';
        $appointment['patient_birthdate'] = $patient['birthdate'] ?? '1900-01-01';
        $appointment['doctor_name'] = $doctor['name'] ?? 'Desconhecido';

        $historyRaw = $this->db->table('appointments')->where('patient_id', '=', $appointment['patient_id'])->get();
        $history = [];
        foreach ($historyRaw as $h) {
            if ((string)$h['id'] !== (string)$id) {
                $doc = $this->db->table('doctors')->where('id', '=', $h['doctor_id'])->first();
                $h['doctor_name'] = $doc['name'] ?? 'Desconhecido';
                $history[] = $h;
            }
        }

        usort($history, function($a, $b) {
            return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) <=> strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
        });

        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_record.php';
        return ob_get_clean();
    }

    public function saveRecord()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $id = $_POST['id'] ?? null;
        $medical_record = $_POST['medical_record'] ?? '';

        if ($id) {
            // Salvar evolução e mudar status para Atendido
            $this->db->table('appointments')->where('id', '=', $id)->update([
                'medical_record' => $medical_record,
                'status' => 'Atendido'
            ]);

            // Atualizar dados do paciente se fornecidos
            $appointment = $this->db->table('appointments')->where('id', '=', $id)->first();
            if (!empty($appointment)) {
                $patientData = [];
                if (!empty($_POST['patient_cpf'])) $patientData['cpf'] = $_POST['patient_cpf'];
                if (!empty($_POST['patient_birthdate'])) $patientData['birthdate'] = $_POST['patient_birthdate'];
                if (!empty($_POST['insurance_number'])) $patientData['insurance_number'] = $_POST['insurance_number'];
                if (!empty($_POST['zip_code'])) $patientData['zip_code'] = $_POST['zip_code'];
                if (!empty($_POST['address'])) $patientData['address'] = $_POST['address'];

                if (!empty($patientData)) {
                    $this->db->table('patients')->where('id', '=', $appointment['patient_id'])->update($patientData);
                }
            }

            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Prontuário salvo e atendimento finalizado com sucesso!'];
        }

        header("Location: " . BASE_URL . "/admin/appointments");
        exit;
    }
}
