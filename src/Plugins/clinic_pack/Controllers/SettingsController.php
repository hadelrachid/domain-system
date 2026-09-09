<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;
use DomainSystem\Core\Application;

class SettingsController
{
    private ThemeManager $theme;
    private SettingRepositoryInterface $settingRepo;

    public function __construct(ThemeManager $theme, SettingRepositoryInterface $settingRepo)
    {
        $this->theme = $theme;
        $this->settingRepo = $settingRepo;
    }

    public function index(Request $request): \DomainSystem\Core\Http\Response
    {
        $rows = $this->settingRepo->getAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        
        $stmtIns = $db->query("SELECT * FROM health_insurances ORDER BY id DESC");
        $insurances = $stmtIns->fetchAll(\PDO::FETCH_ASSOC);

        $stmtDocs = $db->query("SELECT * FROM doctors ORDER BY id DESC");
        $doctors = $stmtDocs->fetchAll(\PDO::FETCH_ASSOC);

        return new \DomainSystem\Core\Http\Response($this->theme->render('settings', [
            'settings' => $settings,
            'insurances' => $insurances,
            'doctors' => $doctors
        ], __DIR__ . '/../views'));
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
            if ($request->has($key)) {
                $this->settingRepo->upsert($key, $request->input($key));
            }
        }

        header('Location: ' . BASE_URL . '/admin/clinic/settings?success=1');
        exit;
    }

    public function addInsurance(Request $request): \DomainSystem\Core\Http\Response
    {
        $name = $request->input('insurance_name');
        if ($name) {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            $stmt = $db->prepare("INSERT INTO health_insurances (name, active) VALUES (?, 1)");
            $stmt->execute([$name]);
        }
        header("Location: " . BASE_URL . "/admin/clinic/settings?saved=1");
        exit;
    }

    public function deleteInsurance(Request $request): \DomainSystem\Core\Http\Response
    {
        $id = $request->input('id');
        if ($id) {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            $db->exec("DELETE FROM health_insurances WHERE id = " . intval($id));
        }
        header("Location: " . BASE_URL . "/admin/clinic/settings?saved=1");
        exit;
    }

    public function addDoctor(Request $request): \DomainSystem\Core\Http\Response
    {
        $name = $request->input('doctor_name');
        $specialty = $request->input('doctor_specialty');
        $email = $request->input('doctor_email');
        if ($name && $email) {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            // Insert Doctor
            $stmt = $db->prepare("INSERT INTO doctors (name, crm, specialty, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, '', $specialty, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
            $doctorId = $db->lastInsertId();

            // Check if user exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                // Generates a random secure password for the newly created user
                $password = bin2hex(random_bytes(4));
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), 'doctor']);
                $userId = $db->lastInsertId();
            }

            // Bind user to doctor
            $stmt = $db->prepare("UPDATE doctors SET user_id = ? WHERE id = ?");
            $stmt->execute([$userId, $doctorId]);
        }
        header("Location: " . BASE_URL . "/admin/clinic/settings?saved=1");
        exit;
    }

    public function deleteDoctor(Request $request): \DomainSystem\Core\Http\Response
    {
        $id = $request->input('doctor_id');
        if ($id) {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            $db->exec("DELETE FROM doctors WHERE id = " . intval($id));
        }
        header("Location: " . BASE_URL . "/admin/clinic/settings?saved=1");
        exit;
    }
}
