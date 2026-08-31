<?php

namespace DomainSystem\Plugins\settings\Controllers;

use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;
use DomainSystem\Core\Theme\ThemeManager;

class SettingsController
{
    private SettingRepositoryInterface $settingRepo;
    private ThemeManager $theme;

    public function __construct(SettingRepositoryInterface $settingRepo, ThemeManager $theme)
    {
        $this->settingRepo = $settingRepo;
        $this->theme = $theme;
    }

    public function index()
    {


        $rows = $this->settingRepo->getAll();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        return $this->theme->render('admin_settings', ['settings' => $settings], __DIR__ . '/../views');
    }

    public function save()
    {


        $allowedKeys = ['clinic_name', 'clinic_cnpj', 'clinic_slogan', 'clinic_address', 'clinic_phone', 'clinic_whatsapp'];

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $this->settingRepo->upsert($key, $_POST[$key]);
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
                    $this->settingRepo->upsert('clinic_logo', $logoUrl);
                }
            }
        }

        header("Location: " . BASE_URL . "/admin/settings?success=1");
        exit;
    }
}
