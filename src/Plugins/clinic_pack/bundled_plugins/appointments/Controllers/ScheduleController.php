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

        // Descobrir o dia da semana (0=Dom, 1=Seg...6=Sab)
        $dayOfWeek = (int) $dateObj->format('w');

        // Buscar grade de horários do médico
        $allSchedules = $doctorReader->getDoctorSchedules((int)$doctorId);
        $schedules = array_filter($allSchedules, fn($s) => (int)$s['day_of_week'] === $dayOfWeek && (int)$s['is_active'] === 1);

        if (empty($schedules)) {
            // Dias da semana em PT-BR
            $diasPtBr = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            $diaNome = $diasPtBr[$dayOfWeek] ?? '';
            return Response::json([
                'slots' => [],
                'message' => "Este médico não atende na $diaNome. Escolha outra data."
            ]);
        }

        // Gerar todos os slots possíveis
        $allSlots = [];
        foreach ($schedules as $sched) {
            $slotDuration = (int) ($sched['slot_duration'] ?: 30);
            $start = \DateTime::createFromFormat('H:i', $sched['start_time']);
            $end = \DateTime::createFromFormat('H:i', $sched['end_time']);

            if (!$start || !$end) continue;

            while ($start < $end) {
                $allSlots[] = $start->format('H:i');
                $start->modify("+{$slotDuration} minutes");
            }
        }

        if (empty($allSlots)) {
            return Response::json([
                'slots' => [],
                'message' => 'Nenhum horário configurado para esta data.'
            ]);
        }

        // Remover slots já ocupados (appointments existentes)
        $booked = $appointmentRepo->getBookedSlots((int)$doctorId, $date, $allSlots);

        // Se for hoje, remover horários que já passaram
        $now = new \DateTime();
        $isToday = ($dateObj->format('Y-m-d') === $now->format('Y-m-d'));

        $available = [];
        foreach ($allSlots as $slot) {
            // Slot já está ocupado
            if (in_array($slot, $booked)) continue;

            // Se for hoje, não mostrar slots no passado
            if ($isToday) {
                $slotTime = \DateTime::createFromFormat('H:i', $slot);
                // Adiciona margem de 30min (não pode agendar "agora")
                $slotTime->modify('+30 minutes');
                if ($now > $slotTime) continue;
            }

            $available[] = $slot;
        }

        sort($available);

        if (empty($available)) {
            return Response::json([
                'slots' => [],
                'message' => 'Todos os horários desta data estão ocupados.'
            ]);
        }

        return Response::json([
            'slots' => $available,
            'message' => count($available) . ' horário(s) disponível(is).'
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

