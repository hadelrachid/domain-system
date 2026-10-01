<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;

class DoctorDashboardController
{
    public function __construct(
        private ThemeManager $theme,
        private SessionManager $session,
        private AppointmentRepositoryInterface $appointmentRepo,
        private DoctorReaderInterface $doctorReader,
        private UserRepositoryInterface $userRepo
    ) {}

    public function index(Request $request): Response
    {
        $userId  = $this->session->get('user_id');
        $user    = $this->userRepo->findById($userId);
        $doctorId = $user['linked_doctor_id'] ?? null;

        $doctorName = 'Médico';
        $appointments = [];
        $history_today = [];
        $schedules = [];
        
        if ($doctorId) {
            $doctorName = $this->doctorReader->getDoctorName($doctorId);
            $appointments = $this->appointmentRepo->getConfirmedAppointmentsByDoctor($doctorId);
            $history_today = $this->appointmentRepo->getHistory($doctorId, '', 'today');
            
            if (method_exists($this->doctorReader, 'getDoctorSchedules')) {
                $schedules = $this->doctorReader->getDoctorSchedules($doctorId);
            }
        }

        $this->theme->setActiveThemePath(__DIR__ . '/../themes/cockpit_doctor');
        $html = $this->theme->render('index', [
            'user_id' => $userId,
            'user_name' => $this->session->get('user_name', $doctorName),
            'user_email' => $user['email'] ?? '',
            'profile_image' => $user['profile_image'] ?? '',
            'two_factor_type' => $user['two_factor_type'] ?? 'none',
            'two_factor_secret' => $user['two_factor_secret'] ?? '',
            'doctor_id' => $doctorId,
            'appointments' => $appointments,
            'history_today' => $history_today,
            'schedules' => $schedules
        ]);
        return new Response($html);
    }
}
