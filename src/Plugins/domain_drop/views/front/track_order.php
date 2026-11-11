<?php
/**
 * View Pública de Rastreamento de Pedidos (/rastrear)
 *
 * @var string $baseUrl
 * @var string $searchCode
 * @var array<int, array<string, mixed>> $matchedOrders
 * @var string $trackingCodeToInspect
 * @var array<int, array<string, mixed>> $sdkEvents
 * @var string $sdkOfficialUrl
 */
$baseUrl = $baseUrl ?? '';
$searchCode = $searchCode ?? '';
$matchedOrders = $matchedOrders ?? [];
$trackingCodeToInspect = $trackingCodeToInspect ?? '';
$sdkEvents = $sdkEvents ?? [];
$sdkOfficialUrl = $sdkOfficialUrl ?? '';

$statusMap = [
    'pending'    => ['label' => 'Aguardando Pagamento / Processamento', 'step' => 1, 'color' => '#fbbf24'],
    'paid'       => ['label' => 'Pagamento Confirmado • Em Curadoria', 'step' => 2, 'color' => '#38bdf8'],
    'processing' => ['label' => 'Em Separação no Fornecedor AliExpress', 'step' => 3, 'color' => '#60a5fa'],
    'shipped'    => ['label' => 'Enviado • Código de Rastreio Gerado', 'step' => 4, 'color' => '#45f3ff'],
    'delivered'  => ['label' => 'Entregue ao Destinatário', 'step' => 5, 'color' => '#00d284'],
    'cancelled'  => ['label' => 'Pedido Cancelado / Estornado', 'step' => 0, 'color' => '#f87171'],
];
?>
<section class="ds-track-page">
    <style>
        .ds-track-page {
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 16px 64px;
            color: #e2e8f0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .ds-track-page * { box-sizing: border-box; }
        .ds-track-hero {
            background: linear-gradient(135deg, rgba(212,175,55,0.12) 0%, rgba(15,20,28,0.95) 100%);
            border: 1px solid rgba(212,175,55,0.3);
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            margin-bottom: 28px;
        }
        .ds-track-hero h1 { font-size: clamp(1.5rem, 3vw, 2.1rem); font-weight: 800; color: #fff; margin: 0 0 8px 0; }
        .ds-track-hero p { color: #94a3b8; font-size: 0.95rem; max-width: 680px; margin: 0 auto 22px; line-height: 1.55; }
        .ds-track-form {
            display: flex;
            gap: 10px;
            max-width: 620px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        .ds-track-input {
            flex: 1;
            min-width: 220px;
            height: 46px;
            padding: 0 14px;
            border-radius: 8px;
            border: 1px solid rgba(69,243,255,0.35);
            background: #06090e;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
        }
        .ds-track-input:focus { border-color: #F0D060; }
        .ds-track-btn {
            height: 46px;
            padding: 0 22px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
            color: #0b0f17;
            font-weight: 800;
            font-size: 0.92rem;
            cursor: pointer;
            white-space: nowrap;
        }
        .ds-track-card {
            background: #0f141c;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .ds-track-steps {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin: 18px 0;
        }
        @media (max-width: 680px) { .ds-track-steps { grid-template-columns: 1fr; } }
        .ds-track-step {
            padding: 10px 8px;
            border-radius: 8px;
            text-align: center;
            font-size: 0.76rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.02);
            color: #64748b;
        }
        .ds-track-step.done {
            border-color: rgba(0,210,132,0.4);
            background: rgba(0,210,132,0.09);
            color: #6ee7b7;
        }
        .ds-carrier-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .ds-carrier-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid rgba(69,243,255,0.35);
            background: rgba(69,243,255,0.08);
            color: #45f3ff;
        }
        .ds-carrier-btn-gold {
            border-color: rgba(212,175,55,0.45);
            background: rgba(212,175,55,0.12);
            color: #F0D060;
        }
    </style>

    <div class="ds-track-hero">
        <div style="display:inline-block; padding:4px 12px; border-radius:20px; background:rgba(69,243,255,0.12); border:1px solid rgba(69,243,255,0.35); color:#45f3ff; font-size:0.75rem; font-weight:700; margin-bottom:10px;">
            🛰️ Rastreamento Integrado RachidD.com (Correios + AliExpress SDK)
        </div>
        <h1>📦 Rastrear Meu Pedido</h1>
        <p>
            Informe o <strong>código do pedido</strong> (ex: <code>ORD-...</code>), seu <strong>código de rastreio dos Correios/Cainiao</strong> ou o <strong>e-mail</strong> usado na compra para acompanhar o despacho pela nossa curadoria no Brasil.
        </p>

        <form method="GET" action="<?= $baseUrl ?>/rastrear" class="ds-track-form">
            <input type="text" name="codigo" value="<?= htmlspecialchars($searchCode, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: ORD-2026..., NM123456789BR ou seu@email.com" class="ds-track-input" required>
            <button type="submit" class="ds-track-btn">🔍 Consultar Rastreio</button>
        </form>
    </div>

    <?php if ($searchCode !== ''): ?>
        <?php if (!empty($matchedOrders)): ?>
            <?php foreach ($matchedOrders as $order): ?>
                <?php
                $st = strtolower((string)($order['status'] ?? 'pending'));
                $meta = $statusMap[$st] ?? ['label' => strtoupper($st), 'step' => 1, 'color' => '#94a3b8'];
                $step = (int)$meta['step'];
                $tCode = trim((string)($order['tracking_code'] ?? ''));
                ?>
                <div class="ds-track-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:14px; margin-bottom:14px;">
                        <div>
                            <div style="font-size:0.78rem; color:#94a3b8;">Referência do Pedido</div>
                            <strong style="font-size:1.1rem; color:#F0D060; font-family:monospace;"><?= htmlspecialchars((string)($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div style="text-align:right;">
                            <span style="display:inline-block; padding:5px 12px; border-radius:20px; font-size:0.8rem; font-weight:700; color:<?= $meta['color'] ?>; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12);">
                                ● <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; font-size:0.86rem;">
                        <div><span style="color:#94a3b8;">Cliente:</span> <strong style="color:#fff;"><?= htmlspecialchars((string)($order['customer_name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <?php if (!empty($order['customer_email_masked'])): ?>
                            <div><span style="color:#94a3b8;">E-mail:</span> <strong style="color:#cbd5e1;"><?= htmlspecialchars((string)$order['customer_email_masked'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <?php endif; ?>
                        <div><span style="color:#94a3b8;">Data:</span> <strong style="color:#cbd5e1;"><?= htmlspecialchars((string)($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div>
                            <span style="color:#94a3b8;">Código Transportadora:</span>
                            <?php if ($tCode !== ''): ?>
                                <strong style="color:#45f3ff; font-family:monospace;"><?= htmlspecialchars($tCode, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php else: ?>
                                <span style="color:#fbbf24;">Aguardando postagem (24h–48h úteis)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ds-track-steps">
                        <div class="ds-track-step <?= $step >= 1 ? 'done' : '' ?>">1. Pedido Registrado</div>
                        <div class="ds-track-step <?= $step >= 2 ? 'done' : '' ?>">2. Pago &amp; Validado</div>
                        <div class="ds-track-step <?= $step >= 3 ? 'done' : '' ?>">3. Separação Fornecedor</div>
                        <div class="ds-track-step <?= $step >= 4 ? 'done' : '' ?>">4. Em Transporte</div>
                        <div class="ds-track-step <?= $step >= 5 ? 'done' : '' ?>">5. Entregue</div>
                    </div>

                    <?php if ($tCode !== ''): ?>
                        <div class="ds-carrier-links">
                            <a href="https://rastreamento.correios.com.br/app/index.php?objetos=<?= urlencode($tCode) ?>" target="_blank" rel="noopener noreferrer" class="ds-carrier-btn ds-carrier-btn-gold">
                                📮 Rastrear nos Correios (<?= htmlspecialchars($tCode, ENT_QUOTES, 'UTF-8') ?>) ↗
                            </a>
                            <a href="https://global.cainiao.com/detail.htm?mailNoList=<?= urlencode($tCode) ?>" target="_blank" rel="noopener noreferrer" class="ds-carrier-btn">
                                🌐 Rastrear via Cainiao / AliExpress ↗
                            </a>
                            <?php if ($sdkOfficialUrl !== ''): ?>
                                <a href="<?= htmlspecialchars($sdkOfficialUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="ds-carrier-btn">
                                    🔗 Portal Transportadora ↗
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($sdkEvents)): ?>
                <div class="ds-track-card" style="border-color:rgba(69,243,255,0.3);">
                    <h3 style="color:#45f3ff; font-size:1rem; margin:0 0 12px 0;">🛰️ Movimentações Recebidas do SDK AliExpress</h3>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($sdkEvents as $ev): ?>
                            <div style="padding:10px 12px; border-radius:8px; background:rgba(255,255,255,0.03); border-left:3px solid #45f3ff; font-size:0.85rem;">
                                <div style="color:#94a3b8; font-size:0.76rem;"><?= htmlspecialchars((string)($ev['event_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="color:#fff; font-weight:600;"><?= htmlspecialchars((string)($ev['event_desc'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($ev['address'])): ?>
                                    <div style="color:#cbd5e1; font-size:0.78rem;"><?= htmlspecialchars((string)$ev['address'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($trackingCodeToInspect !== ''): ?>
            <div class="ds-track-card" style="border-color:rgba(69,243,255,0.35);">
                <h2 style="color:#fff; font-size:1.1rem; margin:0 0 8px 0;">🔎 Código de Objeto Identificado: <span style="color:#45f3ff; font-family:monospace;"><?= htmlspecialchars($trackingCodeToInspect, ENT_QUOTES, 'UTF-8') ?></span></h2>
                <p style="color:#94a3b8; font-size:0.9rem; line-height:1.55; margin:0;">
                    Não encontramos uma referência interna com esse código, mas preparamos os atalhos diretos para consulta imediata nos sistemas dos <strong>Correios</strong> e da rede logística <strong>Cainiao / AliExpress</strong>:
                </p>
                <div class="ds-carrier-links">
                    <a href="https://rastreamento.correios.com.br/app/index.php?objetos=<?= urlencode($trackingCodeToInspect) ?>" target="_blank" rel="noopener noreferrer" class="ds-carrier-btn ds-carrier-btn-gold">
                        📮 Consultar Objeto nos Correios ↗
                    </a>
                    <a href="https://global.cainiao.com/detail.htm?mailNoList=<?= urlencode($trackingCodeToInspect) ?>" target="_blank" rel="noopener noreferrer" class="ds-carrier-btn">
                        🌐 Consultar na Rede Cainiao / AliExpress ↗
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="ds-track-card" style="border-color:rgba(251,191,36,0.35);">
                <h3 style="color:#fbbf24; font-size:1.05rem; margin:0 0 8px 0;">⚠️ Pedido ou Código Não Localizado</h3>
                <p style="color:#cbd5e1; font-size:0.9rem; line-height:1.6; margin:0;">
                    Não encontramos nenhum pedido para <strong>"<?= htmlspecialchars($searchCode, ENT_QUOTES, 'UTF-8') ?>"</strong>. Se você acabou de finalizar o pagamento, o código de rastreio dos Correios ou transportadora é emitido em até <strong>24h a 48h úteis</strong> após a confirmação do fornecedor e enviado para seu e-mail e WhatsApp. Em caso de dúvidas, escreva para <strong style="color:#F0D060;">contato@rachidd.com</strong>.
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="ds-track-card">
        <h3 style="color:#fff; font-size:1rem; margin:0 0 10px 0;">ℹ️ Como funciona o rastreamento na RachidD.com?</h3>
        <ul style="margin:0; padding-left:18px; color:#94a3b8; font-size:0.88rem; line-height:1.7;">
            <li><strong>Postagem pelo Fornecedor:</strong> após o pagamento aprovado, nossa curadoria aciona o vendedor verificado no AliExpress (prioridade estoque no Brasil), que gera a etiqueta em até 24h–48h úteis.</li>
            <li><strong>Integração Oficial:</strong> o sistema consulta eventos via API logística do AliExpress (<code>aliexpress.logistics.ds.trackinginfo.query</code> quando conectada) e disponibiliza consulta direta aos <strong>Correios</strong> e <strong>Cainiao</strong>.</li>
            <li><strong>Garantia de Entrega:</strong> se houver qualquer atraso fora do prazo previsto, nossa equipe acompanha junto ao vendedor ou garante o reembolso integral. Veja todas as regras em <a href="<?= $baseUrl ?>/politica-de-envio" style="color:#F0D060;">Política de Envio &amp; Prazos</a>.</li>
        </ul>
    </div>
</section>
<script>document.title = 'Rastrear Meu Pedido — RachidD.com';</script>
