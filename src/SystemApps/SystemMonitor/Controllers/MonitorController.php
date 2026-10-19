<?php
namespace DomainSystem\SystemApps\SystemMonitor\Controllers;

use DomainSystem\Core\Contracts\SchemaDoctorInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Monitoring\RequestMetricsRecorder;
use DomainSystem\Core\Plugin\Services\ProcessRegistry;
use DomainSystem\Core\Storage\StateSnapshotManager;
use DomainSystem\Core\Application;

class MonitorController
{
    private string $logPath;
    private string $basePath;
    private ThemeManagerInterface $theme;
    private ?\DomainSystem\Core\Contracts\NotificationManagerInterface $notifManager;
    private RequestMetricsRecorder $metricsRecorder;
    private ?SchemaDoctorInterface $schemaDoctor;
    private StateSnapshotManager $snapshotManager;

    public function __construct(
        ThemeManagerInterface $theme,
        ?\DomainSystem\Core\Contracts\NotificationManagerInterface $notifManager = null,
        ?RequestMetricsRecorder $metricsRecorder = null,
        ?string $basePath = null,
        ?SchemaDoctorInterface $schemaDoctor = null,
        ?StateSnapshotManager $snapshotManager = null
    ) {
        $this->theme = $theme;
        $this->notifManager = $notifManager;
        $this->basePath = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->logPath = rtrim($this->basePath, '/\\') . '/temp/error_logs.json';
        $this->metricsRecorder = $metricsRecorder ?? new RequestMetricsRecorder($this->basePath);
        $this->schemaDoctor = $schemaDoctor;
        $this->snapshotManager = $snapshotManager ?? new StateSnapshotManager($this->basePath);
    }

    public function index()
    {
        $logs = [];
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $logs = json_decode($content, true) ?: [];
        }

        // Carrega a Pilha de Serviços (PIDs) do último boot
        $processes = [];
        $stackPath = rtrim($this->basePath, '/\\') . '/temp/process_stack.json';
        if (file_exists($stackPath)) {
            $processes = json_decode(file_get_contents($stackPath), true) ?: [];
        }

        $systemMemory = $this->getSystemMemoryStats();
        $singletonId = ProcessRegistry::getActiveSingletonId() ?: 'KRN-LIVE';

        $schemaReport = [];
        if ($this->schemaDoctor !== null) {
            try {
                $schemaReport = $this->schemaDoctor->inspectStatus();
            } catch (\Throwable $e) {
                $schemaReport = [];
            }
        }

        $snapshots = $this->snapshotManager->listSnapshots();

