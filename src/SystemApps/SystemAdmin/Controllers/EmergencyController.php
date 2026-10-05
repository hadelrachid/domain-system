<?php

namespace DomainSystem\SystemApps\SystemAdmin\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Contracts\SessionManagerInterface;

class EmergencyController
{
    private ThemeManagerInterface $theme;
    private SessionManagerInterface $session;

    public function __construct(ThemeManagerInterface $theme, SessionManagerInterface $session)
    {
        $this->theme = $theme;
        $this->session = $session;
    }

    public function index()
    {
        return new ViewResponse($this->theme->render('emergency'), false);
    }

    public function login(Request $request)
    {
        $emergencyKey = getenv('EMERGENCY_KEY');
        $inputKey = $request->input('app_key');

        // Log setup
        $logFile = dirname(__DIR__, 5) . '/temp/emergency_access.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');

        // Impede uso se a chave de emergência não estiver configurada no .env
        if (empty($emergencyKey)) {
            $msg = "[$timestamp] [BLOCKED] IP: $ip tentou acesso de emergencia, mas EMERGENCY_KEY nao configurada no servidor.\n";
            file_put_contents($logFile, $msg, FILE_APPEND);
            return $this->theme->render('emergency', ['error' => 'Acesso de Emergência Desativado no Servidor.']);
        }

        // Mitigação contra Timing Attack usando hash_equals
        if (hash_equals($emergencyKey, $inputKey)) {
            if (session_status() === PHP_SESSION_NONE) {

            }
            $this->session->set('user_id', 9999);
            $this->session->set('user_role', 'admin');
            $this->session->set('user_name', 'EMERGENCY_ADMIN');
            
            $msg = "[$timestamp] [CRITICAL_ACCESS_GRANTED] IP: $ip ativou a Escotilha de Emergencia.\n";
            file_put_contents($logFile, $msg, FILE_APPEND);

            header("Location: " . BASE_URL . "/admin/plugins");
            exit;
        }

        $msg = "[$timestamp] [FAILED_ATTEMPT] IP: $ip falhou ao tentar abrir a Escotilha de Emergencia.\n";
        file_put_contents($logFile, $msg, FILE_APPEND);

        return $this->theme->render('emergency', ['error' => 'Chave de Emergência Inválida. Acesso Bloqueado.']);
    }
}
