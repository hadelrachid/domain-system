<?php
namespace DomainSystem\Plugins\clinic_pack\Views;

class SecretaryCockpitView extends AbstractCockpitView
{
    public function renderMainContent(): string
    {
        ob_start();
        extract($this->data);
        ?>
<?php if(isset($_GET['success'])): ?>
        <div style="background: #d1fae5; color: #059669; padding: 14px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #059669;">
            <i class="fas fa-check-circle"></i> Configurações atualizadas com sucesso!
        </div>
    <?php endif; ?>

    <?php
    $pendingApps   = array_values(array_filter($appointments ?? [], fn($a) => strtolower($a['status']) === 'pendente'));
    $confirmedToday = $confirmed ?? [];
    $hist           = $history ?? [];
    ?>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('pendentes')" id="tab-btn-pendentes">
            <i class="fas fa-hourglass-half"></i> Aguardando
            <?php if(count($pendingApps) > 0): ?>
                <span style="background:#f59e0b;color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;"><?= count($pendingApps) ?></span>
            <?php endif; ?>
        </div>
        <div class="tab" onclick="switchTab('confirmados')" id="tab-btn-confirmados">
            <i class="fas fa-calendar-check"></i> Confirmados Hoje
            <?php if(count($confirmedToday) > 0): ?>
                <span style="background:#10b981;color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;"><?= count($confirmedToday) ?></span>
            <?php endif; ?>
        </div>
        <div class="tab" onclick="switchTab('agendar')" id="tab-btn-agendar">
            <i class="fas fa-calendar-plus"></i> Agendar Paciente
        </div>
        <div class="tab" onclick="switchTab('historico')" id="tab-btn-historico">
            <i class="fas fa-history"></i> Histórico
        </div>
        <div class="tab" onclick="switchTab('pesquisar')" id="tab-btn-pesquisar">
            <i class="fas fa-search"></i> Pesquisar
        </div>
    </div>

        <!-- ABA: AGUARDANDO -->
    <div id="tab-pendentes" class="tab-content active">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_aguardando role="secretary"]') ?>
    </div>

    <!-- ABA: CONFIRMADOS HOJE -->
    <div id="tab-confirmados" class="tab-content">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_confirmados_hoje role="secretary"]') ?>
    </div>

    <!-- ABA: AGENDAR PACIENTE -->
    <div id="tab-agendar" class="tab-content">
        <div class="booking-form-card" style="border-left: none; padding: 0; box-shadow: none; background: transparent;">
            <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[agendamento_form]') ?>
        </div>
    </div>

    <!-- ABA: HISTÓRICO -->
    <div id="tab-historico" class="tab-content">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_historico role="secretary" type="all"]') ?>
    </div>

    <!-- ABA: PESQUISAR -->
    <div id="tab-pesquisar" class="tab-content">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_pesquisar]') ?>
    </div>
</div>

