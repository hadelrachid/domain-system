<?php
/**
 * View Administrativa: Configurações do Hub DomainDrop
 *
 * @var array<string, mixed> $config
 * @var float $rate
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 */
?>
<div class="domain-drop-settings" style="max-width: 900px; margin: 0 auto;">

    <!-- Top Header & Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <a href="<?= $baseUrl ?>/admin/dropship" style="color: var(--text-muted, #94a3b8); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fas fa-satellite-dish"></i> DomainDrop
                </a>
                <span style="color: var(--text-muted, #94a3b8); font-size: 12px;">/</span>
                <span style="color: #ff4747; font-size: 13px; font-weight: 600;">Configurações</span>
            </div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-sliders-h" style="color: #ff4747;"></i>
                Configurações da API &amp; Conexão AliExpress
            </h1>
            <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Gerenciamento de credenciais oficiais do AliExpress Open Platform e conectividade do Gateway.
            </p>
        </div>

        <div>
            <a href="<?= $baseUrl ?>/admin/dropship" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left"></i> Voltar ao Catálogo
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($flashMsg) && $flashType === 'error'): ?>
        <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
        </div>
    <?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
        <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Live Test Connection Card -->
    <div id="aliexpressConnCard" style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-radius: 8px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div id="connIconBadge" style="width: 44px; height: 44px; border-radius: 50%; background: rgba(69,243,255,0.15); border: 1px solid rgba(69,243,255,0.3); display: flex; align-items: center; justify-content: center; color: #45f3ff; font-size: 20px; flex-shrink: 0;">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 3px;">
                        <span id="connTitle" style="color: #fff; font-weight: 700; font-size: 15px;">Diagnóstico do Servidor AliExpress</span>
                        <span id="connBadge" style="background: rgba(255,255,255,0.1); color: #cbd5e1; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Pronto para Testar</span>
                    </div>
                    <div id="connSubtext" style="color: var(--text-muted, #94a3b8); font-size: 12px;">
                        Gateway Oficial: <code style="color: #45f3ff;">api-sg.aliexpress.com/sync</code> • Protocolo: HMAC-SHA256 • App: <?= htmlspecialchars((string)($config['app_key'] ?? '558628')) ?>
                    </div>
                </div>
            </div>

            <button type="button" id="btnTestAliExpressConn" style="background: linear-gradient(135deg, #45f3ff 0%, #0099ff 100%); color: #0b0f17; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(69,243,255,0.25); transition: all 0.2s ease;">
                <i class="fas fa-bolt"></i> Testar Conexão com o Servidor
            </button>
        </div>

        <!-- Live Result Banner -->
        <div id="connResultBox" style="display: none; margin-top: 16px; padding: 14px 18px; border-radius: 6px; font-size: 13px; line-height: 1.5;"></div>
    </div>

    <!-- Main Settings Form -->
    <form action="<?= $baseUrl ?>/admin/dropship/settings" method="POST" style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 26px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <h3 style="margin: 0 0 18px 0; font-size: 16px; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px;">
            <i class="fas fa-key" style="color: #ff4747; margin-right: 6px;"></i> Credenciais da Aplicação (AliExpress Open Platform)
        </h3>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                AppKey
            </label>
            <input type="text" name="app_key" value="<?= htmlspecialchars((string)($config['app_key'] ?? '558628')) ?>" required style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 10px 14px; border-radius: 6px; font-size: 13px; box-sizing: border-box; font-family: monospace;">
            <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                Identificador da sua aplicação no painel openservice.aliexpress.com.
            </span>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                App Secret
            </label>
            <div style="display: flex; align-items: stretch;">
                <input type="password" id="input_app_secret" name="app_secret" value="<?= htmlspecialchars((string)($config['app_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Cole seu App Secret aqui" style="flex: 1; min-width: 0; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-right: none; border-top-left-radius: 6px; border-bottom-left-radius: 6px; border-top-right-radius: 0; border-bottom-right-radius: 0; color: #fff; padding: 10px 14px; font-size: 13px; box-sizing: border-box; font-family: monospace;">
                <button type="button" onclick="ddToggleSecret('input_app_secret', this)" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-left: 1px solid rgba(255,255,255,0.08); border-top-right-radius: 6px; border-bottom-right-radius: 6px; border-top-left-radius: 0; border-bottom-left-radius: 0; padding: 0 14px; cursor: pointer; font-size: 17px; display: inline-flex; align-items: center; justify-content: center; color: #cbd5e1; transition: background 0.15s ease;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'" title="Revelar ou ocultar App Secret" aria-label="Mostrar ou ocultar chave">👁️</button>
            </div>
            <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                Armazenado com segurança em <code>config/plugins/domain_drop.json</code> (protegido contra commit no Git). Deixe em branco para manter o atual.
            </span>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                Access Token OAuth2 (Sessão do Dropshipper)
            </label>
            <div style="display: flex; align-items: stretch;">
                <input type="password" id="input_access_token" name="access_token" value="<?= htmlspecialchars((string)($config['access_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Cole o Access Token de autorização OAuth (opcional)" style="flex: 1; min-width: 0; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-right: none; border-top-left-radius: 6px; border-bottom-left-radius: 6px; border-top-right-radius: 0; border-bottom-right-radius: 0; color: #fff; padding: 10px 14px; font-size: 13px; box-sizing: border-box; font-family: monospace;">
                <button type="button" onclick="ddToggleSecret('input_access_token', this)" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-left: 1px solid rgba(255,255,255,0.08); border-top-right-radius: 6px; border-bottom-right-radius: 6px; border-top-left-radius: 0; border-bottom-left-radius: 0; padding: 0 14px; cursor: pointer; font-size: 17px; display: inline-flex; align-items: center; justify-content: center; color: #cbd5e1; transition: background 0.15s ease;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'" title="Revelar ou ocultar Access Token" aria-label="Mostrar ou ocultar token">👁️</button>
            </div>
            <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                Token de sessão concedido pelo AliExpress para chamadas autenticadas de produtos e pedidos dropshipping. Caso não possua, o motor utilizará a base inteligente curada.
            </span>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                Gateway Oficial da API (AliExpress IOP)
            </label>
            <input type="text" readonly value="https://api-sg.aliexpress.com/sync" style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid var(--border, rgba(255,255,255,0.1)); color: #94a3b8; padding: 10px 14px; border-radius: 6px; font-size: 13px; box-sizing: border-box; font-family: monospace;">
            <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                Endpoint global HMAC-SHA256 utilizado para sincronização de catálogo, rastreio e pedidos.
            </span>
        </div>

        <!-- Card de Redirecionamento da Estratégia de Precificação -->
        <div style="margin: 28px 0 20px 0; background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(6, 78, 59, 0.15) 100%); border: 1.5px solid rgba(16, 185, 129, 0.35); border-radius: 8px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px; max-width: 580px;">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 20px; flex-shrink: 0;">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 4px 0; color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        Margens de Lucro &amp; Sentinela de Volatilidade
                        <span style="font-size: 11px; background: rgba(16, 185, 129, 0.2); color: #5cd67d; padding: 2px 8px; border-radius: 10px; font-weight: 600;">Centralizado no Catálogo</span>
                    </h4>
                    <p style="margin: 0; color: #cbd5e1; font-size: 12.5px; line-height: 1.5;">
                        Os controles comerciais de <strong>Markup %</strong>, <strong>Lucro Mínimo (Piso)</strong>, <strong>Índice de Volatilidade (12%)</strong> e o combobox de <strong>Varredura da Sentinela (3h, 4h, 6h, 12h)</strong> foram centralizados diretamente na tela de <strong>Catálogo &amp; Mineração</strong> para operação rápida do dropshipper.
                    </p>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/admin/dropship" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; text-decoration: none; padding: 11px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); transition: all 0.2s ease;">
                <i class="fas fa-calculator"></i> Ajustar Margens no Catálogo &rarr;
            </a>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 18px;">
            <button type="submit" style="background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); border: none; color: #fff; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btnTest = document.getElementById('btnTestAliExpressConn');
        var resultBox = document.getElementById('connResultBox');
        var badge = document.getElementById('connBadge');
        var iconBadge = document.getElementById('connIconBadge');
        var title = document.getElementById('connTitle');

        if (!btnTest) return;

        btnTest.addEventListener('click', function() {
            var appKeyInput = document.querySelector('input[name="app_key"]');
            var appSecretInput = document.querySelector('input[name="app_secret"]');
            var accessTokenInput = document.querySelector('input[name="access_token"]');
            var csrfInput = document.querySelector('input[name="csrf_token"]');

            var originalHtml = btnTest.innerHTML;
            btnTest.disabled = true;
            btnTest.style.opacity = '0.7';
            btnTest.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando Conexão...';

            resultBox.style.display = 'block';
            resultBox.style.background = 'rgba(69,243,255,0.08)';
            resultBox.style.border = '1px solid rgba(69,243,255,0.25)';
            resultBox.style.color = '#cbd5e1';
            resultBox.innerHTML = '<i class="fas fa-sync fa-spin" style="color: #45f3ff;"></i> Enviando pacote criptográfico assinado com HMAC-SHA256 para o gateway api-sg.aliexpress.com...';

            var formData = new FormData();
            formData.append('csrf_token', csrfInput ? csrfInput.value : '');
            formData.append('app_key', appKeyInput ? appKeyInput.value : '');
            formData.append('app_secret', appSecretInput ? appSecretInput.value : '');
            formData.append('access_token', accessTokenInput ? accessTokenInput.value : '');
            formData.append('ajax', '1');

            fetch('<?= $baseUrl ?>/admin/dropship/test-connection', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                return res.json().catch(function() {
                    return { success: false, status: 'error', message: 'Erro na resposta HTTP (' + res.status + ')' };
                });
            })
            .then(function(data) {
                btnTest.disabled = false;
                btnTest.style.opacity = '1';
                btnTest.innerHTML = originalHtml;

                if (data.status === 'ok' || data.success === true) {
                    // ✔️ Conexão OK
                    badge.textContent = '✔️ Conectado & Operante';
                    badge.style.background = 'rgba(40,167,69,0.2)';
                    badge.style.color = '#5cd67d';
                    badge.style.border = '1px solid rgba(40,167,69,0.4)';

                    iconBadge.style.background = 'rgba(40,167,69,0.2)';
                    iconBadge.style.borderColor = 'rgba(40,167,69,0.4)';
                    iconBadge.style.color = '#5cd67d';
                    iconBadge.innerHTML = '<i class="fas fa-check-circle"></i>';

                    resultBox.style.background = 'rgba(40,167,69,0.12)';
                    resultBox.style.border = '1px solid #28a745';
                    resultBox.style.color = '#5cd67d';
                    resultBox.innerHTML = '<div style="font-weight: 700; margin-bottom: 4px; font-size: 14px;">✔️ Sucesso: Sincronização Estabelecida!</div>' + ddEscHtml(data.message);
                } else if (data.status === 'warning') {
                    // ⚠️ Modo Test / Access Token
                    badge.textContent = '⚠️ Modo Test / Requer Token';
                    badge.style.background = 'rgba(234,179,8,0.2)';
                    badge.style.color = '#facc15';
                    badge.style.border = '1px solid rgba(234,179,8,0.4)';

                    iconBadge.style.background = 'rgba(234,179,8,0.2)';
                    iconBadge.style.borderColor = 'rgba(234,179,8,0.4)';
                    iconBadge.style.color = '#facc15';
                    iconBadge.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';

                    resultBox.style.background = 'rgba(234,179,8,0.1)';
                    resultBox.style.border = '1px solid #eab308';
                    resultBox.style.color = '#fef08a';
                    resultBox.innerHTML = '<div style="font-weight: 700; margin-bottom: 4px; font-size: 14px;">⚠️ Servidor Alcançado — Ação Requerida</div>' + 
                        ddEscHtml(data.message) + 
                        '<div style="margin-top: 10px; font-size: 12px; background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 4px; border-left: 3px solid #eab308;">' +
                        '<strong>Dica:</strong> Se sua aplicação já foi aprovada em <em>Apply Online</em>, gere o <em>Access Token</em> permanente no console do AliExpress e cole-o no campo de token abaixo antes de salvar.' +
                        '</div>';
                } else {
                    // ❌ Erro de Chave / Token / Rede
                    badge.textContent = '❌ Falha de Autenticação';
                    badge.style.background = 'rgba(239,68,68,0.2)';
                    badge.style.color = '#f87171';
                    badge.style.border = '1px solid rgba(239,68,68,0.4)';

                    iconBadge.style.background = 'rgba(239,68,68,0.2)';
                    iconBadge.style.borderColor = 'rgba(239,68,68,0.4)';
                    iconBadge.style.color = '#f87171';
                    iconBadge.innerHTML = '<i class="fas fa-times-circle"></i>';

                    resultBox.style.background = 'rgba(220,50,50,0.12)';
                    resultBox.style.border = '1px solid #dc3232';
                    resultBox.style.color = '#fca5a5';
                    resultBox.innerHTML = '<div style="font-weight: 700; margin-bottom: 4px; font-size: 14px;">❌ Erro de Ligação com o Servidor</div>' + 
                        ddEscHtml(data.message || 'Falha ao validar credenciais com o servidor do AliExpress.') +
                        '<div style="margin-top: 10px; font-size: 12px; background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 4px; border-left: 3px solid #dc3232;">' +
                        '<strong>Solução:</strong> Verifique se digitou o <em>AppKey</em> e o <em>App Secret</em> corretamente sem espaços ou acesse seu painel em <strong>openservice.aliexpress.com</strong> para redefinir as credenciais.' +
                        '</div>';
                }
            })
            .catch(function(err) {
                btnTest.disabled = false;
                btnTest.style.opacity = '1';
                btnTest.innerHTML = originalHtml;

                badge.textContent = '❌ Erro de Rede';
                badge.style.background = 'rgba(239,68,68,0.2)';
                badge.style.color = '#f87171';

                resultBox.style.background = 'rgba(220,50,50,0.12)';
                resultBox.style.border = '1px solid #dc3232';
                resultBox.style.color = '#fca5a5';
                resultBox.innerHTML = '❌ Falha de comunicação de rede: ' + ddEscHtml(err && err.message ? err.message : 'Erro desconhecido');
            });
        });
    });

    function ddEscHtml(str) {
        var div = document.createElement('div');
        div.textContent = String(str || '');
        return div.innerHTML;
    }

    function ddToggleSecret(id, btn) {
        var input = document.getElementById(id);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? '🙈' : '👁️';
        btn.style.transform = show ? 'scale(1.15)' : 'scale(1)';
    }
    </script>

</div>
