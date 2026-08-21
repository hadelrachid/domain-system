<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\appointments\Repositories\AppointmentRepository;

class AppointmentController
{
    private ThemeManager $theme;
    private QueryBuilder $db;
    private AppointmentRepository $repo;

    public function __construct(ThemeManager $theme, QueryBuilder $db, AppointmentRepository $repo)
    {
        $this->theme = $theme;
        $this->db = $db;
        $this->repo = $repo;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctor_id = $_SESSION['linked_doctor_id'] ?? null;
        
        $appointments = $this->repo->getPendingQueue($role, $doctor_id);
        $patients = $this->db->table('patients')->get();
        $doctors = $this->db->table('doctors')->get();

        return $this->theme->render('admin_appointments', [
            'appointments' => $appointments,
            'patients' => $patients,
            'doctors' => $doctors
        ], __DIR__ . '/../views');
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $patient_id = $_POST['patient_id'] ?? '';
        $doctor_id = $_POST['doctor_id'] ?? '';
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';
        $attendance_type = $_POST['attendance_type'] ?? 'Consulta';
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
                    'status' => 'Confirmado'
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
        
        $appointments = $this->repo->getHistory($role, $doctor_id, $search);

        return $this->theme->render('admin_history', [
            'appointments' => $appointments
        ], __DIR__ . '/../views');
    }

    public function record()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/appointments");
            exit;
        }

        $appointment = $this->repo->getRecordDetails($id);
        if (empty($appointment)) {
            header("Location: " . BASE_URL . "/admin/appointments");
            exit;
        }

        $history = $this->repo->getPatientClinicalHistory($appointment['patient_id'], $id);

        return $this->theme->render('admin_record', [
            'appointment' => $appointment,
            'patient_data' => $appointment['patient_data'],
            'history' => $history
        ], __DIR__ . '/../views');
    }

    public function saveRecord()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $id = $_POST['id'] ?? null;
        $medical_record = $_POST['medical_record'] ?? '';

        if ($id) {
            $this->db->table('appointments')->where('id', '=', $id)->update([
                'medical_record' => $medical_record,
                'status' => 'Atendido'
            ]);

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
