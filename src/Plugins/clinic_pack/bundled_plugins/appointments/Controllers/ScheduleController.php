<?php

namespace DomainSystem\Plugins\appointments\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Application;

/**
 * Controller público para consultar horários disponíveis.
 * 
 * GET /api/agendamento/slots?doctor_id=1&date=2026-09-15
 * Retorna: { "slots": ["08:00","08:30","09:00",...], "message": "..." }
 */
class ScheduleController
{
    public function getAvailableSlots(Request $request): Response
    {
        $doctorId = $request->input('doctor_id');
        $date = $request->input('date');

        if (empty($doctorId) || empty($date)) {
            return Response::json([
                'slots' => [],
                'message' => 'Informe o médico e a data.'
            ]);
        }

        // Validar formato da data
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj) {
            return Response::json([
                'slots' => [],
                'message' => 'Data inválida.'
            ]);
        }

        // Não permitir datas passadas
        $today = new \DateTime('today');
        if ($dateObj < $today) {
            return Response::json([
                'slots' => [],
                'message' => 'Não é possível agendar em datas passadas.'
            ]);
        }

        $container = Application::getInstance()->getContainer();
        /** @var \DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface $doctorReader */
        $doctorReader = $container->make(\DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class);
        /** @var \DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface $appointmentRepo */
        $appointmentRepo = $container->make(\DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface::class);

        // O Escriturário (Service) assume todo o trabalho e devolve o resultado final!
        $escriturario = new \DomainSystem\Plugins\appointments\Services\AvailabilityService($doctorReader, $appointmentRepo);
        
        $result = $escriturario->getAvailableSlots((int)$doctorId, $date);

        return Response::json($result);
    }

    /**
     * GET /api/doctors/schedules?doctor_id=1
     * Retorna a grade de horários do médico (quais dias da semana ele trabalha)
     */
    public function getDoctorSchedulesApi(Request $request): Response
    {
        $doctorId = $request->input('doctor_id');
        if (empty($doctorId)) {
            return Response::json(['schedules' => [], 'message' => 'Médico não informado']);
        }

        $container = Application::getInstance()->getContainer();
        /** @var \DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface $doctorReader */
        $doctorReader = $container->make(\DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class);

        $schedules = $doctorReader->getDoctorSchedules((int)$doctorId);
        
        // Se is_active não existir (schema antigo), assume 1. Se existir, deve ser 1.
        $activeSchedules = array_filter($schedules, function($s) {
            if (!isset($s['is_active'])) return true;
            return (int)$s['is_active'] === 1;
        });

        return Response::json([
            'schedules' => array_values($activeSchedules), 
            'success' => true,
            'debug' => $schedules // Temporary debug info
        ]);
    }

    /**
     * GET /admin/doctors/schedule?doctor_id=X
     * Tela para o admin/médico gerenciar a grade de horários.
     */
    public function editSchedule(Request $request): Response
    {
        $container = Application::getInstance()->getContainer();
        /** @var \DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface $doctorReader */
        $doctorReader = $container->make(\DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class);

        $doctors = $doctorReader->getAllDoctors();
        $selectedDoctorId = $request->input('doctor_id', '');
        $schedules = [];

        if ($selectedDoctorId) {
            $schedules = $doctorReader->getDoctorSchedules((int)$selectedDoctorId);
        }

        $viewPath = dirname(__DIR__) . '/views/schedule_edit.php';
        ob_start();
        include $viewPath;
        $html = ob_get_clean();

        return new Response($html);
    }

    /**
     * POST /admin/doctors/schedule/save
     * Salva a grade de horários do médico.
     */
    public function saveSchedule(Request $request): Response
    {
        $container = Application::getInstance()->getContainer();
        /** @var \DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface $doctorReader */
        $doctorReader = $container->make(\DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface::class);

        $doctorId = (int)$request->input('doctor_id');
        if (!$doctorId) {
            return new Response('<p style="color:red">Médico não informado.</p>');
        }

        // Dias da semana
        $days = range(0, 6);
        $schedulesToSave = [];

        foreach ($days as $day) {
            $active = $request->input("day_{$day}_active");
            if (!$active) continue;

            $periods = $request->input("day_{$day}_periods", []);
            if (!is_array($periods)) continue;

            foreach ($periods as $period) {
                $start = $period['start'] ?? '';
                $end = $period['end'] ?? '';
                $slot = (int)($period['slot'] ?? 30);

                if ($start && $end && $slot > 0) {
                    $schedulesToSave[] = [
                        'day_of_week' => $day,
                        'start_time' => $start,
                        'end_time' => $end,
                        'slot_duration' => $slot
                    ];
                }
            }
        }

        $doctorReader->saveDoctorSchedules($doctorId, $schedulesToSave);

        // Redirecionar de volta com mensagem de sucesso
        $redirectUrl = $request->input('redirect');
        if ($redirectUrl) {
            $redirectUrl = strpos($redirectUrl, '?') !== false ? $redirectUrl . '&success=1' : $redirectUrl . '?success=1';
            header("Location: " . BASE_URL . $redirectUrl);
        } else {
            header("Location: " . BASE_URL . "/admin/doctors/schedule?doctor_id={$doctorId}&saved=1");
        }
        exit;
    }
}

