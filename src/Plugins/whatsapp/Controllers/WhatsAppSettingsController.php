<?php

namespace DomainSystem\Plugins\whatsapp\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\whatsapp\Services\ZApiService;
use Exception;

class WhatsAppSettingsController
{
    private ThemeManager $theme;
    private Connection $db;
    private ZApiService $zapi;

    public function __construct(ThemeManager $theme, Connection $db, ZApiService $zapi)
    {
        $this->theme = $theme;
        $this->db = $db;
        $this->zapi = $zapi;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        $settings = $this->zapi->getSettings();
        return $this->theme->render('admin_whatsapp', ['settings' => $settings], dirname(__DIR__) . '/views');
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin/whatsapp");
            exit;
        }

        $instance = $_POST['zapi_instance'] ?? '';
        $token = $_POST['zapi_token'] ?? '';

        $pdo = $this->db->getPdo();
        
        // Helper para upsert manual no SQLite
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON CONFLICT(key_name) DO UPDATE SET key_value = excluded.key_value");
        $stmt->execute(['zapi_instance', $instance]);
        $stmt->execute(['zapi_token', $token]);

        $_SESSION['success_msg'] = "Configurações da Z-API salvas com sucesso!";
        header("Location: " . BASE_URL . "/admin/whatsapp");
        exit;
    }

    public function testMessage()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin/whatsapp");
            exit;
        }

        $phone = $_POST['test_phone'] ?? '';
        $message = "🤖 *Cockpit Domain System*\n\nSe você recebeu esta mensagem, sua integração com a Z-API foi configurada com sucesso!";

        try {
            $result = $this->zapi->sendMessage($phone, $message);
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

