<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface;
use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Core\Events\EventDispatcher;

class AppointmentController
{
    private ThemeManager $theme;
    private AppointmentRepositoryInterface $repo;
    private EventDispatcher $events;
    private PatientReaderInterface $patientReader;
    private DoctorReaderInterface $doctorReader;

    public function __construct(
        ThemeManager $theme, 
        AppointmentRepositoryInterface $repo, 
        EventDispatcher $events,
        PatientReaderInterface $patientReader,
        DoctorReaderInterface $doctorReader
    ) {
        $this->theme = $theme;
        $this->repo = $repo;
        $this->events = $events;
        $this->patientReader = $patientReader;
        $this->doctorReader = $doctorReader;
    }

    public function index(Request $request): Response
    {
        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctor_id = $_SESSION['linked_doctor_id'] ?? null;
        
        $filterDoctorId = ($role === 'doctor') ? $doctor_id : null;
        $appointmentsRaw = $this->repo->getPendingQueue($filterDoctorId);
        
        $patientsMap = $this->patientReader->getPatientsMap();
        $doctorsMap = $this->doctorReader->getDoctorsMap();

        $appointments = [];
        foreach ($appointmentsRaw as $a) {
            $a['patient_name'] = $patientsMap[$a['patient_id']]['name'] ?? 'Desconhecido';
            $a['patient_phone'] = $patientsMap[$a['patient_id']]['phone'] ?? '';
            $a['doctor_name'] = $doctorsMap[$a['doctor_id']]['name'] ?? 'Desconhecido';
            $a['doctor_specialty'] = $doctorsMap[$a['doctor_id']]['specialty'] ?? '';
            
            $a['status_class'] = 'status-' . strtolower(str_replace(' ', '-', $a['status']));
            try {
                $dateObj = new \DateTime($a['appointment_date'] . ' ' . $a['appointment_time']);
                $a['formatted_date'] = $dateObj->format('d/m/Y');
                $a['formatted_time'] = $dateObj->format('H:i');
            } catch (\Exception $e) {
                $a['formatted_date'] = $a['appointment_date'];
                $a['formatted_time'] = $a['appointment_time'];
            }
            $a['formatted_attendance'] = $a['attendance_type'] === 'conveniado' ? 'Conveniado (' . ($a['health_insurance'] ?: 'Não Informado') . ')' : 'Particular';

            $appointments[] = $a;
        }

        $patients = $this->patientReader->getAllPatients();
        $doctors = $this->doctorReader->getAllDoctors();
        
        $allSchedules = $this->doctorReader->getAllActiveSchedules();
        $doctorDays = [];
        foreach ($allSchedules as $s) {
            $docId = $s['doctor_id'];
            $day = (int)$s['day_of_week'];
            if (!isset($doctorDays[$docId])) {
                $doctorDays[$docId] = [];
            }
            if (!in_array($day, $doctorDays[$docId])) {
                $doctorDays[$docId][] = $day;
            }
        }

        $html = $this->theme->render('admin_appointments', [
            'appointments' => $appointments,
            'patients' => $patients,
            'doctors' => $doctors,
            'doctorDays' => $doctorDays,
            'theme' => $this->theme
        ], __DIR__ . '/../views');
        
        return new Response($html);
    }

    public function store(Request $request): Response
    {
        $patient_id = $request->input('patient_id', '');
        $doctor_id = $request->input('doctor_id', '');
        $appointment_date = $request->input('appointment_date', '');
        $appointment_time = $request->input('appointment_time', '');
        $attendance_type = $request->input('attendance_type', 'Consulta');
        $health_insurance = $request->input('health_insurance', '');
        $reception_notes = $request->input('reception_notes', '');

        if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Preencha todos os campos obrigatórios.'];
        } else {
            try {
                $this->repo->createAppointment([
                    'patient_id' => $patient_id,
                    'doctor_id' => $doctor_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'attendance_type' => $attendance_type,
                    'health_insurance' => $health_insurance,
                    'reception_notes' => $reception_notes,
                    'status' => 'Confirmado'
                ]);

                try {
                    $patient = $this->patientReader->getPatientData((int)$patient_id);
                    $doctorName = $this->doctorReader->getDoctorName((int)$doctor_id);
                    
                    if ($patient) {
                        $this->events->dispatch('appointment.created', [
                            'patient_name' => $patient['name'] ?? 'Paciente',
                            'patient_phone' => $patient['phone'] ?? '',
                            'appointment_date' => $appointment_date,
                            'appointment_time' => $appointment_time,
                            'doctor_name' => $doctorName
                        ]);
                    }
                } catch (\Exception $evtEx) {
                    error_log("Erro ao disparar evento de agendamento: " . $evtEx->getMessage());
                }

                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Agendamento criado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
            }
        }

