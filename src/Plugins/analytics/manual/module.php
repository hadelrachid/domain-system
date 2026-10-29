<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'analytics';
    }

    public function getTitle(): string
    {
        return 'Analytics & Métricas';
    }

    public function getSubtitle(): string
    {
        return 'Monitoramento de visualizações de páginas, eventos de interação e widgets de audiência.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • Analytics v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#06b6d4';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/analytics';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'analytics-metrics-check',
                'label' => 'Validar o registro de acessos nas rotas públicas e nos widgets informativos.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O módulo <strong>Analytics</strong> provê métricas consolidadas de engajamento e exibição de páginas, integrando-se aos cards do Cockpit.</p>
        </div>";
    }
};
