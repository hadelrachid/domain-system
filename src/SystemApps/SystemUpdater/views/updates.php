<?php

declare(strict_types=1);

use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Enum\UpdateStep;
use DomainSystem\Core\Update\Value\BackupRecord;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateState;
use DomainSystem\Core\Update\Value\UpdateTarget;

/** @var UpdateTarget $target */
/** @var UpdateCheckResult $check_result */
/** @var UpdateState|null $update_state */
/** @var list<BackupRecord> $backups */
/** @var bool $db_backup_supported */
/** @var string $csrf_token */
/** @var bool $checked_now */
/** @var string $base_url */

$installedVersion = htmlspecialchars($target->installedVersion, ENT_QUOTES, 'UTF-8');
$repoName = htmlspecialchars($target->repository, ENT_QUOTES, 'UTF-8');
$channelName = htmlspecialchars($target->channel, ENT_QUOTES, 'UTF-8');
$checkedAt = htmlspecialchars($check_result->checkedAt, ENT_QUOTES, 'UTF-8');
$csrfEscaped = htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8');
$basePrefix = rtrim((string)($base_url ?? ''), '/');
$actionCheckUrl = htmlspecialchars($basePrefix . '/admin/settings/updates/core/check', ENT_QUOTES, 'UTF-8');
$actionBeginUrl = htmlspecialchars($basePrefix . '/admin/settings/updates/core/begin', ENT_QUOTES, 'UTF-8');
$actionAdvanceUrl = htmlspecialchars($basePrefix . '/admin/settings/updates/core/advance', ENT_QUOTES, 'UTF-8');
$actionHealthUrl = htmlspecialchars($basePrefix . '/admin/settings/updates/core/health', ENT_QUOTES, 'UTF-8');
$actionRollbackUrl = htmlspecialchars($basePrefix . '/admin/settings/updates/core/rollback', ENT_QUOTES, 'UTF-8');
$actionPruneUrl = htmlspecialchars($basePrefix . '/admin/settings/updates/backups/prune', ENT_QUOTES, 'UTF-8');
$settingsBackUrl = htmlspecialchars($basePrefix . '/admin/settings', ENT_QUOTES, 'UTF-8');

$release = $check_result->release;
$backupsList = is_array($backups ?? null) ? $backups : [];
$dbBackupSupported = (bool)($db_backup_supported ?? true);
$currentState = $update_state ?? null;
$preflightWarnings = [];
if ($currentState !== null && isset($currentState->context['preflight']['checks']) && is_array($currentState->context['preflight']['checks'])) {
    foreach ($currentState->context['preflight']['checks'] as $chk) {
        if (($chk['level'] ?? '') === 'warn') {
            $preflightWarnings[] = (string)($chk['message'] ?? '');
        }
    }
}
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Configurações Globais</h1>
</div>

<div class="sys-tabs">
    <a href="<?= $settingsBackUrl ?>" class="sys-tab" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-cog"></i> Geral</a>
    <a href="<?= $settingsBackUrl ?>#tab-avancado" class="sys-tab" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-tools"></i> Avançado</a>
    <a href="<?= htmlspecialchars($basePrefix . '/admin/settings/updates', ENT_QUOTES, 'UTF-8') ?>" class="sys-tab active" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-sync-alt"></i> Atualizações</a>
</div>

