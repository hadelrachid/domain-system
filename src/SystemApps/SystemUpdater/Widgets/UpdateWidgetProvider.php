<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemUpdater\Widgets;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;
use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\Update\UpdateCheckerInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Contracts\Update\UpdateTargetResolverInterface;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Service\UpdateNotifier;

final class UpdateWidgetProvider implements DashboardWidgetProviderInterface
{
    public const WIDGET_ID = 'sys_version_status';

    private ?UpdateNotifier $notifier;

    public function __construct(
        private readonly UpdateCheckerInterface $checker,
        private readonly UpdateTargetResolverInterface $targetResolver,
        ?UpdateStateStoreInterface $stateStore = null,
        ?UpdateNotifier $notifier = null,
        ?NotificationManagerInterface $notifications = null,
    ) {
        if ($notifier !== null) {
            $this->notifier = $notifier;
        } elseif ($stateStore !== null) {
            $this->notifier = new UpdateNotifier($stateStore, $notifications);
        } else {
            $this->notifier = null;
        }
    }

    public function getProviderName(): string
    {
        return 'Atualizações do Sistema';
    }

    public function getAvailableWidgets(): array
    {
        return [
            self::WIDGET_ID => [
                'title'       => 'Versão do Sistema',
                'description' => 'Exibe a versão instalada do núcleo e avisa quando há uma nova Release disponível.',
            ],
        ];
    }

    public function renderWidget(string $widgetId): string
    {
        if ($widgetId !== self::WIDGET_ID) {
            return '';
        }

        $target = $this->targetResolver->core();
        $result = $this->checker->check($target, false);
        $this->notifier?->notifyIfAvailable($result);

        $installed = htmlspecialchars($target->installedVersion, ENT_QUOTES, 'UTF-8');
        $baseUrl = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
        $updatesUrl = htmlspecialchars($baseUrl . '/admin/settings/updates', ENT_QUOTES, 'UTF-8');

        if ($result->status === UpdateStatus::Available && $result->release !== null) {
            $availableVersion = htmlspecialchars($result->release->version, ENT_QUOTES, 'UTF-8');
            return "
            <div style='display:flex; flex-direction:column; gap:10px;'>
                <div style='display:flex; justify-content:space-between; align-items:center;'>
                    <span style='color:var(--text-muted, #94a3b8); font-size:13px;'>Versão Instalada</span>
                    <span style='font-weight:600; font-family:monospace;'>v{$installed}</span>
                </div>
                <div style='display:flex; justify-content:space-between; align-items:center; background:rgba(245, 158, 11, 0.12); border:1px solid rgba(245, 158, 11, 0.4); border-radius:6px; padding:8px 10px;'>
                    <span style='color:#fbbf24; font-weight:600; font-size:13px;'>🔔 Nova versão disponível</span>
                    <span class='badge' style='background:#f59e0b; color:#0f172a; font-weight:700; padding:2px 8px; border-radius:4px; font-family:monospace;'>v{$availableVersion}</span>
                </div>
                <div style='text-align:right; margin-top:4px;'>
                    <a href='{$updatesUrl}' style='color:var(--accent-blue, #38bdf8); font-size:12px; font-weight:600; text-decoration:none;'>Ver detalhes da atualização →</a>
                </div>
            </div>";
        }

        if ($result->status === UpdateStatus::UpToDate) {
            return "
            <div style='display:flex; flex-direction:column; gap:10px;'>
                <div style='display:flex; justify-content:space-between; align-items:center;'>
                    <span style='color:var(--text-muted, #94a3b8); font-size:13px;'>Versão Instalada</span>
                    <span style='font-weight:600; font-family:monospace;'>v{$installed}</span>
                </div>
                <div style='display:flex; justify-content:space-between; align-items:center;'>
                    <span style='color:var(--text-muted, #94a3b8); font-size:13px;'>Status</span>
                    <span style='color:var(--accent-green, #10b981); font-weight:600; font-size:13px;'>✔ Sistema atualizado</span>
                </div>
                <div style='text-align:right; margin-top:4px;'>
                    <a href='{$updatesUrl}' style='color:var(--text-muted, #94a3b8); font-size:12px; text-decoration:none;'>Gerenciar atualizações →</a>
                </div>
            </div>";
        }

        return "
        <div style='display:flex; flex-direction:column; gap:10px;'>
            <div style='display:flex; justify-content:space-between; align-items:center;'>
                <span style='color:var(--text-muted, #94a3b8); font-size:13px;'>Versão Instalada</span>
                <span style='font-weight:600; font-family:monospace;'>v{$installed}</span>
            </div>
            <div style='display:flex; justify-content:space-between; align-items:center;'>
                <span style='color:var(--text-muted, #94a3b8); font-size:13px;'>Status</span>
                <span style='color:#94a3b8; font-size:12px;'>Verificação pendente</span>
            </div>
            <div style='text-align:right; margin-top:4px;'>
                <a href='{$updatesUrl}' style='color:var(--accent-blue, #38bdf8); font-size:12px; text-decoration:none;'>Verificar agora →</a>
            </div>
        </div>";
    }
}