        return $this->theme->render('admin_monitor', [
            'logs'          => $logs,
            'processes'     => $processes,
            'system_memory' => $systemMemory,
            'singleton_id'  => $singletonId,
            'schema_report' => $schemaReport,
            'snapshots'     => $snapshots,
        ], dirname(__DIR__) . '/views');
    }

    public function healSchema()
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $stoppedParam = '';
        if ($this->schemaDoctor !== null) {
            try {
                $res = $this->schemaDoctor->healAll();
                if (!empty($res['stopped_at']) && is_array($res['stopped_at'])) {
                    $firstPlugin = (string)array_key_first($res['stopped_at']);
                    $firstMigration = (string)$res['stopped_at'][$firstPlugin];
                    $stoppedParam = '&stopped_at=' . rawurlencode("{$firstPlugin}:{$firstMigration}");
                }
            } catch (\Throwable $e) {
                // Silencioso no redirect
            }
        }
        return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/monitor?healed=1" . $stoppedParam);
    }

    public function createSnapshot(Request $request)
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $label = (string)($request->input('label') ?? 'Snapshot Manual');
        try {
            $this->snapshotManager->createSnapshot($label);
        } catch (\Throwable $e) {
            // Silencioso
        }
        return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/monitor?snap_created=1");
    }

    public function restoreSnapshot(Request $request)
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $snapshotId = (string)($request->input('snapshot_id') ?? '');
        try {
            if ($snapshotId !== '') {
                $this->snapshotManager->restoreSnapshot($snapshotId);
            }
        } catch (\Throwable $e) {
            // Silencioso
        }
        return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/monitor?snap_restored=1");
    }

    /**
     * Limpa exclusivamente o histórico de erros/logs e desarmes, sem matar o ciclo do Kernel (M-05).
     */
    public function clear()
    {
        if (file_exists($this->logPath)) {
            @unlink($this->logPath);
        }

        $disarmedPath = rtrim($this->basePath, '/\\') . '/temp/disarmed.json';
        if (file_exists($disarmedPath)) {
            @unlink($disarmedPath);
        }

        $this->metricsRecorder->clear();

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/monitor?cleared=1");
    }

    /**
     * Reinicia explicitamente o ciclo de vida do Kernel Singleton (novos PIDs no próximo boot) (M-05).
     */
    public function restartKernel()
    {
        ProcessRegistry::killSingleton($this->basePath);
        Application::kill();

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/monitor?restarted=1");
    }

    public function getStackApi()
    {
        $app = Application::getInstance();
        $processes = [];
        if ($app) {
            $processes = $app->getProcessRegistry()->getProcesses();
        }

        $stackPath = rtrim($this->basePath, '/\\') . '/temp/process_stack.json';
        if (empty($processes) && file_exists($stackPath)) {
            $processes = json_decode(file_get_contents($stackPath), true) ?: [];
        }

        $lastModified = file_exists($stackPath) ? filemtime($stackPath) : time();

        return new \DomainSystem\Core\Http\Responses\JsonResponse([
            'success'       => true,
            'processes'     => $processes,
            'lastModified'  => $lastModified,
            'singleton_id'  => ProcessRegistry::getActiveSingletonId() ?: 'KRN-LIVE',
            'system_memory' => $this->getSystemMemoryStats(),
        ]);
    }

    public function clearApi()
    {
        try {
            if ($this->notifManager) {
                $this->notifManager->clear();
            }
            return new \DomainSystem\Core\Http\Responses\JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new \DomainSystem\Core\Http\Responses\JsonResponse(['success' => false]);
        }
    }

    /**
     * Coleta métricas de consumo e alocação de memória do worker atual e picos reais registrados (H-05).
     */
    private function getSystemMemoryStats(): array
    {
        $currentBytes = memory_get_usage(false);
        $allocatedBytes = memory_get_usage(true);
        $workerPeakBytes = memory_get_peak_usage(true);

        $workerPeakMb = round($workerPeakBytes / (1024 * 1024), 2);
        $recentPeakMb = $this->metricsRecorder->getMaxRecentPeakMb();
        $effectivePeakMb = max($workerPeakMb, $recentPeakMb);

        $limitStr = ini_get('memory_limit') ?: '128M';
        $limitBytes = $this->parseMemoryLimit($limitStr);

        $percentage = $limitBytes > 0 ? round(($allocatedBytes / $limitBytes) * 100, 1) : 0;

        return [
            'current_mb'      => round($currentBytes / (1024 * 1024), 2),
            'allocated_mb'    => round($allocatedBytes / (1024 * 1024), 2),
            'peak_mb'         => $effectivePeakMb,
            'worker_peak_mb'  => $workerPeakMb,
            'recent_peak_mb'  => $recentPeakMb,
            'recent_requests' => $this->metricsRecorder->getRecentEntries(),
            'limit'           => $limitStr,
            'limit_mb'        => round($limitBytes / (1024 * 1024), 2),
            'percentage'      => $percentage,
            'timestamp'       => time(),
            'time_str'        => date('H:i:s'),
        ];
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '-1') {
            return 2 * 1024 * 1024 * 1024; // 2GB fallback para ilimitado
        }
        $last = strtolower($limit[strlen($limit) - 1]);
        $val = (int) $limit;
        switch ($last) {
            case 'g':
                $val *= 1024;
                // fall through
            case 'm':
                $val *= 1024;
                // fall through
            case 'k':
                $val *= 1024;
                break;
        }
        return $val > 0 ? $val : 128 * 1024 * 1024;
    }
}