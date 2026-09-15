<?php

namespace DomainSystem\Plugins\clinic_pack\Theme;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Core\Application;

class ProfileShortcodes
{
    private \PDO $db;
    private SessionManager $session;

    public function __construct(Connection $conn, SessionManager $session)
    {
        $this->db = $conn->getPdo();
        $this->session = $session;
    }

    private function getUserData(): ?array
    {
        $userId = $this->session->get('user_id');
        if (!$userId) return null;

        $stmt = $this->db->prepare("SELECT email, profile_image, linked_doctor_id, two_factor_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['id'] = $userId;
            $user['name'] = $this->session->get('user_name');
            
            // Se o usuário não tem foto, mas é um médico e o médico tem foto, usa a do médico!
            if (empty($user['profile_image']) && !empty($user['linked_doctor_id'])) {
                $docStmt = $this->db->prepare("SELECT photo_url FROM doctors WHERE id = ?");
                $docStmt->execute([$user['linked_doctor_id']]);
                $docPhoto = $docStmt->fetchColumn();
                if ($docPhoto) {
                    $user['profile_image'] = $docPhoto;
                }
            }
        }
        return $user ?: null;
    }

    public function renderWidgetPerfilHeader(array $attributes = []): string
    {
        $user = $this->getUserData();
        if (!$user) return '<!-- ERRO: USER NULL -->';
        
        ob_start();
        ?>
        <div class="user-profile" onclick="if(typeof toggleModal === 'function') { toggleModal(); } else { const m = document.getElementById('settingsModal'); if (m) m.style.display = (m.style.display === 'flex') ? 'none' : 'flex'; }" title="Configurações do Perfil" style="cursor:pointer; display:flex; align-items:center; gap:10px;">
            <div class="name" style="font-weight:600; font-size:14px;"><?= htmlspecialchars($user['name'] ?? '') ?></div>
            <?php
            $avatarName  = $user['name'] ?? '';
            $avatarPhoto = !empty($user['profile_image']) ? \BASE_URL . htmlspecialchars($user['profile_image']) : '';
            $avatarId    = 'header-avatar';
            $avatarSize  = 'sm';
            include dirname(__DIR__) . '/themes/partials/_avatar.php';
            ?>
            <i class="fas fa-cog" style="color: #64748b;"></i>
        </div>
        <?php
        return ob_get_clean();
    }