<!-- MODAL DE CONFIGURAÇÕES DO PERFIL -->
        <?php
        return ob_get_clean();
    }

    public function renderScripts(): string
    {
        $baseScripts = parent::renderScripts();
        ob_start();
        ?>
<script>
/* ===== SCRIPTS ESPECÍFICOS ===== */
    function doSearch() {
        const name = document.getElementById('search-name').value;
        const date = document.getElementById('search-date').value;
        const resultsDiv = document.getElementById('search-results');
        
        resultsDiv.innerHTML = '<div style="text-align: center; padding: 50px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        
        fetch('<?= \BASE_URL ?>/cockpit/search', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ 
                search_name: name, 
                search_date: date,
                csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
            })
        })
        .then(res => { if(!res.ok && res.status === 403) return res.json().then(data => { if(data.error === 'csrf') { showToast('Acesso Negado 🛑 (CSRF)', 'error'); throw new Error('CSRF'); } }); return res.text(); })
        .then(html => { resultsDiv.innerHTML = html; })
        .catch(err => { resultsDiv.innerHTML = '<div style="color:red; padding:20px;">Erro na busca.</div>'; });
    }

    /* ===== TABS ===== */
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
        const content = document.getElementById('tab-' + tabId);
        const btn     = document.getElementById('tab-btn-' + tabId);
        if (content) content.classList.add('active');
        if (btn)     btn.classList.add('active');
        
        sessionStorage.setItem('active_tab_' + window.location.pathname, tabId);
        
        // Auto-refresh the history tab when clicked
        if (tabId === 'historico-hoje') {
            content.innerHTML = '<div style="text-align: center; padding: 50px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            fetch('<?= \BASE_URL ?>/cockpit/history-tab', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'view_role=secretary&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
            })
            .then(res => res.text())
            .then(html => { content.innerHTML = html; })
            .catch(err => { content.innerHTML = '<div style="color:red; padding:20px;">Erro ao carregar histórico.</div>'; });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTab = sessionStorage.getItem('active_tab_' + window.location.pathname);
        if (savedTab && document.getElementById('tab-' + savedTab)) {
            switchTab(savedTab);
        }
    });

    /* ===== MODAL CONFIGURAÇÕES ===== */
    

    /* ===== PREVIEW FOTO — via partial compartilhado ===== */
    

    /* ===== PREVIEW TEMA — via partial compartilhado ===== */
    



    /* ===== CANCELAR/NÃO COMPARECEU ===== */
    function cancelarAgendamento(id) {
        const btn = document.getElementById('btn-cancel-' + id);
        if (btn.getAttribute('data-confirm') !== '1') {
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Tem certeza?';
            btn.setAttribute('data-confirm', '1');
            setTimeout(() => {
                if(btn.getAttribute('data-confirm') === '1') {
                    btn.innerHTML = '<i class="fas fa-user-times"></i> Não Compareceu';
                    btn.removeAttribute('data-confirm');
                }
            }, 3000);
            return;
        }
        btn.removeAttribute('data-confirm');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelando...';
        btn.disabled = true;

        fetch('<?= \BASE_URL ?>/cockpit/appointments/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'id=' + id + '&status=Cancelado&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.success) {
                const card = document.getElementById('card-' + id);
                showToast('Agendamento cancelado!', 'success');
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => card.remove(), 500);
                }, 500);
            } else {
                showToast('Erro: ' + (data.message || 'Falha ao cancelar.'), 'error');
                btn.innerHTML = orig; btn.disabled = false;
            }
        })
        .catch(() => {
            showToast('Erro de conexão. Tente novamente.', 'error');
            btn.innerHTML = orig; btn.disabled = false;
        });
    }

    /* ===== CONFIRMAR CHEGADA (sem confirm() do JS) ===== */
    function confirmarAgendamento(id) {
        const btn = document.getElementById('btn-confirm-' + id);
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirmando...';
        btn.disabled = true;

        fetch('<?= \BASE_URL ?>/cockpit/appointments/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'id=' + id + '&status=Confirmado&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
        })
        .then(async res => {
            if (!res.ok) {
                const text = await res.text();
                throw new Error('Erro do Servidor: ' + text.substring(0, 50));
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                const card = document.getElementById('card-' + id);
                card.classList.add('status-confirmado');
                const badge = document.getElementById('status-' + id);
                if (badge) { badge.className = 'status-badge Confirmado'; badge.innerHTML = '<i class="fas fa-check-circle"></i> Confirmado'; }
                btn.outerHTML = '<div class="status-badge Confirmado" style="display:inline-block; font-size:13px; font-weight:bold; padding:8px 12px; border-radius:4px; margin-top:10px;"><i class="fas fa-check"></i> Confirmado</div>';
                
                const cancelBtn = document.getElementById('btn-cancel-' + id);
                if(cancelBtn) cancelBtn.remove();
                
                showToast('Paciente confirmado e enviado ao médico!', 'success');
                
                // Remove o delay artificial que estava confundindo o usuário. 
                // Inicia a animação imediatamente após a confirmação do banco de dados.
                setTimeout(() => {
                    card.style.transition = 'opacity 0.4s, transform 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        const tabConfirmados = document.getElementById('tab-confirmados');
                        const emptyMsg = tabConfirmados.querySelector('div[style*="text-align:center"]');
                        if(emptyMsg) emptyMsg.remove();
                        
                        card.style.transition = 'none';
                        card.style.opacity = '1';
                        card.style.transform = 'none';
                        card.style.borderLeftColor = '#10b981';
                        
                        const actions = card.querySelector('.actions');
                        if (actions) {
                            const wa = actions.querySelector('.btn-wa');
                            actions.innerHTML = '';
                            actions.style = 'display:block; padding-top:12px; margin-top:12px; border-top:1px solid #e2e8f0;';
                            if (wa) actions.appendChild(wa);
                        }
                        
                        tabConfirmados.appendChild(card);
                        
                        // Atualizar badges
                        const pendBadge = document.querySelector('#tab-btn-pendentes span');
                        if(pendBadge) {
                            let n = parseInt(pendBadge.innerText) - 1;
                            if(n > 0) pendBadge.innerText = n; else pendBadge.remove();
                        }
                        let confBadge = document.querySelector('#tab-btn-confirmados span');
                        if(confBadge) {
                            confBadge.innerText = parseInt(confBadge.innerText) + 1;
                        } else {
                            document.getElementById('tab-btn-confirmados').innerHTML += '<span style="background:#10b981;color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;">1</span>';
                        }
                    }, 400); // tempo da animação CSS
                }, 50); // dispara logo em seguida
            } else {
                showToast('Erro: ' + (data.message || 'Não foi possível confirmar.'), 'error');
                btn.innerHTML = orig; btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            showToast(err.message || 'Erro de conexão.', 'error');
            btn.innerHTML = orig; btn.disabled = false;
        });
    }

    /* ===== MÁSCARA TELEFONE ===== */
    const phoneInput = document.getElementById('patient_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 6)      this.value = '(' + v.substring(0,2) + ') ' + v.substring(2,7) + '-' + v.substring(7);
            else if (v.length > 2) this.value = '(' + v.substring(0,2) + ') ' + v.substring(2);
            else if (v.length > 0) this.value = '(' + v;
        });
    }

</script>
        <?php
        $extra = ob_get_clean();
        return $baseScripts . $extra;
    }
}

