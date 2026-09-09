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
        $db = Application::getInstance()
            ->getContainer()
            ->make(\DomainSystem\Plugins\Database\Connection::class)
            ->getPdo();
        
        $stmt = $db->query("SELECT id, name, specialty FROM doctors ORDER BY name");
        $doctors = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

        $stmtIns = $db->query("SELECT id, name FROM health_insurances WHERE active = 1 ORDER BY id");
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
        
        /** @var \DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface $patientReader */
        $patientReader = $container->make(\DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface::class);

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
            return Response::json(['success' => false, 'message' => 'Por favor, preencha os campos obrigatórios.']);
        }

        if ($time !== 'A definir') {
            $timeSql = strlen($time) == 5 ? $time . ':00' : $time;
            if ($appointmentRepo->isSlotOccupied((int)$doctor_id, $date, $timeSql)) {
                return Response::json(['success' => false, 'message' => 'Este horário acabou de ser ocupado. Escolha outro.']);
            }
        }
        
        $patient = $patientReader->findPatientByEmailOrPhone($email, $phone);
        
        if ($patient) {
            $patientId = (int)$patient['id'];
        } else {
            $patientId = $patientReader->createPatientFull([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
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

            try {
                $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
                $stmt = $db->prepare("
                    SELECT d.name as doctor_name, u.email as doctor_email 
                    FROM doctors d
                    LEFT JOIN users u ON d.user_id = u.id
                    WHERE d.id = ?
                ");
                $stmt->execute([$doctor_id]);
                $doctorData = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($doctorData && !empty($doctorData['doctor_email'])) {
                    $docEmail = $doctorData['doctor_email'];
                    $docName = $doctorData['doctor_name'];
                    $dateBr = date('d/m/Y', strtotime($date));
                    $subject = "Novo Agendamento Online - $docName";
                    
                    $message = "Olá Dr(a). $docName,\n\n";
                    $message .= "Você tem um novo agendamento marcado pelo portal online:\n\n";
                    $message .= "Paciente: $name\n";
                    $message .= "Telefone: $phone\n";
                    $message .= "Data: $dateBr\n";
                    $message .= "Horário: $time\n";
                    $message .= "Tipo: " . ucfirst($attendance_type) . "\n";
                    if ($attendance_type === 'convenio') {
                        $message .= "Convênio: $health_insurance\n";
                    }
                    if (!empty($notes)) {
                        $message .= "\nObservações do Paciente:\n$notes\n";
                    }
                    $message .= "\n\nAcesse o sistema para gerenciar sua agenda.";

                    $host = $_SERVER['HTTP_HOST'] ?? 'clinica.com';
                    $headers = "From: nao-responda@" . $host . "\r\n";
                    $headers .= "Reply-To: $email\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    @mail($docEmail, $subject, $message, $headers);
                }
            } catch (\Exception $e) {
                error_log("Erro ao enviar notificação ao médico: " . $e->getMessage());
            }

            return Response::json(['success' => true, 'message' => 'Agendamento solicitado com sucesso! Entraremos em contato para confirmar.']);
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'message' => 'Erro ao salvar agendamento: ' . $e->getMessage()]);
        }
    }
}
