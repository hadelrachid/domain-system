<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface;

/**
 * Motor de Higienização de Títulos e Copywriting de Produtos (SRP / DIP).
 * 
 * Transforma os textos poluídos e genéricos do AliExpress em páginas de produtos
 * profissionais, com títulos concisos e descrições orientadas à conversão (AIDA).
 */
class AiProductEnricherService implements AiProductEnricherInterface
{
    /**
     * Palavras e termos de spam frequentemente encontrados em anúncios brutos da China.
     * @var array<int, string>
     */
    private array $spamKeywords = [
        '2024', '2025', '2026', 'novo', 'nova', 'novos', 'novas',
        'quente', 'venda', 'promoção', 'barato', 'frete grátis',
        'alta qualidade', 'top', 'luxo', 'moda', 'marca', 'original',
        'atacado', 'china', 'direto da fábrica', 'melhor preço', 'aliexpress',
        'hot sale', 'new', 'luxury', 'free shipping', 'drop shipping', 'dropshipping', 'dropship',
    ];

    public function polishTitle(string $rawTitle, ?string $category = null): string
    {
        $clean = trim($rawTitle);

        // 1. Remove termos de spam comuns
        foreach ($this->spamKeywords as $spam) {
            $pattern = '/\b' . preg_quote($spam, '/') . '\b/iu';
            $clean = (string)preg_replace($pattern, '', $clean);
        }

        // 2. Remove caracteres estranhos repetidos (ex: |||, ***, ---, [ ])
        $clean = (string)preg_replace('/[\[\]\|\*\#\_]+/', ' ', $clean);
        $clean = (string)preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean, " -.,:;\t\n\r\0\x0B");

        // 3. Limita o tamanho do título para 65-80 caracteres (ideal para SEO e e-commerce)
        $words = explode(' ', $clean);
        if (count($words) > 10) {
            $words = array_slice($words, 0, 10);
            $clean = implode(' ', $words);
        }

        // 4. Formata com inicial maiúscula elegante
        $clean = mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');

        if ($clean === '' && $category !== null) {
            return "Produto Premium - {$category}";
        }

        return $clean !== '' ? $clean : 'Produto Importado Exclusivo';
    }

    public function generateSalesCopy(string $rawDescription, array $attributes = []): string
    {
        $cleanText = strip_tags($rawDescription);
        $cleanText = (string)preg_replace('/\s+/', ' ', $cleanText);
        $summary = mb_substr(trim($cleanText), 0, 320);

        $specsHtml = '';
        if (!empty($attributes)) {
            $specsHtml .= '<div style="margin: 20px 0; padding: 16px; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">';
            $specsHtml .= '<h4 style="margin-top:0; color:#45f3ff; font-size:14px; text-transform:uppercase;">📐 Especificações Técnicas</h4>';
            $specsHtml .= '<ul style="margin:0; padding-left:20px; font-size:13px; color:#cbd5e1; line-height:1.7;">';
            foreach ($attributes as $k => $v) {
                if (is_scalar($v) && trim((string)$v) !== '') {
                    $specsHtml .= '<li><strong>' . htmlspecialchars((string)$k) . ':</strong> ' . htmlspecialchars((string)$v) . '</li>';
                }
            }
            $specsHtml .= '</ul></div>';
        }

        return <<<HTML
<div class="domain-drop-product-description" style="font-size:14px; line-height:1.7; color:var(--text-main, #cbd5e1);">
    <p style="font-size:15px; font-weight:500; color:#fff; margin-bottom:16px;">
        {$summary}...
    </p>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin:20px 0;">
        <div style="background:rgba(0,210,132,0.08); border:1px solid rgba(0,210,132,0.3); border-radius:6px; padding:12px;">
            <strong style="color:#00d284; display:block; margin-bottom:4px;">✨ Qualidade Comprovada</strong>
            <span style="font-size:12px; color:#8c8f94;">Item selecionado entre os mais bem avaliados do catálogo internacional.</span>
        </div>
        <div style="background:rgba(69,243,255,0.08); border:1px solid rgba(69,243,255,0.3); border-radius:6px; padding:12px;">
            <strong style="color:#45f3ff; display:block; margin-bottom:4px;">🚚 Envio com Rastreio</strong>
            <span style="font-size:12px; color:#8c8f94;">Código de rastreamento enviado automaticamente no seu WhatsApp e E-mail.</span>
        </div>
    </div>

    {$specsHtml}
</div>
HTML;
    }
}
