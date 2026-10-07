<?php
namespace DomainSystem\SystemApps\SystemMonitor\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Core\Theme\ThemeManager;

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

        return $this->theme->render('admin_monitor', [
            'logs'      => $logs,
            'processes' => $processes,
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

        return \DomainSystem\Core\Http\Response::redirect(BASE_URL . "/admin/monitor?cleared=1");
    }

    public function getStackApi()
    {
        $stackPath = dirname(__DIR__, 4) . '/temp/process_stack.json';
        $processes = [];
        $lastModified = 0;
        if (file_exists($stackPath)) {
            $processes = json_decode(file_get_contents($stackPath), true) ?: [];
            $lastModified = filemtime($stackPath);
        }
        return new \DomainSystem\Core\Http\Responses\JsonResponse([
            'success'      => true,
            'processes'    => $processes,
            'lastModified' => $lastModified,
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
}