<?php
namespace DomainSystem\Plugins\clinic_pack\Views;

class DoctorCockpitView extends AbstractCockpitView
{
    public function renderMainContent(): string
    {
        ob_start();
        extract($this->data);
        ?>
<?php if(!$doctor_id): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; border-left: 4px solid #ef4444;">
                <i class="fas fa-exclamation-triangle"></i> Atenção: Sua conta de usuário ainda não foi vinculada a um perfil de Médico no sistema. Contate a administração.
            </div>
        <?php else: ?>

            <?php if(isset($_GET['success'])): ?>
                <div style="background: #d1fae5; color: #059669; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #059669;">
                    Operação realizada com sucesso!
                </div>
            <?php endif; ?>

    <div class="tabs" style="margin-top:20px;">
        <div class="tab active" onclick="switchTab('pacientes')" id="tab-btn-pacientes">
            <i class="fas fa-user-injured"></i> Meus Pacientes de Hoje
            <?php if(isset($appointments) && count($appointments) > 0): ?>
                <span style="background:#10b981;color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;"><?= count($appointments) ?></span>
            <?php endif; ?>
        </div>
        <div class="tab" onclick="switchTab('historico-hoje')" id="tab-btn-historico-hoje">
            <i class="fas fa-history"></i> Atendidos Hoje
            <?php if(isset($history_today) && count($history_today) > 0): ?>
                <span style="background:#64748b;color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;"><?= count($history_today) ?></span>
            <?php endif; ?>
        </div>
        <div class="tab" onclick="switchTab('pesquisar')" id="tab-btn-pesquisar">
            <i class="fas fa-search"></i> Pesquisar Histórico
        </div>
    </div>

                        <!-- ABA DE PACIENTES CONFIRMADOS -->
            <div id="tab-pacientes" class="tab-content active">
                <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_confirmados_hoje role="doctor"]') ?>
            </div>

            <!-- ABA: HISTÓRICO DE HOJE -->
            <div id="tab-historico-hoje" class="tab-content">
                <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_historico role="doctor" type="today"]') ?>
            </div>

            <!-- ABA: PESQUISAR HISTÓRICO -->
            <div id="tab-pesquisar" class="tab-content">
                <?= \DomainSystem\Core\Application::getInstance()->getShortcodeManager()->parse('[aba_pesquisar]') ?>
            </div>

        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function renderScripts(): string
    {
        $baseScripts = parent::renderScripts();
        ob_start();
        ?>
        <script>
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
                    body: 'view_role=doctor&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
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

        function toggleSettingsModal() {
            const modal = document.getElementById('settingsModal');
            modal.classList.toggle('active');
        }

        document.getElementById('settingsModal').addEventListener('click', function(e) {
            if(e.target === this) toggleSettingsModal();
        });

        function switchModalTab(tabId) {
            document.querySelectorAll('.modal-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.modal-tab').forEach(el => el.classList.remove('active'));
            
            document.getElementById('mtab-' + tabId).classList.add('active');
            document.getElementById('mtab-btn-' + tabId).classList.add('active');
        }

        function changeStatus(id, newStatus, btn) {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            btn.disabled = true;

            fetch('<?= \BASE_URL ?>/cockpit/appointments/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'id=' + id + '&status=' + newStatus + '&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
            })
            .then(res => { if(!res.ok && res.status === 403) return res.json().then(data => { if(data.error === 'csrf') { showToast('Acesso Negado 🛑 (CSRF): ' + (data.message||'Token Inválido'), 'error'); throw new Error('CSRF'); } }); return res.json(); })
            .then(data => {
                if (data.success) {
                    let card = document.getElementById('card-' + id);
                    if (card) {
                        card.style.transition = 'opacity 0.4s, transform 0.4s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.remove();
                            
                            // Atualizar badge de Pacientes
                            const pacBadge = document.querySelector('#tab-btn-pacientes span');
                            if(pacBadge) {
                                let n = parseInt(pacBadge.innerText) - 1;
                                if(n > 0) pacBadge.innerText = n; else pacBadge.remove();
                            }
                            
                            // Atualizar badge de Atendidos
                            let histBadge = document.querySelector('#tab-btn-historico-hoje span');
                            if(histBadge) {
                                histBadge.innerText = parseInt(histBadge.innerText) + 1;
                            } else {
                                document.getElementById('tab-btn-historico-hoje').innerHTML += '<span style="background:#64748b;color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;">1</span>';
                            }
                        }, 400);
                    }
                } else {
                    alert('Erro ao alterar status.');
                    btn.innerHTML = orig; btn.disabled = false;
                }
            })
            .catch(err => {
                alert('Erro de rede.');
                btn.innerHTML = orig; btn.disabled = false;
            });
        }

        // Funções para grade de horários
        function toggleDay(checkbox, dayIndex) {
            let periods = document.getElementById('periods_' + dayIndex);
            periods.style.display = checkbox.checked ? 'block' : 'none';
        }

        function addPeriod(dayIndex) {
            let container = document.getElementById('periods_' + dayIndex);
            let btn = container.querySelector('.btn-add-period');
            let idx = container.querySelectorAll('.period-row').length;
            
            let row = document.createElement('div');
            row.className = 'period-row';
            row.innerHTML = `
                <input type="time" name="day_${dayIndex}_periods[${idx}][start]" value="13:00" required>
                <span>às</span>
                <input type="time" name="day_${dayIndex}_periods[${idx}][end]" value="18:00" required>
                <input type="number" name="day_${dayIndex}_periods[${idx}][slot]" value="30" style="width:60px" title="Duração (min)">
                <button type="button" class="btn-rm-period" onclick="this.parentElement.remove()"><i class="fas fa-times-circle"></i></button>
            `;
            container.insertBefore(row, btn);
        }

        /* ===== PREVIEW FOTO — via partial compartilhado ===== */
        

        /* ===== PREVIEW TEMA — via partial compartilhado ===== */
        </script>
        <?php
        $extra = ob_get_clean();
        return $baseScripts . $extra;
    }
}
