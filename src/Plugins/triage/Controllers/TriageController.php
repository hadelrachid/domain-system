<?php
namespace DomainSystem\Plugins\triage\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\Connection;

class TriageController
{
    private ThemeManager $theme;
    private \DomainSystem\Plugins\triage\Contracts\TriageRepositoryInterface $repository;

    public function __construct(ThemeManager $theme, \DomainSystem\Plugins\triage\Contracts\TriageRepositoryInterface $repository)
    {
        $this->theme = $theme;
        $this->repository = $repository;
    }

    public function index()
    {
        $appointments = $this->repository->getAwaitingTriage();
        return $this->theme->render('admin_triage', ['appointments' => $appointments], __DIR__ . '/../views');
    }

    public function form($appointmentId)
    {
        $appointment = $this->repository->getAppointmentData($appointmentId);

        if (!$appointment) {
            die("Agendamento não encontrado.");
        }

        // Verificação de Autorização (Básica)
        $this->checkAuthorization($appointment['doctor_id']);

        $triage = $this->repository->getTriageData($appointmentId);
        
        return $this->theme->render('admin_triage_form', ['appointment' => $appointment, 'triage' => $triage], __DIR__ . '/../views');
    }

    public function save($appointmentId, \DomainSystem\Core\Http\Request $request = null)
    {
        $appointment = $this->repository->getAppointmentData($appointmentId);
        if (!$appointment) {
            die("Agendamento inválido.");
        }
        
        $this->checkAuthorization($appointment['doctor_id']);
        
        // Suporte a Request se for injetado, senao fallback para $_POST
        $data = [];
        if ($request) {
            $data = $request->request; // O array que contem $_POST no framework
        } else {
            $data = $_POST;
        }

        $this->repository->saveTriage($appointmentId, $data);

        header("Location: " . BASE_URL . "/admin/triage?success=Triagem salva");
        exit;
    }

    /**
     * Auxiliar para checar se o usuário logado tem permissão
     */
    private function checkAuthorization($appointmentDoctorId): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRole = $_SESSION['user_role'] ?? '';
        $doctorId = $_SESSION['doctor_id'] ?? null;
        
        if (!in_array($userRole, ['admin', 'receptionist']) && !($userRole === 'doctor' && $appointmentDoctorId == $doctorId)) {
            die('<div style="padding:20px; text-align:center; font-family:sans-serif;"><h2>Acesso Negado 🛑</h2><p>Você não tem permissão para triar este paciente.</p><a href="'.BASE_URL.'/admin/triage">Voltar</a></div>');
        }
    }
}