    public function renderModalPerfil(array $attributes = [], ?string $innerContent = null): string
    {
        $user = $this->getUserData();
        if (!$user) return '<!-- ERRO: USER NULL -->';

        $doctorId = $user['linked_doctor_id'] ?? null;
        $app = Application::getInstance();
        $shortcodeManager = $app->getShortcodeManager();

        ob_start();
        ?>
        <div id="settingsModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
            <div style="background:var(--bg-card,#fff);width:100%;max-width:700px;max-height:90vh;overflow-y:auto;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.15);padding:30px;position:relative;">
                <button type="button" onclick="const m = document.getElementById('settingsModal'); if (m) m.style.display = 'none';" style="position:absolute;top:15px;right:15px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;transition:color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'"><i class="fas fa-times"></i></button>
                
                <?php if ($doctorId): ?>
                <style>
                    .modal-nav-tabs { display:flex; border-bottom:1px solid var(--primary-border); margin-bottom: 25px; gap:20px; justify-content:center; }
                    .modal-nav-tabs a { text-decoration:none; padding:10px 15px; color:var(--text-muted); font-weight:600; cursor:pointer; font-size:15px; transition:color 0.2s; border-bottom:3px solid transparent; }
                    .modal-nav-tabs a:hover { color:var(--primary); }
                    .modal-nav-tabs a.active { color:var(--primary); border-bottom:3px solid var(--primary); }
                    .modal-tab-pane { display:none; }
                    .modal-tab-pane.active { display:block; }
                </style>
                <div class="modal-nav-tabs">
                    <a class="active" onclick="document.getElementById('pane-conta').classList.add('active'); document.getElementById('pane-agenda').classList.remove('active'); this.classList.add('active'); this.nextElementSibling.classList.remove('active');"><i class="fas fa-user-cog"></i> Minha Conta</a>
                    <a onclick="document.getElementById('pane-agenda').classList.add('active'); document.getElementById('pane-conta').classList.remove('active'); this.classList.add('active'); this.previousElementSibling.classList.remove('active');"><i class="fas fa-calendar-alt"></i> Minha Agenda</a>
                </div>
                <div id="pane-conta" class="modal-tab-pane active">
                <?php else: ?>
                <h2 style="margin-top:0;color:var(--primary);margin-bottom:20px;font-size:18px;text-align:center;"><i class="fas fa-user-cog"></i> Configurações do Perfil</h2>
                <?php endif; ?>
                
                <form id="profileForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <?= $innerContent ?>
                    
                    <!-- Botões de Ação na parte inferior do grid -->
                    <div style="margin-top:25px;padding-top:20px;border-top:1px solid #e2e8f0;display:flex;flex-wrap:wrap;gap:15px;align-items:center;justify-content:space-between;">
                        <?= $shortcodeManager->parse('[botao_logout]') ?>
                        <button type="submit" class="btn-save" style="margin-top:0;width:auto;padding:10px 25px;" id="btn-save-profile"><i class="fas fa-save"></i> Salvar Configurações</button>
                    </div>
                </form>
                
                <script>
                <?php include dirname(__DIR__) . '/themes/partials/_avatar_js.php'; ?>
                
                if (typeof window.previewTheme !== 'function') {
                    window.previewTheme = function(colorKey) {
                        const allSwatches = document.querySelectorAll('[id^="swatch-"]');
                        allSwatches.forEach(el => {
                            el.style.borderColor = 'transparent';
                        });
                        const selected = document.getElementById('swatch-' + colorKey);
                        if (selected) selected.style.borderColor = '#1e293b';
                    };
                }
                </script>
                
                <?php if ($doctorId): ?>
                </div> <!-- /pane-conta -->
                <div id="pane-agenda" class="modal-tab-pane">
                    <iframe src="<?= \BASE_URL ?>/admin/doctors/schedule?doctor_id=<?= $doctorId ?>&embedded=1&raw=1" style="width:100%; height:500px; border:none; border-radius:8px;"></iframe>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function renderFormDadosPessoais(array $attributes = []): string
    {
        $user = $this->getUserData();
        if (!$user) return '<!-- ERRO: USER NULL -->';

        ob_start();
        $avatarName = $user['name'] ?? '';
        $avatarPhoto = !empty($user['profile_image']) ? \BASE_URL . htmlspecialchars($user['profile_image']) : '';
        $avatarId = 'modal-avatar';
        $headerId = 'header-avatar';
        include dirname(__DIR__) . '/themes/partials/_profile_photo_field.php';
        ?>
        <div class="settings-form-group">
            <label><i class="fas fa-envelope" style="color:var(--primary);width:16px;"></i> E-mail de Login e 2FA</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>
        
        <div class="settings-form-group">
            <label style="display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fas fa-lock" style="color:var(--primary);width:16px;"></i> Nova Senha <small style="font-weight:400;color:var(--text-muted);">(em branco = manter)</small></span>
            </label>
            <div style="position:relative;">
                <input type="password" name="password" id="modal_password" placeholder="••••••••" style="padding-right:40px;" oninput="analyzePasswordStrength(this.value, 'prof')">
                <i class="fas fa-eye" style="position:absolute;right:13px;top:11px;cursor:pointer;color:var(--text-muted);" onclick="togglePasswordVisibility('modal_password', this)"></i>
            </div>
            <div style="margin-top: 8px;">
                <button type="button" onclick="generatePasswordAndAnalyze('modal_password', 'prof')" style="background:#f1f5f9; border:1px solid #cbd5e1; padding:5px 12px; border-radius:6px; font-size:11px; cursor:pointer; color:#3b82f6; font-weight:700; display:inline-flex; align-items:center; gap:6px; transition:0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'">
                    <i class="fas fa-magic"></i> Gerar Senha Segura
                </button>
            </div>
            
            <div id="pwd-meter-prof" style="display:none; margin-top:8px;">
                 <div style="height:6px; background:var(--primary-border); border-radius:3px; overflow:hidden;">
                      <div id="pwd-bar-prof" style="height:100%; width:0%; background:#ef4444; transition: width 0.3s, background 0.3s;"></div>
                 </div>
                 <div style="display:flex; justify-content:space-between; margin-top:4px;">
                     <div id="pwd-hint-prof" style="font-size:11px; color:var(--text-muted);">Inclua letras, números e símbolos</div>
                     <div id="pwd-text-prof" style="font-size:11px; font-weight:600; text-align:right;">Péssimo</div>
                 </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    public function renderFormAutenticacao2FA(array $attributes = []): string
    {
        $user = $this->getUserData();
        if (!$user) return '<!-- ERRO: USER NULL -->';

        ob_start();
        $twoFactorType = $user['two_factor_type'] ?? 'none';
        $userEmail = $user['email'] ?? '';
        include dirname(__DIR__) . '/themes/partials/_2fa_field.php';
        return ob_get_clean();
    }

    public function renderRelogioDigital(array $attributes = []): string
    {
        ob_start();
        ?>
        <div class="header-clock">
            <div id="live-time" style="font-size: 18px; font-weight: 700; color: var(--text-main); letter-spacing: 1px;">--:--:--</div>
            <div id="live-date" style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">--/--/----</div>
        </div>
        <script>
            function updateClock() {
                const now = new Date();
                const t = document.getElementById('live-time');
                const d = document.getElementById('live-date');
                if (t) t.textContent = now.toLocaleTimeString('pt-BR');
                if (d) d.textContent = now.toLocaleDateString('pt-BR');
            }
            setInterval(updateClock, 1000);
            updateClock();
        </script>
        <?php
        return ob_get_clean();
    }

    public function renderBotaoLogout(array $attributes = []): string
    {
        return '<a href="' . \BASE_URL . '/logout" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#ef4444;color:white;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;transition:0.2s;" onmouseover="this.style.background=\'#dc2626\'" onmouseout="this.style.background=\'#ef4444\'">
            <i class="fas fa-sign-out-alt"></i> Sair do Sistema
        </a>';
    }
}
