<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;

class SecretaryDashboardController
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
        // Buscar agendamentos via repositório
        $appointments = $this->appointmentRepo->getAllActiveAppointments();
        
        // Buscar lista de médicos via reader
        $doctors = $this->doctorReader->getAllDoctors();
        
        // Buscar dados do usuário logado via repositório
        $userId = $this->session->get('user_id');
        $user = $this->userRepo->findById($userId);
        
        // Buscar histórico para pegar a contagem de faltas
        $history = $this->appointmentRepo->getHistory(null, '', 'today');
        $missedCount = count(array_filter($history, fn($a) => strtolower($a['status']) === 'faltou' || strtolower($a['status']) === 'cancelado'));

        // Buscar histórico completo para contagem geral
        $historyAll = $this->appointmentRepo->getHistory(null, '', 'all');
        $historyAllCount = count($historyAll);

        $this->theme->setActiveThemePath(__DIR__ . '/../themes/cockpit_secretary');
        $html = $this->theme->render('index', [
            'user_id' => $userId,
            'user_name' => $this->session->get('user_name', 'Secretária'),
            'user_email' => $user['email'] ?? '',
            'profile_image' => $user['profile_image'] ?? '',
            'two_factor_type' => $user['two_factor_type'] ?? 'none',
            'two_factor_secret' => $user['two_factor_secret'] ?? '',
            'appointments' => $appointments,
            'doctors' => $doctors,
            'missedCount' => $missedCount,
            'historyAllCount' => $historyAllCount
        ]);
        return new Response($html);
    }
}
