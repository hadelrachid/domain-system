<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;
use DomainSystem\Plugins\appointments\Contracts\InsuranceRepositoryInterface;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Core\Application;

class SettingsController
{
    private ThemeManager $theme;
    private SettingRepositoryInterface $settingRepo;
    private DoctorRepositoryInterface $doctorRepo;
    private InsuranceRepositoryInterface $insuranceRepo;
    private UserRepositoryInterface $userRepo;

    public function __construct(
        ThemeManager $theme, 
        SettingRepositoryInterface $settingRepo,
        DoctorRepositoryInterface $doctorRepo,
        InsuranceRepositoryInterface $insuranceRepo,
        UserRepositoryInterface $userRepo
    ) {
        $this->theme = $theme;
        $this->settingRepo = $settingRepo;
        $this->doctorRepo = $doctorRepo;
        $this->insuranceRepo = $insuranceRepo;
        $this->userRepo = $userRepo;
    }

    public function index(Request $request): \DomainSystem\Core\Http\Response
    {
        $rows = $this->settingRepo->getAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        $insurances = $this->insuranceRepo->getAll();
        $doctorsData = $this->doctorRepo->findAll();
        
        // Ensure consistent sorting by ID descending for doctors as before
        usort($doctorsData, function($a, $b) {
            return $b['id'] <=> $a['id'];
        });

        return new \DomainSystem\Core\Http\Response($this->theme->render('settings', [
            'settings' => $settings,
            'insurances' => $insurances,
            'doctors' => $doctorsData
        ], __DIR__ . '/../Views'));
    }

    public function save(Request $request): \DomainSystem\Core\Http\Response
    {
        $allowedKeys = [
            'clinic_name', 
            'whatsapp_api_url', 
            'whatsapp_api_token',
            'smtp_host',
            'smtp_port',
            'smtp_user',
            'smtp_pass'
        ];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $request->request)) {
                $this->settingRepo->upsert($key, $request->input($key));
            }
        }

        header('Location: ' . BASE_URL . '/admin/clinic/settings?success=1');
        exit;
    }

    public function addInsurance(Request $request): \DomainSystem\Core\Http\Response
    {
        $name = $request->input('name'); // In the view we used 'name' not 'insurance_name'
        if (!$name) $name = $request->input('insurance_name'); // Fallback
        
        if ($name) {
            $this->insuranceRepo->add($name);
        }
        header("Location: " . BASE_URL . "/admin/clinic/settings?saved=1&tab=convenios");
        exit;
    }

    public function deleteInsurance(Request $request): \DomainSystem\Core\Http\Response
    {
        $id = $request->input('id');
        if ($id) {
            $this->insuranceRepo->delete((int)$id);
        }
        header("Location: " . BASE_URL . "/admin/clinic/settings?saved=1&tab=convenios");
        exit;
    }
}
