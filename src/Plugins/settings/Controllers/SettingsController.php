<?php

namespace DomainSystem\Plugins\settings\Controllers;

use DomainSystem\Plugins\Database\Connection;

use DomainSystem\Core\Theme\ThemeManager;

class SettingsController
{
    private Connection $db;
    private ThemeManager $theme;

    public function __construct(Connection $db, ThemeManager $theme)
    {
        $this->db = $db;
        $this->theme = $theme;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            die("Acesso Negado.");
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->query("SELECT * FROM settings");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        return $this->theme->render('admin_settings', ['settings' => $settings], __DIR__ . '/../views');
    }

    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            die("Acesso Negado.");
        }

        $pdo = $this->db->getPdo();

        $allowedKeys = ['clinic_name', 'clinic_cnpj', 'clinic_slogan', 'clinic_address', 'clinic_phone', 'clinic_whatsapp'];

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON CONFLICT(key_name) DO UPDATE SET key_value = excluded.key_value");
                $stmt->execute([$key, $val]);
            }
        }

        // Upload de Logo
        if (isset($_FILES['clinic_logo']) && $_FILES['clinic_logo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['clinic_logo']['tmp_name'];
            $mime = mime_content_type($tmpName);
            if ($mime === 'image/png') {
                $uploadDir = dirname(__DIR__, 4) . '/public/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $destPath = $uploadDir . '/logo.png';
                if (move_uploaded_file($tmpName, $destPath)) {
                    $logoUrl = BASE_URL . '/uploads/logo.png?' . time(); // cache buster
                    $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES ('clinic_logo', ?) ON CONFLICT(key_name) DO UPDATE SET key_value = excluded.key_value");
                    $stmt->execute([$logoUrl]);
                }
            }
        }

        header("Location: " . BASE_URL . "/admin/settings?success=1");
        exit;
    }
}
