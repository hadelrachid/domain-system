<?php

namespace DomainSystem\Plugins\settings\Controllers;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Core\Theme\ThemeManager;

class SettingsController
{
    private QueryBuilder $db;
    private ThemeManager $theme;

    public function __construct(QueryBuilder $db, ThemeManager $theme)
    {
        $this->db = $db;
        $this->theme = $theme;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $rows = $this->db->table('settings')->get();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        return $this->theme->render('admin_settings', ['settings' => $settings], __DIR__ . '/../views');
    }

    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }


        $allowedKeys = ['clinic_name', 'clinic_cnpj', 'clinic_slogan', 'clinic_address', 'clinic_phone', 'clinic_whatsapp'];

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $this->db->table('settings')->upsert(
                    ['key_name' => $key, 'key_value' => $_POST[$key]],
                    ['key_name'],
                    ['key_value']
                );
            }
        }

        // Upload de Logo
        if (isset($_FILES['clinic_logo']) && $_FILES['clinic_logo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['clinic_logo']['tmp_name'];
            $mime = mime_content_type($tmpName);
            if ($mime === 'image/png') {
                $uploadDir = BASE_PATH . '/public/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $destPath = $uploadDir . '/logo.png';
                if (move_uploaded_file($tmpName, $destPath)) {
                    $logoUrl = BASE_URL . '/uploads/logo.png?' . time(); // cache buster
                    $this->db->table('settings')->upsert(
                        ['key_name' => 'clinic_logo', 'key_value' => $logoUrl],
                        ['key_name'],
                        ['key_value']
                    );
                }
            }
        }

        header("Location: " . BASE_URL . "/admin/settings?success=1");
        exit;
    }
}
