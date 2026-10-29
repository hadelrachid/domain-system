<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\settings\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\Update\MaintenanceModeInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Update\Infrastructure\FileMaintenanceMode;
use DomainSystem\Plugins\settings\Contracts\SettingRepositoryInterface;

class SettingsController
{
    /**
     * Lista estrita de tabelas gerenciadas pelo Domain System (H-03).
     * Jamais executa SHOW TABLES -> DROP TABLE irrestrito em bancos compartilhados.
     */
    public const MANAGED_TABLES = [
        'user_roles',
        'role_capabilities',
        'capabilities',
        'roles',
        'users',
        'settings',
        'pages',
        'theme_menu_items',
        'theme_menus',
        'menu_items',
        'menus',
        'academy_lessons',
        'academy_modules',
        'academy_courses',
        'analytics_visits',
        'legal_cases',
        'kernel_migrations',
    ];

    private ThemeManagerInterface $theme;
    private SettingRepositoryInterface $settingRepo;
    private \PDO $db;
    private SessionManagerInterface $session;
    private string $basePath;
    private MaintenanceModeInterface $maintenanceMode;

    public function __construct(
        ThemeManagerInterface $theme,
        SettingRepositoryInterface $settingRepo,
        \DomainSystem\SystemApps\Database\Connection $connection,
        SessionManagerInterface $session,
        ?string $basePath = null,
        ?MaintenanceModeInterface $maintenanceMode = null
    ) {
        $this->db = $connection->getPdo();
        $this->session = $session;
        $this->theme = $theme;
        $this->settingRepo = $settingRepo;
        $this->basePath = rtrim($basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4)), '/\\');
        $this->maintenanceMode = $maintenanceMode ?? new FileMaintenanceMode($this->basePath);
    }

    public function index(Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $rows = $this->settingRepo->getAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        return new ViewResponse($this->theme->render('admin_settings', [
            'settings' => $settings
        ], __DIR__ . '/../views'));
    }

    public function save(Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $allowedKeys = ['site_name', 'site_cnpj', 'site_slogan', 'site_address', 'site_phone', 'site_whatsapp', 'site_locale'];
        
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
                $destDir = $this->basePath . '/public/assets/img';
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

    public function toggleMaintenance(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $action = $request->input('action');
        if ($action === 'on') {
            $this->maintenanceMode->enable('maintenance');
        } elseif ($action === 'off') {
            $this->maintenanceMode->disable();
        }
        return \DomainSystem\Core\Http\Response::redirect(BASE_URL . '/admin/settings?success=1#tab-avancado');
    }

    public function factoryReset(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $csrfToken = htmlspecialchars((string)$this->session->getCsrfToken(), ENT_QUOTES, 'UTF-8');

        ob_start();
        ?>
        <div class="wrap">
            <h1 style="color: #dc3232;">🛠️ Modo de Fábrica (Wipe Seguro)</h1>
            <p>Esta operação apagará apenas as tabelas gerenciadas pelo Domain System. Para continuar, confirme sua senha de administrador.</p>
            <div style="background: #1d2327; padding: 24px; border: 1px solid rgba(220,50,50,0.4); border-radius: 8px; max-width: 600px; color: #fff;">
                <div id="auth-box" style="margin-bottom: 18px;">
                    <label for="admin_password" style="display:block; margin-bottom:8px; font-weight:bold;">Senha atual do Administrador *</label>
                    <input type="password" id="admin_password" required placeholder="Digite sua senha para confirmar o Wipe" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box; margin-bottom:14px;">
                    <button type="button" id="btn-start-wipe" onclick="startFactoryReset()" class="btn btn-deactivate" style="background:#dc3232; color:#fff; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
                        Confirmar e Executar Wipe
                    </button>
                </div>

                <div id="progress-container" style="display:none;">
                    <div style="background: #f0f0f1; border-radius: 4px; height: 20px; overflow: hidden; margin-bottom: 10px;">
                        <div id="progress-bar" style="background: #dc3232; width: 0%; height: 100%; transition: width 0.5s;"></div>
                    </div>
                    <p id="progress-status" style="margin: 0; font-weight: bold; color: #c5c6c7;">Iniciando...</p>
                </div>
                <p id="reset-error" style="display:none; margin-top:12px; color:#ff8080; font-weight:bold;"></p>
            </div>
        </div>
        <script>
            function startFactoryReset() {
                const passInput = document.getElementById('admin_password');
                const errorEl = document.getElementById('reset-error');
                errorEl.style.display = 'none';

                if (!passInput || !passInput.value) {
                    errorEl.innerText = 'Informe sua senha de administrador para confirmar.';
                    errorEl.style.display = 'block';
                    return;
                }

                document.getElementById('auth-box').style.display = 'none';
                document.getElementById('progress-container').style.display = 'block';

                const steps = [
                    "Validando credenciais do administrador...",
                    "Mapeando tabelas gerenciadas do sistema...",
                    "Limpando tabelas do Domain System...",
                    "Destruindo arquivo de proteção (installed.lock)...",
                    "Encerrando sessão segura e redirecionando..."
                ];
                let currentStep = 0;
                let progress = 0;

                const interval = setInterval(() => {
                    if (currentStep < steps.length - 1) {
                        document.getElementById('progress-status').innerText = steps[currentStep];
                        progress += 22;
                        document.getElementById('progress-bar').style.width = progress + '%';
                        currentStep++;
                    } else {
                        clearInterval(interval);
                        document.getElementById('progress-status').innerText = steps[steps.length - 1];

                        const formData = new FormData();
                        formData.append('csrf_token', '<?= $csrfToken ?>');
                        formData.append('admin_password', passInput.value);

                        fetch('<?= $baseUrl ?>/admin/settings/factory-reset/execute', {
                            method: 'POST',
                            body: formData
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data && data.success) {
                                    document.getElementById('progress-bar').style.width = '100%';
                                    window.location.href = '<?= $baseUrl ?>/setup';
                                } else {
                                    document.getElementById('progress-container').style.display = 'none';
                                    document.getElementById('auth-box').style.display = 'block';
                                    errorEl.innerText = (data && data.error) ? data.error : 'Falha ao executar o reset de fábrica.';
                                    errorEl.style.display = 'block';
                                }
                            })
                            .catch(err => {
                                document.getElementById('progress-container').style.display = 'none';
                                document.getElementById('auth-box').style.display = 'block';
                                errorEl.innerText = 'Erro de rede ao comunicar com o servidor: ' + err.message;
                                errorEl.style.display = 'block';
                            });
                    }
                }, 400);
            }
        </script>
        <?php
        $html = ob_get_clean();
        return new ViewResponse($html);
    }

    public function executeFactoryReset(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        try {
            $password = (string)$request->input('admin_password', '');
            if ($password === '') {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'A confirmação da senha de administrador é obrigatória.'
                ], 403);
            }

            if (!$this->verifyAdminPassword($password)) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'Senha de administrador inválida. Operação abortada.'
                ], 403);
            }

            // Drop ONLY managed system tables (H-03)
            if ($this->db) {
                $db = $this->db;
                $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
                if ($driver === 'mysql') {
                    $db->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    foreach (self::MANAGED_TABLES as $table) {
                        if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                            $db->exec("DROP TABLE IF EXISTS `{$table}`");
                        }
                    }
                    $db->exec('SET FOREIGN_KEY_CHECKS = 1;');
                } elseif ($driver === 'sqlite') {
                    $db->exec('PRAGMA foreign_keys = OFF;');
                    foreach (self::MANAGED_TABLES as $table) {
                        if (preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                            $db->exec("DROP TABLE IF EXISTS `{$table}`");
                        }
                    }
                    $db->exec('PRAGMA foreign_keys = ON;');
                }
            }

            // Remover apenas arquivos de lock e estado de instalação no basePath configurado
            $lockFile = $this->basePath . '/config/installed.lock';
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }

            $pluginsConfig = $this->basePath . '/config/plugins.json';
            if (file_exists($pluginsConfig)) {
                @unlink($pluginsConfig);
            }

            // Destruir sessão via contrato SessionManagerInterface (H-03)
            $this->session->destroy();

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erro ao redefinir: ' . $e->getMessage()], 500);
        }
    }

    private function verifyAdminPassword(string $password): bool
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId > 0) {
            $stmt = $this->db->prepare("SELECT password, role FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user && ($user['role'] ?? '') === 'admin' && password_verify($password, (string)$user['password'])) {
                return true;
            }
            return false;
        }

        // Caso a sessão não traga user_id explícito mas user_role seja admin
        if ($this->session->get('user_role') === 'admin') {
            $stmt = $this->db->query("SELECT password FROM users WHERE role = 'admin'");
            $admins = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
            foreach ($admins as $admin) {
                if (password_verify($password, (string)$admin['password'])) {
                    return true;
                }
            }
        }

        return false;
    }
}