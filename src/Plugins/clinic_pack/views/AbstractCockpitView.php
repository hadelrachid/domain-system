<?php
namespace DomainSystem\Plugins\clinic_pack\Views;

abstract class AbstractCockpitView implements CockpitViewInterface
{
    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

        protected function formatPhone(?string $phone): string
    {
        if (empty($phone)) return '';
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $clean);
        } elseif (strlen($clean) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $clean);
        }
        return htmlspecialchars($phone);
    }

    protected function formatName(?string $name): string
    {
        if (empty($name)) return 'Não informado';
        $parts = explode(' ', trim($name));
        if (count($parts) > 4) {
            return htmlspecialchars(implode(' ', array_slice($parts, 0, 4)) . '...');
        }
        return htmlspecialchars($name);
    }
    protected function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function renderHeader(): string
    {
        ob_start();
        extract($this->data);
        
        // Define title based on class name for generic fallback
        $titleIcon = 'fa-headset';
        $titleText = 'CockPIT';
        if (strpos(static::class, 'Doctor') !== false) {
            $titleIcon = 'fa-stethoscope';
            $titleText = 'CockPIT Médico';
        } elseif (strpos(static::class, 'Secretary') !== false) {
            $titleIcon = 'fa-headset';
            $titleText = 'CockPIT Secretária';
        } elseif (strpos(static::class, 'Nursing') !== false) {
            $titleIcon = 'fa-user-nurse';
            $titleText = 'CockPIT Enfermagem';
        }

        ?>
        <div class="header">
            <h1><i class="fas <?= $titleIcon ?>"></i> <?= $titleText ?></h1>
            <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[relogio_digital]') ?>
            <div style="display:flex; align-items:center; gap:20px;">
                <button type="button" onclick="location.href = location.pathname + '?_t=' + new Date().getTime()" title="Atualizar Tela" style="background:none;border:none;font-size:18px;color:var(--primary);cursor:pointer;transition:transform 0.3s;" onmouseover="this.style.transform='rotate(180deg)'" onmouseout="this.style.transform='none'">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[widget_perfil_header]') ?>
            </div>
        </div>
        <div id="toast"></div>
        <?php
        return ob_get_clean();
    }

    public function renderProfileModal(): string
    {
        $userId = $_SESSION['user_id'] ?? '';
        $shortcode = "
[modal_perfil]
    <div style='display:flex; flex-wrap:wrap; gap:30px; align-items:flex-start;'>
        <div style='flex:1; min-width:260px;'>
            [form_dados_pessoais]
        </div>
        <div style='flex:1.2; min-width:280px;'>
            [theme_palette user_id=\"{$userId}\"]
            [form_autenticacao_2fa]
        </div>
    </div>
[/modal_perfil]";
        return \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse($shortcode);
    }

    public function renderScripts(): string
    {
        ob_start();
        ?>
        <script>
            // Modal Logic
            function toggleModal() {
                const m = document.getElementById('settingsModal');
                if (m) m.style.display = (m.style.display === 'flex') ? 'none' : 'flex';
            }
            
            console.log("DEBUG SSR: user_id = <?= $this->data['user_id'] ?? 'NULL' ?>");
            console.log("DEBUG SSR: cockpitType = <?= (strpos(static::class, 'Secretary') !== false) ? 'secretary' : 'doctor' ?>");

            // Toast Logic
            function showToast(msg, type = 'success') {
                const t = document.getElementById('toast');
                if (!t) return;
                t.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
                t.className = 'show ' + type;
                setTimeout(() => { t.className = ''; }, 4000);
            }

            
            // Profile Form AJAX Submit
            function submitProfileForm(e) {
                e.preventDefault();
                const form = e.target;
                const btn = document.getElementById('btn-save-profile');
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                btn.disabled = true;

                fetch('<?= \BASE_URL ?>/cockpit/profile', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async res => {
                    const contentType = res.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return res.json();
                    } else {
                        // Se não retornou JSON, pode ser um erro fatal no PHP ou redirecionamento inesperado
                        const text = await res.text();
                        if (text.includes('Acesso Negado') || text.includes('CSRF')) {
                            throw new Error('Sessão expirada. Recarregue a página.');
                        }
                        throw new Error('Erro desconhecido no servidor.');
                    }
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Configurações salvas com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Erro ao salvar configurações.', 'error');
                        btn.innerHTML = origHtml; btn.disabled = false;
                    }
                })
                .catch(err => {
                    showToast(err.message || 'Erro de conexão.', 'error');
                    btn.innerHTML = origHtml; btn.disabled = false;
                });
            }
