<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'cookie_consent';
    }

    public function getTitle(): string
    {
        return 'Consentimento de Cookies (LGPD & Google Consent Mode v2)';
    }

    public function getSubtitle(): string
    {
        return 'Banner de conformidade com LGPD, GDPR e Google Consent Mode v2 com opções de aceitar, recusar e personalizar preferências.';
    }

    public function getBadge(): string
    {
        return 'Módulo Oficial • Privacidade & Cookies v1.0.0';
    }

    public function getAccentColor(): string
    {
        return '#00d284';
    }

    public function getAdminUrl(): ?string
    {
        return null;
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'cookie-banner-render',
                'label' => 'Banner de consentimento exibido com botões Aceitar, Recusar e links para Privacidade e Termos.',
            ],
            [
                'id'    => 'google-consent-v2',
                'label' => 'Google Consent Mode v2 acionado com ad_storage, analytics_storage, ad_user_data e ad_personalization.',
            ],
            [
                'id'    => 'cookie-reopen-privacy',
                'label' => 'Link de reabertura de preferências disponível no rodapé para revogação a qualquer momento.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return "<div style='color:var(--text-main, #c5c6c7); font-size:14px; line-height:1.7;'>
            <p>Este plugin implementa o banner oficial de conformidade de cookies da <strong>RachidD Store</strong> em estrita aderência às exigências da <strong>LGPD (Brasil)</strong>, <strong>GDPR (Europa)</strong> e do <strong>Google Consent Mode v2</strong>.</p>
            <ul>
                <li><strong>Botões Diretos:</strong> Permite ao visitante Aceitar Todos, Recusar Opcionais ou Personalizar por categorias.</li>
                <li><strong>Links de Transparência:</strong> Conecta diretamente à Política de Privacidade (<code>/privacidade</code>) e aos Termos de Uso (<code>/termos</code>).</li>
                <li><strong>Google Consent Mode v2:</strong> Inicializa os consentimentos como <code>denied</code> até a decisão do usuário, disparando <code>gtag('consent', 'update', ...)</code> dinamicamente.</li>
                <li><strong>Revogação Descomplicada:</strong> Permite alterar ou revogar as decisões através do link no rodapé do site.</li>
            </ul>
        </div>";
    }
};