<div class="sys-content active" style="padding: 15px 20px; max-width: 940px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; font-size: 20px;">🔄 Atualizações do Sistema</h2>
            <p style="margin: 6px 0 0; color: #64748b; font-size: 13px;">
                Repositório oficial: <code><?= $repoName ?></code> (canal <code><?= $channelName ?></code>)
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="POST" action="<?= $actionCheckUrl ?>" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= $csrfEscaped ?>">
                <button type="submit" class="btn btn-activate" style="background: #0284c7; border-color: #0284c7; color: #fff; font-weight: 600; cursor: pointer;">
                    🔍 Verificar agora
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($checked_now)): ?>
        <div style="background: rgba(16, 185, 129, 0.14); color: #6ee7b7; padding: 12px 16px; margin-bottom: 20px; border-left: 4px solid #10b981; border-radius: 4px; font-size: 13px;">
            ✔ Verificação de versão concluída em <code><?= $checkedAt ?></code>.
        </div>
    <?php endif; ?>

    <!-- Cartão de Status da Versão -->
    <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 22px; color: #f8fafc; margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Versão Instalada</span>
                <div style="font-size: 24px; font-weight: 700; color: #38bdf8; margin-top: 4px; font-family: monospace;">
                    v<?= $installedVersion ?>
                </div>
            </div>

            <div>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Última Release no GitHub</span>
                <div style="font-size: 24px; font-weight: 700; color: <?= $check_result->status === UpdateStatus::Available ? '#fbbf24' : '#10b981' ?>; margin-top: 4px; font-family: monospace;">
                    <?= $release !== null ? 'v' . htmlspecialchars($release->version, ENT_QUOTES, 'UTF-8') : '—' ?>
                </div>
            </div>

            <div>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600;">Status</span>
                <div style="margin-top: 8px;">
                    <?php if ($check_result->status === UpdateStatus::Available): ?>
                        <span style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.45); padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 13px;">
                            🔔 Nova versão disponível
                        </span>
                    <?php elseif ($check_result->status === UpdateStatus::UpToDate): ?>
                        <span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 13px;">
                            ✔ Sistema atualizado
                        </span>
                    <?php else: ?>
                        <span style="background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.35); padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 13px;">
                            ⚠️ Não foi possível verificar
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #1e293b; font-size: 12px; color: #64748b;">
            Última verificação: <code><?= $checkedAt ?></code>
        </div>
    </div>

    <?php if ($check_result->status === UpdateStatus::Unknown && !empty($check_result->reasons)): ?>
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 16px; color: #fca5a5; margin-bottom: 20px;">
            <strong style="display: block; margin-bottom: 6px;">Motivo da indisponibilidade:</strong>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                <?php foreach ($check_result->reasons as $reason): ?>
                    <li><?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($preflightWarnings)): ?>
        <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 8px; padding: 16px; color: #fcd34d; margin-bottom: 20px;">
            <strong style="display: block; margin-bottom: 6px;">⚠️ Avisos de Pré-checagem:</strong>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                <?php foreach ($preflightWarnings as $warnMsg): ?>
                    <li><?= htmlspecialchars($warnMsg, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($release !== null): ?>
        <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 22px; color: #f8fafc; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
                <h2 style="margin: 0; font-size: 18px; color: #f8fafc;">
                    📦 Detalhes da Release <code><?= htmlspecialchars($release->tag, ENT_QUOTES, 'UTF-8') ?></code>
                </h2>
                <span style="font-size: 12px; color: #94a3b8;">
                    Publicada em: <strong><?= htmlspecialchars($release->publishedAt, ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
            </div>

            <div style="margin-bottom: 14px; font-size: 12px; color: #94a3b8;">
                Pacote: <code><?= htmlspecialchars($release->assetName, ENT_QUOTES, 'UTF-8') ?></code>
                • SHA-256: <code><?= htmlspecialchars($release->sha256, ENT_QUOTES, 'UTF-8') ?></code>
            </div>

            <h3 style="font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Notas de Lançamento (Changelog):</h3>
            <pre style="background: #090d16; color: #e2e8f0; padding: 16px; border-radius: 6px; border: 1px solid #1e293b; white-space: pre-wrap; word-wrap: break-word; font-size: 13px; line-height: 1.5; margin: 0 0 18px 0;"><?= htmlspecialchars($release->changelog !== '' ? $release->changelog : 'Sem notas de lançamento informadas.', ENT_QUOTES, 'UTF-8') ?></pre>

            <?php if ($check_result->status === UpdateStatus::Available): ?>
                <div style="background: #131c31; border: 1px solid #1e293b; border-radius: 8px; padding: 16px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #38bdf8;">O que será feito durante a atualização:</h4>
                    <ol style="margin: 0 0 14px 0; padding-left: 20px; font-size: 13px; color: #cbd5e1; line-height: 1.6;">
                        <li>Download do pacote oficial e verificação criptográfica SHA-256 + manifesto.</li>
                        <li>Pré-checagem de ambiente, espaço em disco, permissões e plano de banco de dados.</li>
                        <li>Backup automático do código-fonte e do banco de dados antes de qualquer alteração.</li>
                        <li>Ativação do modo de manutenção, troca atômica de arquivos (preservando <code>.env</code>, <code>config/</code>, <code>storage/</code> e uploads) e execução de migrations aditivas.</li>
                        <li>Verificação de saúde em duas fases e desativação automática da manutenção (ou rollback automático em caso de falha).</li>
                    </ol>

                    <div style="margin-bottom: 14px;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: <?= !$dbBackupSupported ? '#fbbf24' : '#94a3b8' ?>; cursor: pointer;">
                            <input type="checkbox" id="ds-confirm-no-db-backup" value="1" <?= !$dbBackupSupported ? 'required' : '' ?>>
                            <span>
                                Prosseguir mesmo se o backup automático do banco não estiver disponível (confirmação explícita de segurança)
                            </span>
                        </label>
                    </div>

                    <button
                        type="button"
                        id="ds-btn-start-update"
                        data-version="<?= htmlspecialchars($release->version, ENT_QUOTES, 'UTF-8') ?>"
                        class="button button-primary"
                        style="background: #10b981; border-color: #059669; color: #fff; font-weight: 700; padding: 10px 18px; font-size: 14px; cursor: pointer;">
                        🚀 Atualizar para v<?= htmlspecialchars($release->version, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Painel de Progresso em Tempo Real -->
    <div id="ds-update-progress-panel" style="display: none; background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 22px; color: #f8fafc; margin-bottom: 20px;">
        <h2 style="margin: 0 0 12px 0; font-size: 17px; color: #38bdf8;">⚡ Progresso da Atualização</h2>
        <div style="background: #1e293b; border-radius: 6px; height: 12px; overflow: hidden; margin-bottom: 16px;">
            <div id="ds-update-progress-bar" style="background: linear-gradient(90deg, #0284c7, #10b981); width: 0%; height: 100%; transition: width 0.3s ease;"></div>
        </div>
        <div id="ds-update-status-message" style="font-size: 13px; color: #e2e8f0; margin-bottom: 12px; font-weight: 600;">
            Iniciando atualização...
        </div>
        <ul id="ds-update-steps-list" style="list-style: none; margin: 0; padding: 0; font-size: 13px; display: grid; gap: 6px;">
            <li data-step="checked">⏳ 1. Verificação inicial (Checked)</li>
            <li data-step="downloaded">⏳ 2. Download do pacote (Downloaded)</li>
            <li data-step="verified">⏳ 3. Verificação SHA-256 e Manifesto (Verified)</li>
            <li data-step="preflighted">⏳ 4. Pré-checagem do sistema (Preflighted)</li>
            <li data-step="backed_up">⏳ 5. Backup de código e banco (BackedUp)</li>
            <li data-step="maintenance_on">⏳ 6. Modo de manutenção ativado (MaintenanceOn)</li>
            <li data-step="applied">⏳ 7. Aplicação atômica dos arquivos (Applied)</li>
            <li data-step="migrated">⏳ 8. Execução de migrations (Migrated)</li>
            <li data-step="schema_verified">⏳ 9. Verificação do contrato de banco (SchemaVerified)</li>
            <li data-step="health_checked">⏳ 10. Verificação de saúde pós-atualização (HealthChecked)</li>
            <li data-step="done">⏳ 11. Concluído (Done)</li>
        </ul>
    </div>

    <!-- Lista de Backups Disponíveis -->
    <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 22px; color: #f8fafc;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
            <h2 style="margin: 0; font-size: 18px; color: #f8fafc;">🛡️ Backups do Sistema</h2>
            <?php if (count($backupsList) > 2): ?>
                <button
                    type="button"
                    id="ds-btn-prune-backups"
                    class="button"
                    style="background: #334155; border-color: #475569; color: #f8fafc; font-size: 12px; cursor: pointer;">
                    🧹 Remover antigos (manter 2 mais recentes)
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($backupsList)): ?>
            <p style="margin: 0; color: #94a3b8; font-size: 13px;">Nenhum ponto de restauração registrado até o momento.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #1e293b; text-align: left; color: #94a3b8;">
                        <th style="padding: 8px;">ID do Backup</th>
                        <th style="padding: 8px;">Versão</th>
                        <th style="padding: 8px;">Criado em</th>
                        <th style="padding: 8px;">Banco Incluído</th>
                        <th style="padding: 8px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backupsList as $record): ?>
                        <tr style="border-bottom: 1px solid #1e293b;">
                            <td style="padding: 8px;"><code><?= htmlspecialchars($record->id, ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td style="padding: 8px;"><?= htmlspecialchars($record->label, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 8px;"><?= htmlspecialchars($record->createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 8px;"><?= $record->dumpPath !== null ? '✔ Sim' : '— Não' ?></td>
                            <td style="padding: 8px; text-align: right;">
                                <button
                                    type="button"
                                    class="button ds-btn-restore-backup"
                                    data-backup-id="<?= htmlspecialchars($record->id, ENT_QUOTES, 'UTF-8') ?>"
                                    style="background: #dc2626; border-color: #b91c1c; color: #fff; font-size: 12px; cursor: pointer;">
                                    ⏪ Restaurar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode($csrf_token ?? '') ?>;
    const urls = {
        begin: <?= json_encode($basePrefix . '/admin/settings/updates/core/begin') ?>,
        advance: <?= json_encode($basePrefix . '/admin/settings/updates/core/advance') ?>,
        health: <?= json_encode($basePrefix . '/admin/settings/updates/core/health') ?>,
        rollback: <?= json_encode($basePrefix . '/admin/settings/updates/core/rollback') ?>,
        prune: <?= json_encode($basePrefix . '/admin/settings/updates/backups/prune') ?>
    };

    const orderedSteps = [
        'checked',
        'downloaded',
        'verified',
        'preflighted',
        'backed_up',
        'maintenance_on',
        'applied',
        'migrated',
        'schema_verified',
        'health_checked',
        'done'
    ];

    function askConfirmation(message, onConfirm) {
        if (window.OS && typeof window.OS.confirm === 'function') {
            window.OS.confirm(message, onConfirm);
            return;
        }
        if (window.confirm(message)) {
            onConfirm();
        }
    }

    function updateStepIndicators(currentStep, isError) {
        const panel = document.getElementById('ds-update-progress-panel');
        const bar = document.getElementById('ds-update-progress-bar');
        if (panel) panel.style.display = 'block';

        const idx = orderedSteps.indexOf(currentStep);
        if (bar && idx >= 0) {
            const pct = Math.min(100, Math.round(((idx + 1) / orderedSteps.length) * 100));
            bar.style.width = pct + '%';
            if (isError) {
                bar.style.background = '#ef4444';
            }
        }

        const items = document.querySelectorAll('#ds-update-steps-list li[data-step]');
        items.forEach(function (li) {
            const stepName = li.getAttribute('data-step');
            const stepIdx = orderedSteps.indexOf(stepName);
            const labelText = li.textContent.replace(/^([^\s]+)\s/, '');
            if (stepIdx < idx || currentStep === 'done') {
                li.textContent = '✅ ' + labelText;
                li.style.color = '#34d399';
            } else if (stepIdx === idx) {
                li.textContent = (isError ? '❌ ' : '🔄 ') + labelText;
                li.style.color = isError ? '#f87171' : '#38bdf8';
            } else {
                li.textContent = '⏳ ' + labelText;
                li.style.color = '#94a3b8';
            }
        });
    }

    function setStatusText(text, isError) {
        const el = document.getElementById('ds-update-status-message');
        if (!el) return;
        el.textContent = text;
        el.style.color = isError ? '#fca5a5' : '#e2e8f0';
    }

    async function postForm(url, params) {
        const body = new URLSearchParams(params);
        body.set('csrf_token', csrfToken);
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        return resp.json();
    }

    async function runUpdateLoop() {
        const startBtn = document.getElementById('ds-btn-start-update');
        if (startBtn) startBtn.disabled = true;

        const confirmNoDbCheckbox = document.getElementById('ds-confirm-no-db-backup');
        const confirmNoDb = confirmNoDbCheckbox && confirmNoDbCheckbox.checked ? '1' : '0';

        try {
            setStatusText('Iniciando atualização e adquirindo lock exclusivo...', false);
            const beginRes = await postForm(urls.begin, { confirm_no_db_backup: confirmNoDb });

            if (!beginRes.ok || !beginRes.state) {
                setStatusText('Falha ao iniciar: ' + (beginRes.error || 'Erro desconhecido.'), true);
                if (startBtn) startBtn.disabled = false;
                return;
            }

            let state = beginRes.state;
            const updateId = state.updateId || state.update_id;
            updateStepIndicators(state.step, false);

            let guard = 0;
            while (guard < 25) {
                guard++;
                setStatusText('Executando etapa após ' + state.step + '...', false);
                const advRes = await postForm(urls.advance, { update_id: updateId });

                if (!advRes.state) {
                    setStatusText('Erro na comunicação: ' + (advRes.error || 'Resposta inválida.'), true);
                    updateStepIndicators(state.step, true);
                    return;
                }

                state = advRes.state;
                updateStepIndicators(state.step, !advRes.ok);

                if (state.context && state.context.awaiting === 'health') {
                    setStatusText('Verificando saúde do sistema em nova requisição HTTP...', false);
                    const healthUrl = urls.health + '?update_id=' + encodeURIComponent(updateId);
                    await fetch(healthUrl, { method: 'GET', credentials: 'same-origin' });
                    continue;
                }

                if (state.step === 'done') {
                    setStatusText('✅ Atualização concluída com sucesso! Recarregando página...', false);
                    setTimeout(function () { window.location.reload(); }, 1500);
                    return;
                }

                if (state.step === 'rolled_back' || state.step === 'failed' || !advRes.ok) {
                    setStatusText(
                        '❌ Atualização interrompida (' + state.step + '): ' + (state.error || advRes.error || ''),
                        true
                    );
                    return;
                }
            }
        } catch (err) {
            setStatusText('Erro inesperado de rede: ' + (err && err.message ? err.message : String(err)), true);
            if (startBtn) startBtn.disabled = false;
        }
    }

    const startBtn = document.getElementById('ds-btn-start-update');
    if (startBtn) {
        startBtn.addEventListener('click', function () {
            const ver = startBtn.getAttribute('data-version') || '';
            askConfirmation(
                'Deseja iniciar a atualização automática do núcleo para v' + ver + '? Um backup completo será criado antes da aplicação.',
                runUpdateLoop
            );
        });
    }

    document.querySelectorAll('.ds-btn-restore-backup').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const backupId = btn.getAttribute('data-backup-id') || '';
            askConfirmation(
                'Deseja restaurar o backup "' + backupId + '"? Os arquivos atuais serão substituídos pela versão do backup.',
                async function () {
                    const res = await postForm(urls.rollback, { backup_id: backupId });
                    if (res.ok) {
                        window.location.reload();
                    } else {
                        alert('Erro ao restaurar backup: ' + (res.error || 'Falha desconhecida.'));
                    }
                }
            );
        });
    });

    const pruneBtn = document.getElementById('ds-btn-prune-backups');
    if (pruneBtn) {
        pruneBtn.addEventListener('click', function () {
            askConfirmation(
                'Deseja remover os backups antigos e manter apenas os 2 mais recentes?',
                async function () {
                    const res = await postForm(urls.prune, {});
                    if (res.ok) {
                        window.location.reload();
                    } else {
                        alert('Erro ao limpar backups: ' + (res.error || 'Falha desconhecida.'));
                    }
                }
            );
        });
    }
})();
</script>
