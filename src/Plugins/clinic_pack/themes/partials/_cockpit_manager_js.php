/**
 * CockpitController - Gerenciador Orientado a Objetos da UI do Cockpit
 * Encapsula a lógica de abas, sincronização em tempo real (polling), atualizações de DOM e formulários.
 */
class CockpitController {
    constructor(config) {
        this.role = config.role;
        this.baseUrl = config.baseUrl;
        this.csrfToken = config.csrfToken;
        this.pollInterval = null;
        this.listeners = {}; // Dicionário de Callbacks/Eventos
        
        this.init();
    }

    // --- SISTEMA DE EVENTOS (PUB/SUB) ---
    on(event, callback) {
        if (!this.listeners[event]) this.listeners[event] = [];
        this.listeners[event].push(callback);
    }

    emit(event, data = {}) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(cb => cb(data));
        }
    }
    // ------------------------------------

    init() {
        this.bindProfileForm();
        this.restoreActiveTab();
        this.startPolling(15000);

        // Expor métodos globalmente para retrocompatibilidade com marcações HTML antigas (onclick="switchTab(...)")
        window.switchTab = (tabId) => this.switchTab(tabId);
        window.forceSync = () => this.forceSync();
    }

    bindProfileForm() {
        // Usa delegação de eventos para capturar o submit mesmo se o formulário for recarregado
        document.body.addEventListener('submit', async (e) => {
            if (e.target && e.target.id === 'profileForm') { // Assumindo que o formulário tenha id, ou podemos usar selector genérico
                // Fallback para form sem ID, busca pelo botão interno
                if (!e.target.querySelector('#btn-save-profile')) return;
            } else if (!e.target.querySelector('#btn-save-profile')) {
                return;
            }
            
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('btn-save-profile');
            if (!btn) return;

            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            btn.disabled = true;

            try {
                const data = await DS.api.post('/cockpit/profile', new FormData(form));
                
                if (data.success) {
                    DS.ui.toast.success(data.message || 'Configurações salvas com sucesso!');
                    // Evita reload forçado, mas recarrega a página após o toast para aplicar novo tema/avatar
                    setTimeout(() => location.reload(), 1500);
                } else {
                    DS.ui.toast.error(data.message || 'Erro ao salvar configurações.');
                    this._resetButton(btn, origHtml);
                }
            } catch (err) {
                // Erros já são capturados e mostrados pelo DS.api/toast
                this._resetButton(btn, origHtml);
            }
        });
    }

    _resetButton(btn, origHtml) {
        if (btn) {
            btn.innerHTML = origHtml;
            btn.disabled = false;
        }
    }

    startPolling(ms) {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => this.forceSync(), ms);
    }

    async forceSync() {
        try {
            console.log('[CockpitController] Iniciando forceSync...');
            const data = await DS.api.post('/cockpit/sync', `csrf_token=${this.csrfToken}`, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            console.log('[CockpitController] Sync Retornou:', data);
            
            let changed = false;
            
            if (this.role === 'secretary') {
                changed = this.updateBadge('pendentes', data.pending, '#f59e0b', 'Novo agendamento recebido!') || changed;
                changed = this.updateBadge('confirmados', data.confirmed, '#10b981') || changed;
                changed = this.updateBadge('falta', data.missed, '#ef4444') || changed;
                
                if (changed) {
                    console.log('[CockpitController] Houve mudança! Recarregando abas...');
                    this.reloadTabContent('aguardando', 'pendentes');
                    this.reloadTabContent('confirmados', 'confirmados');
                    this.reloadTabContent('falta', 'falta');
                }
            } else if (this.role === 'doctor') {
                let changedPacientes = this.updateBadge('pacientes', data.confirmed, '#10b981', 'Novo paciente aguardando!');
                let changedHistory = this.updateBadge('historico-hoje', data.history, '#64748b');
                changed = changedPacientes || changedHistory || changed;
                
                if (changed) {
                    this.reloadTabContent('confirmados', 'pacientes');
                    this.reloadTabContent('historico', 'historico-hoje');
                }
            }
        } catch (e) {
            // Ignora silenciosamente erros de rede causados por reload da página ou queda de conexão.
            console.error('[CockpitController] Erro de rede no sync (pode ser causado por navegação ou queda de conexão):', e);
        }
    }

    updateBadge(tabId, newCount, color, toastMsg = null) {
        const btn = document.getElementById('tab-btn-' + tabId);
        if (!btn) return false;
        
        let badge = btn.querySelector('span');
        let count = parseInt(badge ? badge.innerText : 0);
        if (isNaN(count)) count = 0; // Previne comportamentos anômalos
        
        if (count !== newCount) {
            // Emite o callback de que a badge mudou! (Desacoplado)
            if (newCount > count && toastMsg) {
                this.emit('badge:increased', { tab: tabId, msg: toastMsg });
            }
            
            if (!badge && newCount >= 0) {
                btn.innerHTML += `<span style="background:${color};color:white;border-radius:50%;padding:1px 7px;font-size:11px;margin-left:5px;">${newCount}</span>`;
            } else if (badge && newCount >= 0) {
                badge.innerText = newCount;
            }
            return true;
        }
        return false;
    }

    async reloadTabContent(tabMapKey, targetTabId) {
        const tabDiv = document.getElementById('tab-' + targetTabId);
        if (!tabDiv) return;
        
        try {
            const html = await DS.api.post('/cockpit/tab-ajax', `tab=${tabMapKey}&view_role=${this.role}&csrf_token=${this.csrfToken}`, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            tabDiv.innerHTML = html;
        } catch (e) {
            console.error(`Falha ao recarregar aba ${targetTabId}:`, e);
        }
    }

    switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
        
        const content = document.getElementById('tab-' + tabId);
        const btn = document.getElementById('tab-btn-' + tabId);
        
        if (content) content.classList.add('active');
        if (btn) btn.classList.add('active');
        
        sessionStorage.setItem('active_tab_' + window.location.pathname, tabId);
        
        // Força sincronização ao alternar para garantir que o usuário veja a versão mais atual
        this.forceSync();
        
        // Regras específicas de abas
        if (tabId === 'historico-hoje') {
            this._loadHistoricoHoje(content);
        }
    }

    async _loadHistoricoHoje(contentDiv) {
        contentDiv.innerHTML = '<div style="text-align: center; padding: 50px; color: var(--text-muted);"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        try {
            const html = await DS.api.post('/cockpit/history-tab', `view_role=${this.role}&csrf_token=${this.csrfToken}`, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            contentDiv.innerHTML = html;
        } catch (err) {
            contentDiv.innerHTML = '<div style="color:red; padding:20px;">Erro ao carregar histórico.</div>';
        }
    }

    restoreActiveTab() {
        const savedTab = sessionStorage.getItem('active_tab_' + window.location.pathname);
        if (savedTab && document.getElementById('tab-' + savedTab)) {
            this.switchTab(savedTab);
        }
    }
}
