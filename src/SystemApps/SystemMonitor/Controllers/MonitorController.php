<?php
namespace DomainSystem\SystemApps\SystemMonitor\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Plugin\Services\ProcessRegistry;
use DomainSystem\Core\Application;

class MonitorController
{
    private string $logPath;
    private ThemeManagerInterface $theme;
    private ?\DomainSystem\Core\Contracts\NotificationManagerInterface $notifManager;

    public function __construct(ThemeManagerInterface $theme, ?\DomainSystem\Core\Contracts\NotificationManagerInterface $notifManager = null)
    {
        $this->theme = $theme;
        $this->notifManager = $notifManager;
        $this->logPath = dirname(__DIR__, 4) . '/temp/error_logs.json';
    }

    public function index()
    {
        // Somente admin pode ver os erros
        $logs = [];
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $logs = json_decode($content, true) ?: [];
        }

        // Carrega a Pilha de Serviços (PIDs) do último boot
        $processes = [];
        $stackPath = dirname(__DIR__, 4) . '/temp/process_stack.json';
        if (file_exists($stackPath)) {
            $processes = json_decode(file_get_contents($stackPath), true) ?: [];
        }

        $systemMemory = $this->getSystemMemoryStats();
        $singletonId = ProcessRegistry::getActiveSingletonId() ?: 'KRN-LIVE';

        return $this->theme->render('admin_monitor', [
            'logs'          => $logs,
            'processes'     => $processes,
            'system_memory' => $systemMemory,
            'singleton_id'  => $singletonId,
        ], dirname(__DIR__) . '/views');
    }

    public function clear()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        $disarmedPath = dirname(__DIR__, 4) . '/temp/disarmed.json';
        if (file_exists($disarmedPath)) {
            unlink($disarmedPath);
        }

        // Encerra o ciclo do Singleton ao limpar os logs/diagnósticos
        Application::kill();

        return \DomainSystem\Core\Http\Response::redirect(BASE_URL . "/admin/monitor?cleared=1");
    }

    public function getStackApi()
    {
        $app = Application::getInstance();
        $processes = [];
        if ($app) {
            $processes = $app->getProcessRegistry()->getProcesses();
        }

        $stackPath = dirname(__DIR__, 4) . '/temp/process_stack.json';
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
     * Coleta métricas de consumo e alocação de memória do PHP e do sistema operacional.
     */
    private function getSystemMemoryStats(): array
    {
        $currentBytes = memory_get_usage(false);
        $allocatedBytes = memory_get_usage(true);
        $peakBytes = memory_get_peak_usage(true);

        $limitStr = ini_get('memory_limit') ?: '128M';
        $limitBytes = $this->parseMemoryLimit($limitStr);

        $percentage = $limitBytes > 0 ? round(($allocatedBytes / $limitBytes) * 100, 1) : 0;

        return [
            'current_mb'   => round($currentBytes / (1024 * 1024), 2),
            'allocated_mb' => round($allocatedBytes / (1024 * 1024), 2),
            'peak_mb'      => round($peakBytes / (1024 * 1024), 2),
            'limit'        => $limitStr,
            'limit_mb'     => round($limitBytes / (1024 * 1024), 2),
            'percentage'   => $percentage,
            'timestamp'    => time(),
            'time_str'     => date('H:i:s'),
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