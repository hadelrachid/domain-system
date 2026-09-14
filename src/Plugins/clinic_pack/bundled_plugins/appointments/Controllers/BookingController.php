<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Application;
use DomainSystem\Core\Theme\ThemeManager;

class BookingController
{
    public function showBookingForm(Request $request): Response
    {
        $container = Application::getInstance()->getContainer();
        
        /** @var \DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface $doctorRepo */
        $doctorRepo = $container->make(\DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface::class);
        
        $doctors = $doctorRepo->findAll();

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

        // TODO: Mover para um HealthInsuranceRepositoryInterface
        $pdo = $container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        $stmtIns = $pdo->query("SELECT id, name FROM health_insurances WHERE active = 1 ORDER BY id");
        $insurances = $stmtIns->fetchAll(\PDO::FETCH_ASSOC);
        
        $selectedDoctor = $request->input('medico', '');

        $theme = Application::getInstance()->getContainer()->make(ThemeManager::class);
        $themePath = dirname(__DIR__, 3) . '/themes/public_booking';
        $theme->setActiveThemePath($themePath);
        
        if (!defined('DOMAIN_SYSTEM_ROOT')) {
            define('DOMAIN_SYSTEM_ROOT', dirname(__DIR__, 5));
        }
        
        $html = $theme->render('index', [
            'doctors' => $doctors,
            'specialties' => $specialties,
            'doctorsBySpecialty' => $doctorsBySpecialty,
            'insurances' => $insurances,
            'selectedDoctor' => $selectedDoctor,
            'theme' => $theme,
            'isShortcode' => $request->input('shortcode') == '1',
        ]);
        
        return new Response($html);
    }
    
    public function submitBooking(Request $request): Response
    {
        $container = Application::getInstance()->getContainer();
        
        /** @var \DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface $appointmentRepo */
        $appointmentRepo = $container->make(\DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface::class);
        
        /** @var \DomainSystem\Plugins\appointments\Contracts\PatientFinderInterface $patientFinder */
        $patientFinder = $container->make(\DomainSystem\Plugins\appointments\Contracts\PatientFinderInterface::class);

        /** @var \DomainSystem\Plugins\appointments\Contracts\PatientWriterInterface $patientWriter */
        $patientWriter = $container->make(\DomainSystem\Plugins\appointments\Contracts\PatientWriterInterface::class);

        /** @var \DomainSystem\Plugins\appointments\Contracts\BookingCallbackInterface $callback */
        $callback = $container->make(\DomainSystem\Plugins\appointments\Contracts\BookingCallbackInterface::class);

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
            return $callback->respond($callback::ERR_MISSING_FIELDS);
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $callback->respond($callback::ERR_INVALID_EMAIL);
        }

        if ($time !== 'A definir') {
            if ($appointmentRepo->isSlotOccupied((int)$doctor_id, $date, $time)) {
                return $callback->respond($callback::ERR_SLOT_OCCUPIED);
            }
        }
        
        $patient = $patientFinder->findPatientByEmailOrPhone($email, $phone);
        
        if ($patient) {
            $patientId = (int)$patient['id'];
            
            // Se o nome fornecido for diferente do que está no banco, atualiza o cadastro do paciente
            if (!empty($name) && trim($name) !== trim($patient['name'] ?? '')) {
                $patientWriter->updatePatientData($patientId, ['name' => trim($name)]);
            }
        } else {
            $patientId = $patientWriter->createPatientFull([
                'name' => trim($name),
                'email' => trim($email ?? ''),
                'phone' => trim($phone),
            ]);
        }

        try {
            $appointmentRepo->createAppointment([
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

            return $callback->respond($callback::SUCCESS_BOOKED, ['message' => 'Agendamento solicitado com sucesso! Entraremos em contato para confirmar.']);
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'message' => 'Erro ao salvar agendamento: ' . $e->getMessage()]);
        }
    }
}
