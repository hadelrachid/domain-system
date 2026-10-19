<?php

declare(strict_types=1);

use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Value\UpdateCheckResult;
use DomainSystem\Core\Update\Value\UpdateTarget;

/** @var UpdateTarget $target */
/** @var UpdateCheckResult $check_result */
/** @var string $csrf_token */
/** @var bool $checked_now */
/** @var string $base_url */

$installedVersion = htmlspecialchars($target->installedVersion, ENT_QUOTES, 'UTF-8');
$repoName = htmlspecialchars($target->repository, ENT_QUOTES, 'UTF-8');
$channelName = htmlspecialchars($target->channel, ENT_QUOTES, 'UTF-8');
$checkedAt = htmlspecialchars($check_result->checkedAt, ENT_QUOTES, 'UTF-8');
$csrfEscaped = htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8');
$actionCheckUrl = htmlspecialchars(($base_url ?? '') . '/admin/settings/updates/core/check', ENT_QUOTES, 'UTF-8');
$settingsBackUrl = htmlspecialchars(($base_url ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8');
$release = $check_result->release;
?>
<div class="wrap" style="max-width: 900px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <div>
            <h1 style="margin: 0;">🔄 Atualizações do Sistema</h1>
            <p style="margin: 6px 0 0; color: #94a3b8; font-size: 13px;">
                Repositório oficial: <code><?= $repoName ?></code> (canal <code><?= $channelName ?></code>)
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="<?= $settingsBackUrl ?>" class="button" style="text-decoration: none;">← Voltar para Configurações</a>
            <form method="POST" action="<?= $actionCheckUrl ?>" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= $csrfEscaped ?>">
                <button type="submit" class="button button-primary" style="background: #0284c7; border-color: #0284c7; color: #fff; font-weight: 600;">
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

    <?php if ($release !== null): ?>
        <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 22px; color: #f8fafc;">
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
            <pre style="background: #090d16; color: #e2e8f0; padding: 16px; border-radius: 6px; border: 1px solid #1e293b; white-space: pre-wrap; word-wrap: break-word; font-size: 13px; line-height: 1.5; margin: 0;"><?= htmlspecialchars($release->changelog !== '' ? $release->changelog : 'Sem notas de lançamento informadas.', ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    <?php endif; ?>
</div>
