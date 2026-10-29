<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'visual_themes';
    }

    public function getTitle(): string
    {
        return 'Temas Visuais & Paletas do Cockpit';
    }

    public function getSubtitle(): string
    {
        return 'Gerenciamento de esquemas de cores claros e escuros para o painel de administração.';
    }

    public function getBadge(): string
    {
        return 'Aparência • Temas v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#ec4899';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/themes';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'themes-palette-check',
                'label' => 'Verificar a alternância entre modo escuro e claro no painel administrativo.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O módulo <strong>Visual Themes</strong> controla a aplicação de variáveis CSS de cores primárias, secundárias e contraste no painel de administração.</p>
        </div>";
    }
};
