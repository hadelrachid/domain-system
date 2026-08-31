<?php

namespace DomainSystem\Plugins\whatsapp\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\whatsapp\Contracts\WhatsAppProviderInterface;
use DomainSystem\Plugins\whatsapp\Contracts\WhatsAppSettingsRepositoryInterface;
use DomainSystem\Core\Http\Request;
use Exception;

class WhatsAppSettingsController
{
    private ThemeManager $theme;
    private WhatsAppProviderInterface $provider;
    private WhatsAppSettingsRepositoryInterface $repository;

    public function __construct(
        ThemeManager $theme, 
        WhatsAppProviderInterface $provider, 
        WhatsAppSettingsRepositoryInterface $repository
    ) {
        $this->theme = $theme;
        $this->provider = $provider;
        $this->repository = $repository;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $settings = $this->repository->getSettings();
        return $this->theme->render('admin_whatsapp', ['settings' => $settings], dirname(__DIR__) . '/views');
    }

    public function save(Request $request = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin/whatsapp");
            exit;
        }

        $input = $request ? $request->request : $_POST;
        $instance = $input['zapi_instance'] ?? '';
        $token = $input['zapi_token'] ?? '';

        $this->repository->saveSettings($instance, $token);

        $_SESSION['success_msg'] = "Configurações da API salvas com sucesso!";
        header("Location: " . BASE_URL . "/admin/whatsapp");
        exit;
    }

    public function testMessage(Request $request = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin/whatsapp");
            exit;
        }

        $input = $request ? $request->request : $_POST;
        $phone = $input['test_phone'] ?? '';
        $message = "🤖 *Cockpit Domain System*\n\nSe você recebeu esta mensagem, sua integração com a API foi configurada com sucesso!";

        try {
            // Garante que o provider receba as credenciais do banco antes de enviar
            $this->provider->setConfig($this->repository->getSettings());
            
            $result = $this->provider->sendMessage($phone, $message);
            if ($result['success']) {
                $_SESSION['success_msg'] = "Mensagem de teste enviada com sucesso!";
            } else {
                $_SESSION['error_msg'] = "Falha ao enviar. Código HTTP: " . $result['http_code'];
            }
        } catch (Exception $e) {
            $_SESSION['error_msg'] = $e->getMessage();
        }

        header("Location: " . BASE_URL . "/admin/whatsapp");
        exit;
    }
}

