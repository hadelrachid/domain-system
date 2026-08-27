<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\appointments\Repositories\AppointmentRepository;

use DomainSystem\Core\Events\EventDispatcher;

class AppointmentController
{
    private ThemeManager $theme;
    private QueryBuilder $db;
    private AppointmentRepository $repo;
    private EventDispatcher $events;

    public function __construct(ThemeManager $theme, QueryBuilder $db, AppointmentRepository $repo, EventDispatcher $events)
    {
        $this->theme = $theme;
        $this->db = $db;
        $this->repo = $repo;
        $this->events = $events;
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
            'doctors' => $doctors,
            'theme' => $this->theme
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

                // Dispara o evento de "Agendamento Criado" para o sistema
                try {
                    $patient = $this->db->table('patients')->where('id', '=', $patient_id)->first();
                    $doctor = $this->db->table('doctors')->where('id', '=', $doctor_id)->first();
                    
                    if ($patient) {
                        $this->events->dispatch('appointment.created', [
                            'patient_name' => $patient['name'] ?? 'Paciente',
                            'patient_phone' => $patient['phone'] ?? '',
                            'appointment_date' => $appointment_date,
                            'appointment_time' => $appointment_time,
                            'doctor_name' => $doctor['name'] ?? 'Médico'
                        ]);
                    }
                } catch (\Exception $evtEx) {
                    // Protege o agendamento caso algum plugin falhe ao processar o evento
                    error_log("Erro ao disparar evento de agendamento: " . $evtEx->getMessage());
                }

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
            $allowed_statuses = ['Pendente', 'Confirmado', 'Aguardando Triagem', 'Aguardando Médico', 'Em Atendimento', 'Finalizado', 'Cancelado'];
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
            'appointments' => $appointments,
            'theme' => $this->theme
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
            'history' => $history,
            'theme' => $this->theme
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

    /**
     * Renderiza o formulário de marcação de consulta via Shortcode.
     */
    public function renderShortcodeBooking(array $attributes = []): string
    {
        $preSelectedDoctorId = $attributes['doctor_id'] ?? null;
        
        $patients = $this->db->table('patients')->get();
        $doctors = $this->db->table('doctors')->get();

        ob_start();
        include __DIR__ . '/../views/partials/booking_form.php';
        return ob_get_clean();
    }
}

