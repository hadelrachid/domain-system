<?php
/**
 * Template da página Institucional "Sobre a RachidD.com" (Tema RachidD Store)
 */
$baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
?>
<section class="rachidd-about-page">
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
