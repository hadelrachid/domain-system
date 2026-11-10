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
                Configurações da API & Margens de Lucro
            </h1>
            <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Gerenciamento de credenciais oficiais do AliExpress Open Platform e parâmetros da precificação dinâmica.
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

    <!-- Status da Conexão AliExpress -->
    <div style="background: rgba(40,167,69,0.1); border: 1px solid rgba(40,167,69,0.3); border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(40,167,69,0.2); display: flex; align-items: center; justify-content: center; color: #5cd67d; font-size: 18px;">
                <i class="fas fa-shield-check"></i>
            </div>
            <div>
                <div style="color: #fff; font-weight: 600; font-size: 14px;">Status ISV AliExpress: Aprovado & Operante</div>
                <div style="color: #94a3b8; font-size: 12px;">Permissões Ativas: <strong>AliExpress-dropship</strong> e <strong>System Tool</strong> | Protocolo: HMAC-SHA256</div>
            </div>
        </div>
        <div style="font-size: 12px; color: #94a3b8; background: rgba(0,0,0,0.3); padding: 4px 10px; border-radius: 6px;">
            Gateway: <code>api-sg.aliexpress.com/sync</code>
        </div>
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
            <input type="password" name="app_secret" placeholder="<?= !empty($config['app_secret']) ? '•••••••••••••••••••••••••••••••• (Salvo com segurança)' : 'Cole seu App Secret aqui' ?>" style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 10px 14px; border-radius: 6px; font-size: 13px; box-sizing: border-box; font-family: monospace;">
            <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                Armazenado com segurança em <code>config/plugins/domain_drop.json</code> (protegido contra commit no Git). Deixe em branco para manter o atual.
            </span>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                Callback URL Cadastrada
            </label>
            <input type="text" readonly value="<?= $baseUrl ?>/admin/dropship/callback" style="width: 100%; background: rgba(0,0,0,0.4); border: 1px solid var(--border, rgba(255,255,255,0.1)); color: #94a3b8; padding: 10px 14px; border-radius: 6px; font-size: 13px; box-sizing: border-box; font-family: monospace;">
            <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                URL de redirecionamento configurada no AliExpress Console.
            </span>
        </div>

        <h3 style="margin: 28px 0 18px 0; font-size: 16px; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px;">
            <i class="fas fa-calculator" style="color: #45f3ff; margin-right: 6px;"></i> Motor de Precificação Dinâmica & Margens
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                    Markup Padrão (%)
                </label>
                <div style="position: relative;">
                    <input type="number" step="1" min="10" name="default_markup_percent" value="<?= (float)($config['default_markup_percent'] ?? 100.0) ?>" required style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 10px 14px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                    <span style="position: absolute; right: 12px; top: 10px; color: #94a3b8; font-size: 13px;">%</span>
                </div>
                <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                    Ex: 100% significa dobrar o valor de custo convertido (2x).
                </span>
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 6px;">
                    Lucro Mínimo por Item (R$)
                </label>
                <div style="position: relative;">
                    <input type="number" step="0.5" min="0" name="min_profit_margin_brl" value="<?= (float)($config['min_profit_margin_brl'] ?? 30.0) ?>" required style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 10px 14px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                    <span style="position: absolute; right: 12px; top: 10px; color: #94a3b8; font-size: 13px;">R$</span>
                </div>
                <span style="font-size: 11px; color: var(--text-muted, #94a3b8); display: block; margin-top: 4px;">
                    Margem mínima de segurança para cobrir taxas de gateway e tráfego pago.
                </span>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 18px;">
            <button type="submit" style="background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); border: none; color: #fff; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </div>
    </form>

</div>
