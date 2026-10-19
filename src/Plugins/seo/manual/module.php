<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'seo';
    }

    public function getTitle(): string
    {
        return 'Plugin SEO (Metadados, OpenGraph & Sitemap)';
    }

    public function getSubtitle(): string
    {
        return 'Gerenciamento dinâmico de títulos, meta-descriptions e tags sociais via SeoManagerInterface.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • SEO v2.3.0';
    }

    public function getAccentColor(): string
    {
        return '#10b981';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/seo';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'seo-meta-injection',
                'label' => 'Verificar se as páginas públicas e aulas do Academy injetam <title> e <meta name="description"> via SeoManagerInterface.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p style='margin-top:0;'>O <strong>Plugin SEO</strong> disponibiliza o contrato <code>SeoManagerInterface</code> no Container de Injeção de Dependências, permitindo que qualquer plugin (como <em>Pages</em> e <em>Academy</em>) defina títulos e meta-descriptions dinâmicas sem manipular HTML global.</p>
        </div>";
    }
};
