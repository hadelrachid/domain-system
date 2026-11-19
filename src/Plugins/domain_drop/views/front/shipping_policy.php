<?php
/**
 * View Pública: Política de Envio & Prazos — Diretrizes AliExpress Brasil & Global (/politica-de-envio)
 *
 * @var string $baseUrl
 */
$baseUrl = $baseUrl ?? '';
?>
<section class="ds-policy-page">
    <style>
        .ds-policy-page {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 16px 64px;
            color: #e2e8f0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .ds-policy-page * { box-sizing: border-box; }
        .ds-policy-hero {
            background: linear-gradient(135deg, rgba(212,175,55,0.12) 0%, rgba(15,20,28,0.95) 100%);
            border: 1px solid rgba(212,175,55,0.3);
            border-radius: 16px;
            padding: 32px 24px;
            margin-bottom: 26px;
            text-align: center;
        }
        .ds-policy-hero h1 { font-size: clamp(1.5rem, 3vw, 2.15rem); font-weight: 800; color: #fff; margin: 0 0 10px 0; }
        .ds-policy-hero p { color: #94a3b8; font-size: 0.96rem; max-width: 720px; margin: 0 auto; line-height: 1.6; }
        .ds-policy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ds-policy-card {
            background: #0f141c;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 12px;
            padding: 22px;
        }
        .ds-policy-card h2 {
            color: #F0D060;
            font-size: 1.05rem;
            font-weight: 800;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ds-policy-card p, .ds-policy-card li {
            color: #cbd5e1;
            font-size: 0.9rem;
            line-height: 1.65;
        }
        .ds-policy-card ul { margin: 8px 0 0 0; padding-left: 18px; }
        .ds-policy-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .ds-shipping-table-wrap {
            overflow-x: auto;
            margin: 16px 0;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.25);
        }
        .ds-shipping-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }
        .ds-shipping-table th {
            background: rgba(240, 208, 96, 0.12);
            color: #F0D060;
            padding: 12px 14px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 0.86rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ds-shipping-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            color: #cbd5e1;
            font-size: 0.88rem;
        }
        .ds-shipping-table tr:last-child td {
            border-bottom: none;
        }
        .ds-shipping-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
    </style>

    <div class="ds-policy-hero">
        <span class="ds-policy-badge" style="background:rgba(0,210,132,0.12); border:1px solid rgba(0,210,132,0.35); color:#6ee7b7; display: inline-flex; align-items: center; gap: 6px;">
            <?= \DomainSystem\Plugins\domain_drop\Helpers\FlagHelper::br(16, 11) ?> Diretrizes AliExpress Brasil &amp; Logística Global
        </span>
        <h1>🚚 Política de Envio &amp; Prazos</h1>
        <p>
            A <strong>RachidD.com</strong> atua como serviço de <strong>curadoria técnica</strong> para localizar produtos físicos minerados no AliExpress (com prioridade atual para estoques já no Brasil) por nota, aprovações e termômetro de demanda. Todas as entregas seguem as diretrizes oficiais dos fornecedores certificados do AliExpress no Brasil e globalmente.
        </p>
    </div>

    <div class="ds-policy-grid">
        <div class="ds-policy-card" style="border-color: rgba(0,210,132,0.3);">
            <span class="ds-policy-badge" style="background:rgba(0,210,132,0.14); color:#00d284;">PRIORIDADE ATUAL</span>
            <h2 style="display: flex; align-items: center; gap: 8px;"><?= \DomainSystem\Plugins\domain_drop\Helpers\FlagHelper::br(22, 15) ?> Estoque Nacional no Brasil</h2>
            <p>Produtos já internalizados por vendedores parceiros em centros de distribuição dentro do território brasileiro.</p>
            <ul>
                <li><strong>Prazo médio de entrega:</strong> <strong>2 a 7 dias úteis</strong> após a postagem (podendo variar em regiões Norte/Nordeste ou áreas remotas).</li>
                <li><strong>Alfândega:</strong> itens já nacionalizados; não passam por nova inspeção aduaneira de importação.</li>
                <li><strong>Tributos:</strong> impostos internos já embutidos pelo vendedor local, sem cobrança surpresa de desembaraço na chegada.</li>
            </ul>
        </div>

        <div class="ds-policy-card" style="border-color: rgba(69,243,255,0.28);">
            <span class="ds-policy-badge" style="background:rgba(69,243,255,0.12); color:#45f3ff;">EXPANSÃO GLOBAL</span>
            <h2>🌐 Envio Internacional &amp; Remessa Conforme</h2>
            <p>Quando o catálogo incluir ofertas globais (incluindo modalidade <em>AliExpress Choice</em>), aplicam-se as regras do Programa <strong>Remessa Conforme</strong> da Receita Federal no Brasil:</p>
            <ul>
                <li><strong>Prazo médio internacional:</strong> <strong>12 a 25 dias úteis</strong> (sujeito a sazonalidades ou greves/fiscalização local).</li>
                <li><strong>Compras até US$ 50:</strong> seguem a alíquota vigente do Remessa Conforme (imposto federal conforme regra aplicável + ICMS estadual de 17%, discriminados previamente).</li>
                <li><strong>Compras acima de US$ 50:</strong> recolhimento antecipado na origem para desembaraço prioritário na Receita Federal, evitando retenções prolongadas.</li>
            </ul>
        </div>
    </div>

    <div class="ds-policy-card" style="margin-bottom: 24px; border-color: rgba(69,243,255,0.3);">
        <span class="ds-policy-badge" style="background:rgba(69,243,255,0.12); color:#45f3ff;">TRANSPARÊNCIA LOGÍSTICA</span>
        <h2>⚡ Modalidades de Frete &amp; Tabela de Cálculo por Peso (Sedex)</h2>
        <p>
            No momento de finalizar a compra, oferecemos duas opções oficiais de envio para garantir total flexibilidade, rapidez e economia:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin: 16px 0 20px;">
            <div style="background: rgba(0,210,132,0.06); border: 1px solid rgba(0,210,132,0.3); border-radius: 8px; padding: 14px;">
                <div style="font-weight: 700; color: #00d284; font-size: 0.95rem; margin-bottom: 6px;">
                    📦 Frete Padrão com Rastreamento — GRÁTIS
                </div>
                <div style="font-size: 0.85rem; color: #cbd5e1; line-height: 1.5;">
                    Disponível para todo o Brasil em 100% dos produtos minerados, com código oficial rastreável nos Correios e transportadoras.
                </div>
            </div>
            <div style="background: rgba(69,243,255,0.06); border: 1px solid rgba(69,243,255,0.3); border-radius: 8px; padding: 14px;">
                <div style="font-weight: 700; color: #45f3ff; font-size: 0.95rem; margin-bottom: 6px;">
                    ⚡ Envio Expresso Prioritário (Sedex)
                </div>
                <div style="font-size: 0.85rem; color: #cbd5e1; line-height: 1.5;">
                    Despacho no mesmo dia útil com fila prioritária de triagem, prioridade aduaneira e seguro integral de transporte.
                </div>
            </div>
        </div>

        <h3 style="font-size: 0.95rem; color: #fff; margin: 18px 0 8px 0; font-weight: 700;">
            📐 Como o Frete Expresso é calculado com base no Vendedor e nos Correios:
        </h3>
        <p style="font-size: 0.88rem; margin-bottom: 12px;">
            O valor do frete expresso é determinado de forma <strong>dinâmica e justa</strong> através do <strong>peso real do produto</strong> informado nas especificações técnicas e descrições do vendedor do AliExpress, multiplicado pela quantidade de unidades da remessa:
        </p>

        <div class="ds-shipping-table-wrap">
            <table class="ds-shipping-table">
                <thead>
                    <tr>
                        <th style="min-width: 170px;">Faixa de Peso Total</th>
                        <th style="min-width: 150px; text-align: center;">Valor do Frete Expresso</th>
                        <th style="min-width: 260px;">Exemplos de Produtos / Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong style="color: #fff;">Até 300g</strong></td>
                        <td style="text-align: center;"><span style="color: #45f3ff; font-weight: 800; font-size: 0.95rem;">R$ 19,90</span></td>
                        <td>Lingeries (1 un), fones de ouvido, bijuterias, cases, pulseiras</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #fff;">301g até 1.000g (1 kg)</strong></td>
                        <td style="text-align: center;"><span style="color: #45f3ff; font-weight: 800; font-size: 0.95rem;">R$ 29,90</span></td>
                        <td>Moletons, calçados leves, ou 2 unidades de lingerie (400g)</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #fff;">1.001g até 2.000g (2 kg)</strong></td>
                        <td style="text-align: center;"><span style="color: #45f3ff; font-weight: 800; font-size: 0.95rem;">R$ 39,90</span></td>
                        <td>Mini projetores, drones com controle, múltiplos itens no pedido</td>
                    </tr>
                    <tr>
                        <td><strong style="color: #fff;">Acima de 2.000g (2 kg)</strong></td>
                        <td style="text-align: center;"><span style="color: #45f3ff; font-weight: 800; font-size: 0.95rem;">R$ 39,90 + R$ 10,00/kg extra</span></td>
                        <td>Maletas de ferramentas, equipamentos pesados (ex: 3,5 kg = R$ 59,90)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="font-size: 0.82rem; color: #94a3b8; margin: 10px 0 0 0; line-height: 1.5;">
            💡 <em>Nota de Transparência:</em> Se nas especificações do vendedor do produto não constar peso explícito, aplica-se o padrão seguro de produto leve (até 300g), mantendo a tarifa inicial base de <strong>R$ 19,90</strong>. Tanto a página do produto quanto o checkout exibem o peso estimado e recalculam o valor em tempo real se você alterar a quantidade de itens.
        </p>
    </div>

    <div class="ds-policy-card" style="margin-bottom: 18px;">
        <h2>📦 Processamento, Fornecedores Distintos e Rastreamento</h2>
        <ul>
            <li><strong>Prazo de postagem:</strong> após a aprovação do pagamento (Pix ou Cartão), o pedido é encaminhado ao vendedor curado, que realiza o despacho em <strong>24h a 48h úteis</strong>.</li>
            <li><strong>Fornecedores independentes:</strong> como mineramos os melhores vendedores do AliExpress, cada loja é identificada no checkout pelo seu ID de fornecedor. Produtos de vendedores diferentes são enviados em <strong>pacotes independentes</strong> com códigos de rastreio próprios.</li>
            <li><strong>Acompanhamento em tempo real:</strong> você recebe o código dos Correios/transportadora por e-mail e WhatsApp e pode consultar a qualquer momento em <a href="<?= $baseUrl ?>/rastrear" style="color:#45f3ff;">rachidd.com/rastrear</a>.</li>
        </ul>
    </div>

    <div class="ds-policy-card" style="border-color: rgba(212,175,55,0.3);">
        <h2>🛡️ Garantia de Entrega, Atrasos e Política de Reembolso</h2>
        <ul>
            <li><strong>Transparência da curadoria:</strong> o valor pago remunera a nossa busca técnica especializada + acompanhamento completo da entrega até sua residência.</li>
            <li><strong>Proteção contra extravio ou não recebimento:</strong> caso a transportadora confirme extravio, ou o pedido ultrapasse o prazo limite de proteção estipulado nas diretrizes do AliExpress (em regra até 50 dias corridos para envios globais ou atraso crítico no Brasil sem movimentação), garantimos mediação imediata ou <strong>reembolso integral</strong>.</li>
            <li><strong>Avarias ou divergências:</strong> ao receber, registre fotos/vídeo da embalagem e entre em contato em até 7 dias corridos (CDC Art. 49 para direito de arrependimento em compras online) pelo canal <strong style="color:#F0D060;">contato@rachidd.com</strong>.</li>
        </ul>
    </div>

    <div style="text-align:center; margin-top:26px; display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
        <a href="<?= $baseUrl ?>/rastrear" style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; border-radius:8px; background:rgba(69,243,255,0.12); border:1px solid rgba(69,243,255,0.4); color:#45f3ff; text-decoration:none; font-weight:700;">
            📦 Ir para Rastrear Pedido
        </a>
        <a href="<?= $baseUrl ?>/loja" style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border-radius:8px; background:linear-gradient(135deg,#F0D060,#D4AF37); color:#0b0f17; text-decoration:none; font-weight:800;">
            🛍️ Voltar à Loja RachidD.com
        </a>
    </div>
</section>
<script>document.title = 'Política de Envio & Prazos — RachidD.com';</script>
