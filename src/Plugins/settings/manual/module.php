<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'settings';
    }

    public function getTitle(): string
    {
        return 'Configurações Globais da Plataforma';
    }

    public function getSubtitle(): string
    {
        return 'Gerenciamento de parâmetros do sistema, chaves globais, URLs e atualizações do núcleo.';
    }

    public function getBadge(): string
    {
        return 'Núcleo • Configurações v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#64748b';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/settings';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'settings-url-check',
                'label' => 'Verificar o endereço base e o nome configurado para a aplicação.',
            ],
            [
                'id'    => 'settings-update-check',
                'label' => 'Consultar a aba Atualizações para verificar versões mais recentes.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O módulo <strong>Settings</strong> centraliza os parâmetros globais do sistema, opções de cabeçalhos de segurança e interface com o atualizador automático via GitHub Releases.</p>
        </div>";
    }
};
