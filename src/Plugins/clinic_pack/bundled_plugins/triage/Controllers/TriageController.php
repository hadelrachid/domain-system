<?php
namespace DomainSystem\Plugins\triage\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Http\Response;

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
            return Response::redirect(BASE_URL . '/admin/triage?error=Agendamento_nao_encontrado');
        }

        // Verificação de Autorização (Básica)
        $authResponse = $this->checkAuthorization($appointment['doctor_id']);
        if ($authResponse) return $authResponse;

        $triage = $this->repository->getTriageData($appointmentId);
        
        return $this->theme->render('admin_triage_form', ['appointment' => $appointment, 'triage' => $triage], __DIR__ . '/../views');
    }

    public function save($appointmentId, \DomainSystem\Core\Http\Request $request = null)
    {
        $appointment = $this->repository->getAppointmentData($appointmentId);
        if (!$appointment) {
            return Response::redirect(BASE_URL . '/admin/triage?error=Agendamento_invalido');
        }
        
        $authResponse = $this->checkAuthorization($appointment['doctor_id']);
        if ($authResponse) return $authResponse;
        
        // Suporte a Request se for injetado, senao fallback para $_POST
        $data = [];
        if ($request) {
            $data = $request->request; // O array que contem $_POST no framework
        } else {
            $data = $_POST;
        }

        $this->repository->saveTriage($appointmentId, $data);

        return Response::redirect(BASE_URL . "/admin/triage?success=Triagem_salva");
    }

    /**
     * Auxiliar para checar se o usuário logado tem permissão
     * Retorna a Response de redirect em caso de falha, ou null em caso de sucesso
     */
    private function checkAuthorization($appointmentDoctorId)
    {
        $userRole = $_SESSION['user_role'] ?? '';
        $doctorId = $_SESSION['doctor_id'] ?? null;
        
        if ($userRole === 'doctor' && $appointmentDoctorId != $doctorId) {
            return Response::redirect(BASE_URL . '/admin/triage?error=Acesso_Negado_Sem_Permissao');
        }
        return null;
    }
}
