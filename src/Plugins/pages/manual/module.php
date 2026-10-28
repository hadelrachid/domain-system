<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'pages';
    }

    public function getTitle(): string
    {
        return 'Plugin Pages (Gerenciador de Páginas & Hierarquia)';
    }

    public function getSubtitle(): string
    {
        return 'Criação de páginas hierárquicas, vinculação de templates do tema e árvore navegável.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • Pages v2.5.0';
    }

    public function getAccentColor(): string
    {
        return '#3b82f6';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/pages';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'pages-create-check',
                'label' => 'Acessar /admin/pages e validar a listagem em árvore de páginas publicadas e rascunhos.',
            ],
            [
                'id'    => 'pages-shortcode-check',
                'label' => 'Testar o uso de shortcodes (como [hub_de_cursos]) dentro do conteúdo de uma página.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O <strong>Plugin Pages</strong> gerencia páginas institucionais e árvores de documentação através dos contratos <code>PageLookupInterface</code> e <code>PageTreeRendererInterface</code>.</p>
            <ul style='margin:0 0 10px 20px;'>
                <li><strong>Suporte a Hierarquia:</strong> Páginas pai e filhas com geração automática de breadcrumbs e menus laterais.</li>
                <li><strong>Shortcodes Integrados:</strong> Processa shortcodes registrados por outros plugins sem acoplamento direto.</li>
            </ul>
        </div>";
    }
};
