<?php
/**
 * Template da página Institucional "Sobre a RachidD.com" (Tema RachidD Store)
 */
$baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
?>
<section class="rachidd-about-page">
    <style>
        .rachidd-about-page {
            max-width: 1120px;
            margin: 0 auto;
            padding: 36px 20px 64px;
            color: #e2e8f0;
        }
        .rachidd-about-hero {
            background: linear-gradient(135deg, rgba(15, 20, 28, 0.98) 0%, rgba(22, 29, 41, 0.94) 100%);
            border: 1px solid rgba(212, 175, 55, 0.28);
            border-radius: 18px;
            padding: 38px 32px;
            margin-bottom: 28px;
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
        }
        .rachidd-about-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(212, 175, 55, 0.14);
            border: 1px solid rgba(240, 208, 96, 0.35);
            color: #F0D060;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .rachidd-about-hero h1 {
            margin: 0 0 14px;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            line-height: 1.2;
            color: #ffffff;
        }
        .rachidd-about-hero h1 span {
            color: #F0D060;
        }
        .rachidd-about-lead {
            margin: 0;
            max-width: 780px;
            font-size: 1.05rem;
            line-height: 1.75;
            color: #cbd5e1;
        }
        .rachidd-about-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 28px;
        }
        .rachidd-about-stat {
            background: rgba(11, 15, 23, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px 18px;
        }
        .rachidd-about-stat strong {
            display: block;
            color: #F0D060;
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .rachidd-about-stat span {
            display: block;
            color: #94a3b8;
            font-size: 0.88rem;
            line-height: 1.45;
        }
        .rachidd-about-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .rachidd-about-card {
            background: #0f141c;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 24px;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }
        .rachidd-about-card:hover {
            border-color: rgba(212, 175, 55, 0.45);
            transform: translateY(-2px);
        }
        .rachidd-about-card h2 {
            margin: 0 0 10px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #F0D060;
        }
        .rachidd-about-card p {
            margin: 0;
            color: #cbd5e1;
            font-size: 0.94rem;
            line-height: 1.68;
        }
        .rachidd-about-story {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .rachidd-about-panel {
            background: #0f141c;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 26px;
        }
        .rachidd-about-panel h2 {
            margin: 0 0 12px;
            font-size: 1.22rem;
            color: #ffffff;
        }
        .rachidd-about-panel p {
            margin: 0 0 14px;
            color: #cbd5e1;
            font-size: 0.95rem;
            line-height: 1.72;
        }
        .rachidd-about-panel p:last-child {
            margin-bottom: 0;
        }
        .rachidd-about-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .rachidd-about-list li {
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .rachidd-about-list strong {
            color: #F0D060;
        }
        .rachidd-about-cta {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.14) 0%, rgba(15, 20, 28, 0.96) 100%);
            border: 1px solid rgba(212, 175, 55, 0.32);
            border-radius: 16px;
            padding: 28px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .rachidd-about-cta h3 {
            margin: 0 0 6px;
            font-size: 1.2rem;
            color: #ffffff;
        }
        .rachidd-about-cta p {
            margin: 0;
            color: #cbd5e1;
            font-size: 0.94rem;
        }
        .rachidd-about-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .rachidd-btn-gold {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 20px;
            border-radius: 8px;
            background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
            color: #0b0f17 !important;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 800;
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.3);
        }
        .rachidd-btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(212, 175, 55, 0.4);
            color: #F0D060 !important;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
        }
        @media (max-width: 900px) {
            .rachidd-about-stats,
            .rachidd-about-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .rachidd-about-story {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 640px) {
            .rachidd-about-page {
                padding: 24px 14px 48px;
            }
            .rachidd-about-hero {
                padding: 26px 20px;
            }
            .rachidd-about-stats,
            .rachidd-about-grid {
                grid-template-columns: 1fr;
            }
            .rachidd-about-actions {
                width: 100%;
            }
            .rachidd-btn-gold,
            .rachidd-btn-outline {
                width: 100%;
            }
        }
    </style>

    <div class="rachidd-about-hero">
        <span class="rachidd-about-badge">RachidD.com • Curadoria Inteligente</span>
        <h1>Sobre a <span>RachidD.com</span></h1>
        <p class="rachidd-about-lead">
            A <strong>RachidD.com</strong> nasceu com um propósito claro: filtrar o excesso de ofertas da internet e reunir em um único lugar produtos úteis, tecnológicos e com ótimo custo-benefício, selecionados por reputação real de fornecedores, avaliações verificadas e transparência total do pedido até a entrega.
        </p>

        <div class="rachidd-about-stats">
            <div class="rachidd-about-stat">
                <strong>Curadoria Verificada</strong>
                <span>Seleção criteriosa de eletrônicos, smartwatches, áudio, gadgets e casa inteligente.</span>
            </div>
            <div class="rachidd-about-stat">
                <strong>Compra Protegida</strong>
                <span>Pagamentos criptografados via Pix, Cartão em até 12x, Mercado Pago e PayPal.</span>
            </div>
            <div class="rachidd-about-stat">
                <strong>Rastreamento Real</strong>
                <span>Acompanhamento de código postal passo a passo direto em nossa central de rastreio.</span>
            </div>
        </div>
    </div>

    <div class="rachidd-about-grid">
        <article class="rachidd-about-card">
            <h2>Garimpagem de Qualidade</h2>
            <p>
                Em vez de listar milhares de itens aleatórios, analisamos avaliações reais, notas acima de 4.7 estrelas, histórico de despacho e confiabilidade do fabricante antes de disponibilizar qualquer produto no catálogo.
            </p>
        </article>
        <article class="rachidd-about-card">
            <h2>Logística Nacional e Global</h2>
            <p>
                Trabalhamos com centros de distribuição parceiros no Brasil e no exterior (como fornecedores certificados AliExpress Brasil &amp; Global) para equilibrar preço competitivo, envio ágil e código de rastreio ativo.
            </p>
        </article>
        <article class="rachidd-about-card">
            <h2>Suporte Humanizado</h2>
            <p>
                Comprou e ficou com dúvida sobre prazo, rastreio ou troca? Nossa equipe acompanha o pedido de ponta a ponta pelo e-mail <strong>contato@rachidd.com</strong> com suporte em português.
            </p>
        </article>
    </div>

    <div class="rachidd-about-story">
        <div class="rachidd-about-panel">
            <h2>Como funciona comprar na RachidD.com</h2>
            <p>
                Nosso modelo de curadoria e logística direta elimina intermediários desnecessários. Assim que o pagamento é confirmado, o pedido segue para separação junto ao parceiro logístico homologado e você recebe o código de rastreamento para acompanhar cada movimentação até a porta da sua casa.
            </p>
            <p>
                Mantemos regras claras de garantia contra extravio, defeito de fabricação e avaria no transporte, com direito a reenvio ou reembolso integral conforme nossa política de envio e devoluções.
            </p>
        </div>
        <div class="rachidd-about-panel">
            <h2>Nossos Compromissos</h2>
            <ul class="rachidd-about-list">
                <li><strong>Preço Transparente:</strong> valores exibidos claramente em Reais (BRL) antes da finalização.</li>
                <li><strong>Dados Seguros:</strong> checkout protegido com criptografia SSL e meios de pagamento reconhecidos.</li>
                <li><strong>Garantia de Entrega:</strong> suporte ativo até a confirmação de recebimento do seu pacote.</li>
            </ul>
        </div>
    </div>

    <div class="rachidd-about-cta">
        <div>
            <h3>Pronto para explorar nossas ofertas selecionadas?</h3>
            <p>Acesse o catálogo completo da loja ou consulte o status do seu pedido a qualquer momento.</p>
        </div>
        <div class="rachidd-about-actions">
            <a href="<?= htmlspecialchars($baseUrl . '/loja', ENT_QUOTES, 'UTF-8') ?>" class="rachidd-btn-gold">Ver Loja</a>
            <a href="<?= htmlspecialchars($baseUrl . '/rastrear', ENT_QUOTES, 'UTF-8') ?>" class="rachidd-btn-outline">Rastrear Pedido</a>
            <a href="<?= htmlspecialchars($baseUrl . '/politica-de-envio', ENT_QUOTES, 'UTF-8') ?>" class="rachidd-btn-outline">Política de Envio</a>
        </div>
    </div>
</section>
