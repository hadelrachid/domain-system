<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'clock_widget';
    }

    public function getTitle(): string
    {
        return 'Relógio do Sistema (Clock Widget)';
    }

    public function getSubtitle(): string
    {
        return 'Widget visual de horário e fuso horário para o painel de controle.';
    }

    public function getBadge(): string
    {
        return 'Widget Auto-Descoberto • Clock v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#f59e0b';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'clock-display-check',
                'label' => 'Verificar a sincronização do horário do servidor no Cockpit administrativo.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O <strong>Clock Widget</strong> exibe informações de data, hora e fuso horário configurado no ambiente da aplicação diretamente na tela principal do painel.</p>
        </div>";
    }
};
