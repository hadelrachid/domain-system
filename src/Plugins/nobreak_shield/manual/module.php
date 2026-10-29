<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'nobreak_shield';
    }

    public function getTitle(): string
    {
        return 'Filtro de Resiliência de Interface (No-Break Shield)';
    }

    public function getSubtitle(): string
    {
        return 'Camada de tolerância a falhas visuais para componentes de renderização do painel.';
    }

    public function getBadge(): string
    {
        return 'Resiliência • NoBreak v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#10b981';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/supervision';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'nobreak-shield-check',
                'label' => 'Validar a captura de exceções em widgets visuais do painel.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O <strong>No-Break Shield</strong> atua como interceptador de exceções em tempo de renderização de widgets para evitar que erros em componentes auxiliares interrompam a exibição do layout principal.</p>
        </div>";
    }
};
