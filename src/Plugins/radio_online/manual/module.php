<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'radio_online';
    }

    public function getTitle(): string
    {
        return 'Player de Áudio & Rádio Online';
    }

    public function getSubtitle(): string
    {
        return 'Integração de streaming de áudio e player no painel administrativo.';
    }

    public function getBadge(): string
    {
        return 'Mídia • Rádio v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#8b5cf6';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'radio-stream-check',
                'label' => 'Validar a conexão com o endpoint de streaming de áudio configurado.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O módulo <strong>Rádio Online</strong> disponibiliza um widget com suporte a streaming de áudio contínuo para reprodução no dashboard administrativo.</p>
        </div>";
    }
};
