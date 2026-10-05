<?php

namespace DomainSystem\Plugins\settings\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;

class SettingsController
{
    private ThemeManagerInterface $theme;
    private SettingRepositoryInterface $settingRepo;

    private \PDO $db;
    private SessionManagerInterface $session; public function __construct(ThemeManagerInterface $theme, SettingRepositoryInterface $settingRepo, \DomainSystem\SystemApps\Database\Connection $connection, \DomainSystem\Core\Contracts\SessionManagerInterface $session)
    { $this->db = $connection->getPdo();
        $this->session = $session;
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
        $allowedKeys = ['site_name', 'site_cnpj', 'site_slogan', 'site_address', 'site_phone', 'site_whatsapp'];
        
        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                $this->settingRepo->upsert($key, $request->input($key));
            }
        }

        // Handle File Upload (Logo)
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['site_logo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $destDir = DOMAIN_SYSTEM_ROOT . '/public/assets/img';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $destFile = $destDir . '/logo-cockpit.png';
                if (move_uploaded_file($tmpPath, $destFile)) {
                    $this->settingRepo->upsert('site_logo', BASE_URL . '/assets/img/logo-cockpit.png?' . time());
                }
            }
        }

        return \DomainSystem\Core\Http\Response::redirect(BASE_URL . '/admin/settings?success=1');
    }

    public function toggleMaintenance(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Http\Response
    {
        $lockFile = dirname(__DIR__, 4) . '/.maintenance';
        $action = $request->input('action');
        if ($action === 'on') {
            file_put_contents($lockFile, 'maintenance');
        } elseif ($action === 'off') {
            if (file_exists($lockFile)) unlink($lockFile);
        }
        return \DomainSystem\Core\Http\Response::redirect(BASE_URL . '/admin/settings?success=1#tab-avancado');
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
                        formData.append('csrf_token', '<?= $this->session->getCsrfToken() ?>');

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
        try {
            // Drop all tables
            if ($this->db) {
                $db = $this->db;
                $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
                if ($driver === 'mysql') {
                    $db->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    $tables = $db->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
                    foreach ($tables as $table) {
                        $db->exec("DROP TABLE IF EXISTS `$table`");
                    }
                    $db->exec('SET FOREIGN_KEY_CHECKS = 1;');
                } elseif ($driver === 'sqlite') {
                    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
                    foreach ($tables as $table) {
                        if ($table !== 'sqlite_sequence') {
                            $db->exec("DROP TABLE `$table`");
                        }
                    }
                }
            }

            // Apenas destruir o arquivo de lock e limpar config/plugins.json
            $lockFile = DOMAIN_SYSTEM_ROOT . '/config/installed.lock';
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }

            $pluginsConfig = DOMAIN_SYSTEM_ROOT . '/config/plugins.json';
            if (file_exists($pluginsConfig)) {
                unlink($pluginsConfig);
            }

            // Destruir sessão
            session_destroy();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro ao redefinir: ' . $e->getMessage()]);
            exit;
        }
    }
}