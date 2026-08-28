<?php

namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;

class EmergencyController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function index()
    {
        return $this->theme->render('emergency');
    }

    public function login(Request $request)
    {
        $appKey = getenv('APP_KEY');
        $inputKey = $request->input('app_key');

        if ($appKey && $inputKey === $appKey) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = 9999;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_name'] = 'EMERGENCY_ADMIN';
            
            header("Location: " . BASE_URL . "/admin/plugins");
            exit;
        }

        return $this->theme->render('emergency', ['error' => 'Chave de Emergência Inválida. Acesso Bloqueado.']);
    }
}
