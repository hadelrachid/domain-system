<?php
namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface;
use Exception;

class DashboardController
{
    private ThemeManager $theme;
    private DashboardRepositoryInterface $repo;

    public function __construct(ThemeManager $theme, DashboardRepositoryInterface $repo)
    {
        $this->theme = $theme;
        $this->repo = $repo;
    }

    public function index(\DomainSystem\Core\Http\Request $request)
    {

        $role = strtolower($_SESSION['user_role'] ?? 'admin');
        $doctorId = $_SESSION['linked_doctor_id'] ?? null;
        
        try {
            $today = date('Y-m-d');

            if ($role === 'doctor') {
                $stats = $this->repo->getDoctorStats((int)$doctorId, $today);
                $queue = $this->repo->getDoctorQueue((int)$doctorId, $today);
                $chartData = $this->repo->getAppointmentsChartData(7, (int)$doctorId);

                return $this->theme->render('dashboard_doctor', [
                    'appointmentsToday' => $stats['appointmentsToday'],
                    'patientsServed' => $stats['patientsServed'],
                    'pendingQueue' => $stats['pendingQueue'],
                    'queue' => $queue,
                    'chartData' => json_encode($chartData)
                ]);
            } else {
                $stats = $this->repo->getGlobalStats($today);
                $queue = $this->repo->getGlobalQueue($today);
                $waitingRoom = $this->repo->getWaitingRoom();
                $chartData = $this->repo->getAppointmentsChartData(7);

                return $this->theme->render('dashboard', [
                    'theme' => $this->theme,
                    'totalPatients' => $stats['totalPatients'],
                    'totalDoctors' => $stats['totalDoctors'],
                    'appointmentsToday' => $stats['appointmentsToday'],
                    'queue' => $queue,
                    'waitingRoom' => $waitingRoom,
                    'role' => $role,
                    'chartData' => json_encode($chartData)
                ]);
            }
        } catch (Exception $e) {
            return "Erro ao renderizar dashboard: " . $e->getMessage();
        }
    }
}
