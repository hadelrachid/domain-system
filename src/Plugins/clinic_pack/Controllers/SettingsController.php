<?php
namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ThemeManager;

class SettingsController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function index(Request $request): string
    {
        return $this->theme->render("settings", [], __DIR__ . "/../views");
    }

    public function save(Request $request): string
    {
        // Aqui futuramente salvaremos no SettingsRepository
        $clinicName = $_POST['clinic_name'] ?? 'DaherClínica';
        // Redireciona de volta
        header('Location: /domain-system/admin/clinic/settings?success=1');
        exit;
    }
}