        return Response::redirect(BASE_URL . '/admin/appointments');
    }

    public function updateStatus(Request $request): Response
    {
        $id = $request->input('id');
        $status = $request->input('status');

        if ($id && $status) {
            $allowed_statuses = ['Pendente', 'Confirmado', 'Aguardando Triagem', 'Aguardando Médico', 'Em Atendimento', 'Finalizado', 'Cancelado'];
            if (in_array($status, $allowed_statuses)) {
                $this->repo->updateStatus((int)$id, $status);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => "Status atualizado para $status."];
            }
        }

        return Response::redirect(BASE_URL . '/admin/appointments');
    }

    public function updateStatusApi(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Http\Response
    {
        $id = $request->input('id');
        $status = $request->input('status');

        if ($id && $status) {
            $allowed_statuses = ['Pendente', 'Confirmado', 'Aguardando Triagem', 'Aguardando Médico', 'Em Atendimento', 'Finalizado', 'Cancelado', 'Concluído'];
            if (in_array($status, $allowed_statuses)) {
                $this->repo->updateStatus((int)$id, $status);
                return \DomainSystem\Core\Http\Response::json(['success' => true]);
            }
        }
        
        return \DomainSystem\Core\Http\Response::json(['success' => false, 'message' => 'Parâmetros inválidos']);
    }

    public function history(Request $request): Response
    {
        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctor_id = $_SESSION['linked_doctor_id'] ?? null;
        $search = strtolower($request->input('s', ''));
        
        $filterDoctorId = ($role === 'doctor') ? $doctor_id : null;
        $appointmentsRaw = $this->repo->getHistory($filterDoctorId, $search, 'all');

        $patientsMap = $this->patientReader->getPatientsMap();
        $doctorsMap = $this->doctorReader->getDoctorsMap();

        $appointments = [];
        foreach ($appointmentsRaw as $a) {
            $a['patient_name'] = $patientsMap[$a['patient_id']]['name'] ?? 'Desconhecido';
            $a['patient_phone'] = $patientsMap[$a['patient_id']]['phone'] ?? '';
            $a['doctor_name'] = $doctorsMap[$a['doctor_id']]['name'] ?? 'Desconhecido';

            if (!empty($search)) {
                $matchName = str_contains(strtolower($a['patient_name']), $search);
                $matchPhone = str_contains(strtolower($a['patient_phone']), $search);
                $matchDate = str_contains($a['appointment_date'], $search);
                
                if (!$matchName && !$matchPhone && !$matchDate) {
                    continue; // Skip this one since it didn't match the search
                }
            }
            
            // Lógica movida da View para o Controller
            $a['status_class'] = 'status-' . strtolower(str_replace(' ', '-', $a['status']));
            try {
                $dateObj = new \DateTime($a['appointment_date'] . ' ' . $a['appointment_time']);
                $a['formatted_date'] = $dateObj->format('d/m/Y');
                $a['formatted_time'] = $dateObj->format('H:i');
            } catch (\Exception $e) {
                $a['formatted_date'] = $a['appointment_date'];
                $a['formatted_time'] = $a['appointment_time'];
            }
            $a['formatted_attendance'] = $a['attendance_type'] === 'conveniado' ? 'Conveniado (' . ($a['health_insurance'] ?: 'Não Informado') . ')' : 'Particular';

            $appointments[] = $a;
        }

        // Limit to 20 like before
        $appointments = array_slice($appointments, 0, 20);

        $html = $this->theme->render('admin_history', [
            'appointments' => $appointments,
            'theme' => $this->theme
        ], __DIR__ . '/../views');
        
        return new Response($html);
    }

    public function renderShortcodeBooking(array $attributes = []): string
    {
        return $this->renderShortcodeBookingPublic($attributes);
    }

    public function renderShortcodeBookingPublic(array $attributes = []): string
    {
        $container = \DomainSystem\Core\Application::getInstance()->getContainer();
        
        // Em vez de duplicar lógica, chamamos o BookingController passando um Request simulado com &shortcode=1
        $request = new \DomainSystem\Core\Http\Request(['shortcode' => '1'], [], [], [], [], []);
        
        $bookingController = $container->make(\DomainSystem\Plugins\appointments\Controllers\BookingController::class);
        $response = $bookingController->showBookingForm($request);
        
        return $response->getContent();
    }
}

