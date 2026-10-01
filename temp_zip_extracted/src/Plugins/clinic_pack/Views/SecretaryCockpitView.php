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
        <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 14px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #10b981;">
            <i class="fas fa-check-circle"></i> Configurações atualizadas com sucesso!
        </div>
    <?php endif; ?>

    <?php
    $pendingApps   = array_values(array_filter($appointments ?? [], fn($a) => strtolower($a['status']) === 'pendente'));
    $confirmedToday = $confirmed ?? [];
    $hist           = $history ?? [];
    ?>
    <div class="tabs" style="margin-top:20px;">
        <?php
        $pendentesCount = count(array_filter($appointments ?? [], fn($a) => $a['status'] === 'Pendente'));
        $confirmedCount = count(array_filter($appointments ?? [], fn($a) => $a['status'] === 'Confirmado' && date('Y-m-d', strtotime($a['appointment_date'])) === date('Y-m-d')));
        
        echo $this->renderTab('pendentes', 'fas fa-calendar-alt', 'Aguardando', $pendentesCount, '#f59e0b', true);
        echo $this->renderTab('confirmados', 'fas fa-calendar-check', 'Confirmados Hoje', $confirmedCount, '#10b981');
        echo $this->renderTab('falta', 'fas fa-user-times', 'Faltou', $missedCount ?? 0, '#ef4444');
        echo $this->renderTab('agendar', 'fas fa-calendar-plus', 'Agendar Paciente', 0, '#64748b', false, true);
        echo $this->renderTab('historico', 'fas fa-history', 'Histórico', $historyAllCount ?? 0, '#64748b');
        echo $this->renderTab('pesquisar', 'fas fa-search', 'Pesquisar', 0, '#64748b', false, true);
        ?>
    </div>

        <!-- ABA: AGUARDANDO -->
    <div id="tab-pendentes" class="tab-content active">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_aguardando role="secretary"]') ?>
    </div>

    <!-- ABA: CONFIRMADOS HOJE -->
    <div id="tab-confirmados" class="tab-content">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_confirmados_hoje role="secretary"]') ?>
    </div>

    <!-- ABA: FALTOU -->
    <div id="tab-falta" class="tab-content">
        <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_nao_compareceu role="secretary"]') ?>
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
    async function doSearch() {
        const name = document.getElementById('search-name').value;
        const date = document.getElementById('search-date').value;
        const resultsDiv = document.getElementById('search-results');
        
        resultsDiv.innerHTML = '<div style="text-align: center; padding: 50px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const html = await DS.api.post('/cockpit/search', `search_name=${encodeURIComponent(name)}&search_date=${encodeURIComponent(date)}&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>`, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            resultsDiv.innerHTML = html;
        } catch (err) {
            resultsDiv.innerHTML = '<div style="color:red; padding:20px;">Erro na busca.</div>';
        }
    }

    // Lógica das abas e sincronização movidas para a classe abstrata (AbstractCockpitView.php)
    
    /* ===== CANCELAR/NÃO COMPARECEU ===== */
    async function cancelarAgendamento(id) {
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

        try {
            const data = await DS.api.post('/cockpit/appointments/status', `id=${id}&status=Cancelado&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>`, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            
            if (data.success) {
                const card = document.getElementById('card-' + id);
                DS.ui.toast.success('Agendamento cancelado!');
                if (card) {
                    card.style.transition = 'opacity 0.4s, transform 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        card.remove();
                        if (window.forceSync) window.forceSync();
                    }, 400);
                } else {
                    if (window.forceSync) window.forceSync();
                }
            } else {
                DS.ui.toast.error('Erro: ' + (data.message || 'Falha ao cancelar.'));
                btn.innerHTML = orig; btn.disabled = false;
            }
        } catch (err) {
            btn.innerHTML = orig; btn.disabled = false;
        }
    }

    /* ===== CONFIRMAR CHEGADA (sem confirm() do JS) ===== */
    async function confirmarAgendamento(id) {
        const btn = document.getElementById('btn-confirm-' + id);
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirmando...';
        btn.disabled = true;

        try {
            const data = await DS.api.post('/cockpit/appointments/status', `id=${id}&status=Confirmado&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>`, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            
            if (data.success) {
                const card = document.getElementById('card-' + id);
                DS.ui.toast.success('Paciente confirmado e enviado ao médico!');
                if (card) {
                    card.style.transition = 'opacity 0.4s, transform 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        card.remove();
                        if (window.forceSync) window.forceSync();
                    }, 400);
                } else {
                    if (window.forceSync) window.forceSync();
                }
            } else {
                DS.ui.toast.error('Erro: ' + (data.message || 'Não foi possível confirmar.'));
                btn.innerHTML = orig; btn.disabled = false;
            }
        } catch (err) {
            btn.innerHTML = orig; btn.disabled = false;
        }
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