// Profile Form AJAX Submit
            function submitProfileForm(e) {
                e.preventDefault();
                const form = e.target;
                const btn = document.getElementById('btn-save-profile');
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                btn.disabled = true;

                fetch('<?= \BASE_URL ?>/cockpit/profile', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async res => {
                    const contentType = res.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return res.json();
                    } else {
                        // Se não retornou JSON, pode ser um erro fatal no PHP ou redirecionamento inesperado
                        const text = await res.text();
                        if (text.includes('Acesso Negado') || text.includes('CSRF')) {
                            throw new Error('Sessão expirada. Recarregue a página (F5).');
                        }
                        throw new Error('Erro desconhecido no servidor.');
                    }
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Configurações salvas com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Erro ao salvar configurações.', 'error');
                        btn.innerHTML = origHtml; btn.disabled = false;
                    }
                })
                .catch(err => {
                    showToast(err.message || 'Erro de conexão.', 'error');
                    btn.innerHTML = origHtml; btn.disabled = false;
                });
            }
            
            <?php 
            include __DIR__ . '/../themes/partials/_avatar_js.php'; 
            $events = \DomainSystem\Core\Application::getInstance()->getDispatcher();
            echo $events->applyFilters('cockpit.footer.js', '');
            ?>
        </script>
        <?php
        return ob_get_clean();
    }

    public function render(): void
    {
        extract($this->data);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>CockPIT</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                <?php
                $events = \DomainSystem\Core\Application::getInstance()->getDispatcher();
                $cockpitType = (strpos(static::class, 'Secretary') !== false) ? 'secretary' : 'doctor';
                $currentUserId = $this->data['user_id'] ?? $_SESSION['user_id'] ?? null;
                echo $events->applyFilters('cockpit.head.css', '', $currentUserId, $cockpitType);
                ?>
                body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg-body); margin: 0; padding: 0; color: var(--text-main); }
                .header { background: var(--bg-card); padding: 12px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-top: 4px solid var(--primary); }
                .header h1 { margin: 0; color: var(--primary); font-size: 20px; display: flex; align-items: center; gap: 10px; }
                .header-clock { display: flex; flex-direction: column; align-items: center; background: var(--bg-body); padding: 5px 15px; border-radius: 8px; border: 1px solid var(--primary-border); }
                .user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
                .user-profile img { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
                .user-profile .name { font-weight: 600; font-size: 14px; }
                .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
                
                .settings-form-group { margin-bottom: 20px; }
                .settings-form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
                .settings-form-group input, .settings-form-group select { width: 100%; padding: 10px; border: 1px solid var(--primary-border); background: var(--bg-body); color: var(--text-main); border-radius: 4px; box-sizing: border-box; font-family:inherit;}
                .btn-save { background: var(--primary); color: white; padding: 10px 20px; width: 100%; font-size: 15px; margin-top: 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition:0.2s;}
                .btn-save:hover { background: var(--primary-hover); }
                
                .avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; color: white; background: var(--primary); flex-shrink: 0; overflow:hidden;}
                .avatar-lg { width: 90px; height: 90px; font-size: 28px; border: 3px solid var(--primary-border); }
                .avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
                
                #toast { position: fixed; bottom: 30px; right: 30px; background: #1e293b; color: white; padding: 14px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; display: none; align-items: center; gap: 10px; z-index: 999999; box-shadow: 0 4px 12px rgba(0,0,0,0.3); min-width: 280px; }
                #toast.show { display: flex; }
                #toast.success { border-left: 4px solid #10b981; }
                #toast.error   { border-left: 4px solid #ef4444; }

                /* Common helper classes that were in secretary/doctor index.php */
                .tabs { display: flex; gap: 2px; margin-bottom: 20px; border-bottom: 2px solid var(--primary-border); flex-wrap: wrap; }
                .tab { padding: 10px 16px; cursor: pointer; font-weight: 600; font-size: 13px; color: var(--text-muted); border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color 0.2s; white-space: nowrap; }
                .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
                .tab:hover { color: var(--primary); }
                .tab-content { display: none; }
                .tab-content.active { display: block; }
                
                .appointment-card { background: var(--bg-card) !important; border-radius: 8px; padding: 18px 20px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; border-left: 5px solid #f59e0b; color: var(--text-main) !important; }
                .appointment-card.status-confirmado { border-left-color: #10b981; animation: none !important; }
                @keyframes pulse-yellow { 0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.4); } 70% { box-shadow: 0 0 0 8px rgba(245,158,11,0); } }
                .appointment-card:not(.status-confirmado) { animation: pulse-yellow 2s infinite; }
                
                .info-group { display: flex; flex-direction: column; gap: 3px; min-width: 110px; }
                .info-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
                .info-value { font-size: 14px; font-weight: 500; }

                /* Botões e Badges (Restaurados) */
                .actions { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
                .btn { padding: 7px 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: all 0.2s; }
                .btn-wa    { background: #25D366; color: white; }
                .btn-wa:hover { background: #128C7E; }
                .btn-tg    { background: #0088cc; color: white; }
                .btn-tg:hover { background: #0077b5; }
                .btn-email { background: #64748b; color: white; }
                .btn-email:hover { background: #475569; }
                .btn-confirm { background: #10b981; color: white; border: 2px solid #059669; padding: 8px 16px; font-size: 13px; border-radius: 6px; }
                .btn-confirm:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 2px 6px rgba(5,150,105,0.4); }
                .btn-confirm:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
                
                .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
                .status-badge.Pendente   { background: rgba(217, 119, 6, 0.2); color: #d97706; border: 1px solid rgba(217,119,6,0.3); }
                .status-badge.Confirmado { background: rgba(5, 150, 105, 0.2); color: #059669; border: 1px solid rgba(5,150,105,0.3); }
            </style>
            
            <?php 
            // Allow subclasses to inject additional CSS
            if (method_exists($this, 'renderExtraCss')) {
                echo $this->renderExtraCss();
            }
            ?>
        </head>
        <body>
            <?= $this->renderHeader() ?>
            
            <div class="container">
                <?php if(isset($_GET['success'])): ?>
                    <div style="background: #d1fae5; color: #059669; padding: 14px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #059669;">
                        <i class="fas fa-check-circle"></i> Configurações atualizadas com sucesso!
                    </div>
                <?php endif; ?>
                
                <?= $this->renderMainContent() ?>
            </div>
            
            <?= $this->renderProfileModal() ?>
            
            <?= $this->renderScripts() ?>
        </body>
        </html>
        <?php
    }
}


