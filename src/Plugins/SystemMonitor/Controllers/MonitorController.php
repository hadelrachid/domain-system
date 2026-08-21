<?php
namespace DomainSystem\Plugins\SystemMonitor\Controllers;

use DomainSystem\Core\Theme\ThemeManager;

class MonitorController
{
    private string $logPath;
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
        $this->logPath = dirname(__DIR__, 4) . '/temp/error_logs.json';
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        // Somente admin pode ver os erros
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            die("Acesso Negado. Apenas administradores/desenvolvedores podem acessar o Monitor.");
        }

        $logs = [];
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $logs = json_decode($content, true) ?: [];
        }

        ob_start();
        require dirname(__DIR__) . '/views/admin_monitor.php';
        $content = ob_get_clean();

        return $this->theme->render('admin/layout', ['content' => $content]);
    }

    public function clear()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            die("Acesso Negado.");
        }

        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        header("Location: " . BASE_URL . "/admin/monitor?cleared=1");
        exit;
    }
}
