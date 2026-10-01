<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\PatientFinderInterface;
use DomainSystem\Plugins\appointments\Contracts\PatientWriterInterface;
use DomainSystem\Plugins\appointments\Contracts\BookingCallbackInterface;
use DomainSystem\Plugins\Database\Connection;

class BookingController
{
    private DoctorRepositoryInterface $doctorRepo;
    private AppointmentRepositoryInterface $appointmentRepo;
    private PatientFinderInterface $patientFinder;
    private PatientWriterInterface $patientWriter;
    private BookingCallbackInterface $callback;
    private Connection $connection;
    private ThemeManager $theme;

    public function __construct(
        DoctorRepositoryInterface $doctorRepo,
        AppointmentRepositoryInterface $appointmentRepo,
        PatientFinderInterface $patientFinder,
        PatientWriterInterface $patientWriter,
        BookingCallbackInterface $callback,
        Connection $connection,
        ThemeManager $theme
    ) {
        $this->doctorRepo = $doctorRepo;
        $this->appointmentRepo = $appointmentRepo;
        $this->patientFinder = $patientFinder;
        $this->patientWriter = $patientWriter;
        $this->callback = $callback;
        $this->connection = $connection;
        $this->theme = $theme;
    }

    public function showBookingForm(Request $request): Response
    {
        $doctors = $this->doctorRepo->findAll();

        $specialties = [];
        $doctorsBySpecialty = [];
        
        foreach ($doctors as $doc) {
            $spec = $doc['specialty'] ?: 'Clínico geral';
            if (!in_array($spec, $specialties)) {
                $specialties[] = $spec;
            }
            $doctorsBySpecialty[$spec][] = $doc;
        }
        sort($specialties);

        // TODO: Mover para um HealthInsuranceRepositoryInterface futuramente
        $pdo = $this->connection->getPdo();
        $stmtIns = $pdo->query("SELECT id, name FROM health_insurances WHERE active = 1 ORDER BY id");
        $insurances = $stmtIns->fetchAll(\PDO::FETCH_ASSOC);
        
        $allSchedules = $this->doctorRepo->getAllActiveSchedules();
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
        
        $selectedDoctor = $request->input('medico', '');

        $themePath = dirname(__DIR__, 3) . '/themes/public_booking';
        $this->theme->setActiveThemePath($themePath);
        
        if (!defined('DOMAIN_SYSTEM_ROOT')) {
            define('DOMAIN_SYSTEM_ROOT', dirname(__DIR__, 5));
        }
        
        $html = $this->theme->render('index', [
            'doctors' => $doctors,
            'specialties' => $specialties,
            'doctorsBySpecialty' => $doctorsBySpecialty,
            'insurances' => $insurances,
            'doctorDays' => $doctorDays,
            'selectedDoctor' => $selectedDoctor,
            'theme' => $this->theme,
            'isShortcode' => $request->input('shortcode') == '1',
        ]);
        
        return new Response($html);
    }
    
    public function submitBooking(Request $request): Response
    {
        $name = $request->input('name');
        $phone = $request->input('phone');
        $email = $request->input('email');
        $doctor_id = $request->input('doctor_id');
        $date = $request->input('date');
        $time = $request->input('time', 'A definir');
        $attendance_type = $request->input('attendance_type', 'particular');
        $health_insurance = $request->input('health_insurance', '');
        $notes = $request->input('notes', '');
        
        if (empty($name) || empty($phone) || empty($doctor_id) || empty($date)) {
            return $this->callback->respond($this->callback::ERR_MISSING_FIELDS);
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->callback->respond($this->callback::ERR_INVALID_EMAIL);
        }

        if ($time !== 'A definir') {
            if ($this->appointmentRepo->isSlotOccupied((int)$doctor_id, $date, $time)) {
                return $this->callback->respond($this->callback::ERR_SLOT_OCCUPIED);
            }
        }
        
        $patient = $this->patientFinder->findPatientByEmailOrPhone($email, $phone);
        
        if ($patient) {
            $patientId = (int)$patient['id'];
            
            // Se o nome fornecido for diferente do que está no banco, atualiza o cadastro do paciente
            if (!empty($name) && trim($name) !== trim($patient['name'] ?? '')) {
                $this->patientWriter->updatePatientData($patientId, ['name' => trim($name)]);
            }
        } else {
            $patientId = $this->patientWriter->createPatientFull([
                'name' => trim($name),
                'email' => trim($email ?? ''),
                'phone' => trim($phone),
            ]);
        }

        try {
            $this->appointmentRepo->createAppointment([
                'patient_id' => $patientId,
                'doctor_id' => $doctor_id,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'status' => 'Pendente',
                'reception_notes' => $notes,
                'attendance_type' => $attendance_type,
                'health_insurance' => $health_insurance
            ]);

            // A notificação de agendamento ao médico foi temporariamente removida para ser
            // reimplementada futuramente como um módulo/subplugin assíncrono (Event-Driven).

            return $this->callback->respond($this->callback::SUCCESS_BOOKED, ['message' => 'Agendamento solicitado com sucesso! Entraremos em contato para confirmar.']);
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'message' => 'Erro ao salvar agendamento: ' . $e->getMessage()]);
        }
    }
}
