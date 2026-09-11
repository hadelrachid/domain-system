<?php

namespace DomainSystem\Plugins\settings\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;

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

        return new \DomainSystem\Core\Http\Response($this->theme->render('admin_settings', [
            'settings' => $settings
        ], __DIR__ . '/../views'));
    }

    public function save(Request $request): \DomainSystem\Core\Http\Response
    {
        $allowedKeys = ['clinic_name', 'clinic_cnpj', 'clinic_slogan', 'clinic_address', 'clinic_phone', 'clinic_whatsapp'];
        
        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                $this->settingRepo->upsert($key, $request->input($key));
            }
        }

        // Handle File Upload (Logo)
        if (isset($_FILES['clinic_logo']) && $_FILES['clinic_logo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['clinic_logo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['clinic_logo']['name'], PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $destDir = DOMAIN_SYSTEM_ROOT . '/public/assets/img';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $destFile = $destDir . '/logo-cockpit.png';
                if (move_uploaded_file($tmpPath, $destFile)) {
                    $this->settingRepo->upsert('clinic_logo', BASE_URL . '/assets/img/logo-cockpit.png?' . time());
                }
            }
        }

        header('Location: ' . BASE_URL . '/admin/settings?success=1');
        exit;
    }

    public function factoryReset(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Http\Response
    {
        ob_start();
        ?>
        <div class="wrap">
            <h1 style="color: #dc3232;">🛠️ Modo de Fábrica (Wipe)</h1>
            <p>O sistema está sendo higienizado e apagado. Por favor, não feche a página.</p>
            <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px; max-width: 600px;">
                <div style="background: #f0f0f1; border-radius: 4px; height: 20px; overflow: hidden; margin-bottom: 10px;">
                    <div id="progress-bar" style="background: #dc3232; width: 0%; height: 100%; transition: width 0.5s;"></div>
                </div>
                <p id="progress-status" style="margin: 0; font-weight: bold; color: #50575e;">Iniciando...</p>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const steps = [
                    "Desativando restrições de chaves estrangeiras...",
                    "Mapeando tabelas do banco de dados...",
                    "Limpando usuários e sessões...",
                    "Dropando tabelas...",
                    "Destruindo arquivo de proteção (installed.lock)...",
                    "Redirecionando para o Assistente de Instalação..."
                ];
                let currentStep = 0;
                let progress = 0;
                
                const interval = setInterval(() => {
                    if (currentStep < steps.length - 1) {
                        document.getElementById('progress-status').innerText = steps[currentStep];
                        progress += 18;
                        document.getElementById('progress-bar').style.width = progress + '%';
                        currentStep++;
                    } else {
                        clearInterval(interval);
                        document.getElementById('progress-status').innerText = steps[steps.length - 1];
                        
                        const formData = new FormData();
                        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

                        fetch('<?= BASE_URL ?>/admin/settings/factory-reset/execute', { 
                            method: 'POST',
                            body: formData
                        })
                            .then(res => res.json())
                            .then(data => {
                                window.location.href = '<?= BASE_URL ?>/setup';
                            });
                    }
                }, 800);
            });
        </script>
        <?php
        $html = ob_get_clean();
        return new \DomainSystem\Core\Http\Response($html);
    }

    public function executeFactoryReset(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Http\Response
    {
        $db = \DomainSystem\Core\Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        try {
            if ($driver === 'sqlite') {
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    if ($table !== 'sqlite_sequence') {
                        $db->exec("DROP TABLE IF EXISTS `$table`");
                    }
                }
            } else {
                $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $db->exec("DROP TABLE `$table`");
                }
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }

            // Remover installed.lock
            $lockFile = DOMAIN_SYSTEM_ROOT . '/config/installed.lock';
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }

            // Destruir sessão
            session_destroy();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
