<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'flextheme';
    }

    public function getTitle(): string
    {
        return 'Plugin FlexTheme (Motor de Temas & Roteamento)';
    }

    public function getSubtitle(): string
    {
        return 'Gerenciamento de temas públicos, compilação de páginas e resolução segura via SafeThemeResolver.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • FlexTheme v2.3.0';
    }

    public function getAccentColor(): string
    {
        return '#06b6d4';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/themes';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'flextheme-active-check',
                'label' => 'Verificar em /admin/themes qual tema público está ativo (ex: rachidd) e validar seu preview.',
            ],
            [
                'id'    => 'flextheme-safe-resolver',
                'label' => 'Confirmar que o SafeThemeResolver bloqueia travessia de diretórios (../) e carrega templates de pages/ com isolamento.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O <strong>FlexTheme</strong> orquestra a renderização do front-end público, conectando o tema ativo (configurado em <code>SiteStateInterface</code>) com os shortcodes e páginas dinâmicas do sistema.</p>
            <ul style='margin:0 0 10px 20px;'>
                <li><strong>Isolamento de Diretório:</strong> Todo carregamento de template passa pelo <code>SafeThemeResolver</code>.</li>
                <li><strong>Integração Nativa com Academy:</strong> As páginas fabricadas em <code>pages/*.php</code> herdam automaticamente o layout e variáveis CSS do tema ativo.</li>
            </ul>
        </div>";
    }
};
