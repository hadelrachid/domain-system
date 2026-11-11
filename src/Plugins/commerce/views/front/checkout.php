<?php

declare(strict_types=1);

/**
 * View de Checkout Transparente e Seguro (Commerce).
 */
$baseUrl = $baseUrl ?? '';
$course = $course ?? [];
$isPhysicalOrDropship = !empty($isPhysicalOrDropship);
$unitPrice = (float)($unitPrice ?? ($course['price'] ?? 0.0));
$quantity = $isPhysicalOrDropship ? max(1, (int)($quantity ?? 1)) : 1;
$total = (float)($total ?? round($unitPrice * $quantity, 2));
$formattedUnit = number_format($unitPrice, 2, ',', '.');
$formattedTotal = number_format($total, 2, ',', '.');
$sellerInfo = is_array($sellerInfo ?? null) ? $sellerInfo : null;
$csrfToken = htmlspecialchars((string)($csrfToken ?? ''), ENT_QUOTES, 'UTF-8');
$userName = htmlspecialchars((string)($userName ?? 'Aluno'), ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars((string)($userEmail ?? ''), ENT_QUOTES, 'UTF-8');
$userCpf = htmlspecialchars((string)($userCpf ?? ''), ENT_QUOTES, 'UTF-8');
$courseTitle = htmlspecialchars((string)($course['title'] ?? 'Curso Academy'), ENT_QUOTES, 'UTF-8');
$courseId = (int)($course['id'] ?? 0);
$courseImage = $courseImage ?? '';
$coursePlatform = (string)($coursePlatform ?? ($course['external_platform'] ?? ''));
$gateways = $gateways ?? [];
$hasMercadoPago = false;
$hasStripe = false;
$hasPayPal = false;
foreach ($gateways as $g) {
    if (is_object($g) && method_exists($g, 'getId')) {
        if ($g->getId() === 'mercadopago') $hasMercadoPago = true;
        if ($g->getId() === 'stripe') $hasStripe = true;
        if ($g->getId() === 'paypal') $hasPayPal = true;
    }
}
$fallbackThumb = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360"><rect width="100%" height="100%" fill="%23141a23"/><text x="50%" y="50%" fill="%2345f3ff" font-family="sans-serif" font-size="28" font-weight="bold" text-anchor="middle" dominant-baseline="middle">🎓 ' . rawurlencode((string)($course['title'] ?? 'Academy')) . '</text></svg>';
$effectiveThumb = !empty($courseImage) ? $courseImage : $fallbackThumb;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro | <?= $courseTitle ?></title>
    <style>
        :root {
            --bg-body: #06090e;
            --bg-card: #0f141c;
            --border-color: rgba(69, 243, 255, 0.2);
            --accent-cyan: #45f3ff;
            --accent-green: #00d284;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); min-height: 100vh; padding: 30px 16px; }
        .ds-checkout-container { max-width: 860px; margin: 0 auto; }
        .ds-checkout-header { text-align: center; margin-bottom: 30px; }
        .ds-checkout-header h1 { font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 6px; }
        .ds-checkout-header p { color: var(--text-muted); font-size: 0.95rem; }
        .ds-checkout-grid { display: grid; grid-template-columns: 1fr 1.15fr; gap: 24px; align-items: start; }
        @media (max-width: 768px) { .ds-checkout-grid { grid-template-columns: 1fr; } }
        .ds-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .ds-card-title { font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 10px; }
        .ds-checkout-course-thumb { width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 6px 18px rgba(0,0,0,0.45); margin-bottom: 16px; background: #000; display: block; }
        .ds-order-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.94rem; }
        .ds-order-total { display: flex; justify-content: space-between; margin-top: 16px; padding-top: 14px; border-top: 1px dashed rgba(255,255,255,0.15); font-size: 1.25rem; font-weight: 800; color: var(--accent-green); }
        .ds-method-option { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.02); }
        .ds-method-option:hover { border-color: var(--accent-cyan); background: rgba(69,243,255,0.04); }
        .ds-method-option.active { border-color: var(--accent-green); background: rgba(0,210,132,0.06); }
        .ds-method-option input[type="radio"] { accent-color: var(--accent-green); transform: scale(1.15); }
        .ds-btn-pay { width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; background: linear-gradient(135deg, #00d284, #00b370); color: #000; font-weight: 800; font-size: 1.05rem; padding: 14px; border-radius: 8px; border: none; cursor: pointer; margin-top: 18px; box-shadow: 0 4px 18px rgba(0,210,132,0.35); transition: transform 0.2s; }
        .ds-btn-pay:hover { transform: translateY(-2px); }
        .ds-btn-pay:disabled { opacity: 0.6; cursor: not-allowed; }
        .ds-pix-box { text-align: center; padding: 20px; background: rgba(0,0,0,0.35); border-radius: 10px; border: 1px solid rgba(69,243,255,0.3); margin-top: 16px; display: none; }
        .ds-pix-code-area { width: 100%; background: #06090e; color: var(--accent-cyan); font-family: monospace; font-size: 12px; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); margin: 10px 0; word-break: break-all; resize: none; }
    </style>
</head>
<body>
    <div class="ds-checkout-container">
        <header class="ds-checkout-header">
            <h1>🔒 <?= $isPhysicalOrDropship ? 'Finalizar Pedido' : 'Finalizar Matrícula' ?></h1>
            <p><?= $isPhysicalOrDropship ? 'Ambiente seguro e criptografado. Envio internacional com rastreio até sua casa.' : 'Ambiente seguro e criptografado. Seus dados estão 100% protegidos.' ?></p>
        </header>

        <div class="ds-checkout-grid">
            <!-- Resumo do Pedido -->
            <div class="ds-card">
                <h2 class="ds-card-title">📋 Resumo da Compra</h2>

                <div id="checkout_item_summary">
                    <!-- Imagem Ilustrativa do Produto / Curso -->
                    <img src="<?= htmlspecialchars($effectiveThumb, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $courseTitle ?>" class="ds-checkout-course-thumb">

                    <div class="ds-order-item" style="align-items: flex-start; gap: 10px;">
                        <div style="flex: 1;">
                            <div style="color: #fff; font-weight: 700; font-size: 1.02rem; line-height: 1.35;"><?= $courseTitle ?></div>
                            <?php if ($isPhysicalOrDropship): ?>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                    Valor unitário: <strong style="color: #cbd5e1;">R$ <?= $formattedUnit ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span id="checkout_subtotal_val" style="color: var(--accent-green); font-weight: 800; font-size: 1.05rem; white-space: nowrap;">R$ <?= $formattedTotal ?></span>
                    </div>

                    <?php if ($isPhysicalOrDropship && $sellerInfo !== null): ?>
                        <!-- Identificação do Vendedor / Fornecedor Exclusivo -->
                        <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.28); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 0.8rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 4px;">
                                <span style="color: #fff; font-weight: 700;">🏪 <?= htmlspecialchars((string)$sellerInfo['store_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span style="font-family: monospace; font-size: 0.74rem; color: #93c5fd; background: rgba(255,255,255,0.08); padding: 2px 7px; border-radius: 4px;">
                                    ID: <?= htmlspecialchars((string)$sellerInfo['seller_id'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.74rem; line-height: 1.4;">
                                <?= htmlspecialchars((string)$sellerInfo['warehouse'], ENT_QUOTES, 'UTF-8') ?> • Enviado diretamente por este fornecedor (itens de outros vendedores seguem em pacotes independentes).
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($isPhysicalOrDropship): ?>
                        <!-- Controles de Quantidade (+ / - / Apagar) -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; flex-wrap: wrap;">
                            <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Quantidade:</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="display: inline-flex; align-items: center; background: #06090e; border: 1px solid rgba(69,243,255,0.3); border-radius: 6px; overflow: hidden;">
                                    <button type="button" id="btn_qty_minus" onclick="adjustCheckoutQty(-1)" aria-label="Diminuir quantidade" style="width: 32px; height: 32px; background: rgba(255,255,255,0.05); border: none; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer;">−</button>
                                    <span id="checkout_qty_display" style="min-width: 34px; text-align: center; color: var(--accent-cyan); font-family: monospace; font-weight: 800; font-size: 0.95rem;"><?= (int)$quantity ?></span>
                                    <button type="button" id="btn_qty_plus" onclick="adjustCheckoutQty(1)" aria-label="Aumentar quantidade" style="width: 32px; height: 32px; background: rgba(255,255,255,0.05); border: none; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer;">+</button>
                                </div>
                                <button type="button" onclick="removeCheckoutItem()" title="Apagar item deste pedido" style="background: rgba(239,68,68,0.14); border: 1px solid rgba(239,68,68,0.35); color: #fca5a5; padding: 6px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                    <span>🗑️</span><span>Apagar</span>
                                </button>
                            </div>
                        </div>

                        <div class="ds-order-item" style="color: var(--text-muted); font-size: 0.84rem;">
                            <span>Frete com Rastreamento Oficial</span>
                            <span style="color: var(--accent-green); font-weight: 700;">GRÁTIS (Promoção)</span>
                        </div>
                        <div class="ds-order-item" style="color: var(--text-muted); font-size: 0.84rem;">
                            <span>Curadoria &amp; Busca AliExpress Brasil</span>
                            <span>Incluso</span>
                        </div>
                        <div class="ds-order-item" style="color: var(--text-muted); font-size: 0.84rem;">
                            <span>Garantia de Entrega (7 Dias)</span>
                            <span>Incluso</span>
                        </div>
                        <div style="background: rgba(240, 208, 96, 0.08); border: 1px solid rgba(240, 208, 96, 0.25); border-radius: 8px; padding: 9px 11px; margin-top: 8px; font-size: 0.76rem; color: #e2e8f0; line-height: 1.45;">
                            🔎 <strong style="color: #F0D060;">Transparência:</strong> Este pedido remunera nosso serviço de curadoria e busca técnica de produtos físicos minerados no AliExpress Brasil, validados por nota e aprovações, com cobertura de entrega garantida.
                        </div>
                    <?php else: ?>
                        <div class="ds-order-item" style="color: var(--text-muted); font-size: 0.84rem;">
                            <span>Acesso Imediato e Vitalício</span>
                            <span>Incluso</span>
                        </div>
                        <div class="ds-order-item" style="color: var(--text-muted); font-size: 0.84rem;">
                            <span>Apostilas e Códigos para Download</span>
                            <span>Incluso</span>
                        </div>
                        <div class="ds-order-item" style="color: var(--text-muted); font-size: 0.84rem;">
                            <span>Certificado de Conclusão</span>
                            <span>Incluso</span>
                        </div>
                    <?php endif; ?>
                    <div class="ds-order-total">
                        <span>Total a Pagar:</span>
                        <span id="checkout_total_val">R$ <?= $formattedTotal ?></span>
                    </div>
                </div>

                <!-- Estado Vazio quando o cliente clica em Apagar -->
                <div id="checkout_empty_state" style="display: none; text-align: center; padding: 26px 14px; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.16); border-radius: 10px;">
                    <div style="font-size: 2.2rem; margin-bottom: 10px;">🗑️</div>
                    <h3 style="color: #fff; font-size: 1rem; margin-bottom: 6px;">Item removido do resumo do pedido</h3>
                    <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 16px; line-height: 1.4;">
                        Deseja restabelecer este produto ou escolher outro item na loja?
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <button type="button" onclick="restoreCheckoutItem()" style="width: 100%; padding: 10px 14px; background: rgba(0,210,132,0.15); border: 1px solid var(--accent-green); color: var(--accent-green); border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                            ↩️ Restabelecer Produto
                        </button>
                        <a href="<?= $baseUrl ?>/loja" style="display: block; width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 8px; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                            🛍️ Voltar para a Loja
                        </a>
                    </div>
                </div>

                <?php if (!$isPhysicalOrDropship): ?>
                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.85rem; color: var(--text-muted);">
                    <div>Aluno(a): <strong style="color: #fff;"><?= $userName ?></strong></div>
                    <div style="margin-top: 4px;">E-mail: <strong style="color: #fff;"><?= $userEmail ?></strong></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Meios de Pagamento e Entrega -->
            <div class="ds-card">
                <h2 class="ds-card-title"><?= $isPhysicalOrDropship ? '📦 Dados de Entrega &amp; Pagamento' : '💳 Escolha a Forma de Pagamento' ?></h2>
                <form id="checkout_form" onsubmit="handlePaymentSubmit(event)">
                    <?php if ($isPhysicalOrDropship): ?>
                    <!-- Dados do Comprador -->
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <h3 style="font-size: 0.92rem; font-weight: 700; color: #fff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <span>👤</span> 1. Dados do Comprador
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Nome Completo</label>
                                <input type="text" id="payer_name" name="payer_name" value="<?= $userName !== 'Aluno' ? $userName : '' ?>" placeholder="Seu nome" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">E-mail (para rastreio)</label>
                                <input type="email" id="payer_email" name="payer_email" value="<?= $userEmail ?>" placeholder="seu@email.com" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;" required>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">WhatsApp / Telefone</label>
                            <input type="tel" id="payer_phone" name="payer_phone" placeholder="(11) 99999-9999" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;">
                        </div>
                    </div>

                    <!-- Endereço de Entrega com ViaCEP -->
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <h3 style="font-size: 0.92rem; font-weight: 700; color: #fff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <span>🚚</span> 2. Endereço de Entrega
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">CEP (Busca automática)</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" placeholder="00000-000" maxlength="9" oninput="formatCepAndSearch(this)" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(69,243,255,0.3); border-radius: 6px; color: #45f3ff; font-weight: 600; font-family: monospace; font-size: 0.9rem;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Rua / Logradouro</label>
                                <input type="text" id="shipping_address" name="shipping_address" placeholder="Av. / Rua..." style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Número</label>
                                <input type="text" id="shipping_number" name="shipping_number" placeholder="123" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Complemento</label>
                                <input type="text" id="shipping_complement" name="shipping_complement" placeholder="Apto, Bloco..." style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1.2fr 1.2fr 0.6fr; gap: 10px;">
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Bairro</label>
                                <input type="text" id="shipping_neighborhood" name="shipping_neighborhood" placeholder="Bairro" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">Cidade</label>
                                <input type="text" id="shipping_city" name="shipping_city" placeholder="Cidade" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem;" required>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">UF</label>
                                <input type="text" id="shipping_state" name="shipping_state" placeholder="SP" maxlength="2" style="width: 100%; padding: 10px 12px; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #fff; font-size: 0.9rem; text-transform: uppercase;" required>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <h3 style="font-size: 0.92rem; font-weight: 700; color: #fff; margin: 16px 0 12px 0; display: flex; align-items: center; gap: 8px;">
                        <span>💳</span> <?= $isPhysicalOrDropship ? '3. Meio de Pagamento' : 'Escolha a Forma de Pagamento' ?>
                    </h3>
                    <?php if (!$hasMercadoPago && !$hasStripe && !$hasPayPal): ?>
                        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 14px 16px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 16px;">
                            ⚠️ Nenhum gateway de pagamento com credenciais ativas foi encontrado para este curso. Por favor, contate o administrador.
                        </div>
                    <?php endif; ?>

                    <?php if ($hasPayPal): ?>
                    <label class="ds-method-option <?= ($coursePlatform === 'paypal' || (!$hasMercadoPago && !$hasStripe)) ? 'active' : '' ?>" onclick="selectPaymentMethod('paypal', 'paypal')">
                        <input type="radio" name="payment_method" value="paypal" <?= ($coursePlatform === 'paypal' || (!$hasMercadoPago && !$hasStripe)) ? 'checked' : '' ?>>
                        <div style="flex:1;">
                            <strong style="color: #fff; display: block; font-size: 0.95rem;">🅿️ PayPal (Saldo PayPal &amp; Cartão Internacional)</strong>
                            <span style="font-size: 0.8rem; color: #38bdf8;">Ambiente oficial global do PayPal com conversão de moeda e proteção ao comprador</span>
                        </div>
                    </label>
                    <?php endif; ?>

                    <?php if ($hasMercadoPago): ?>
                    <label class="ds-method-option <?= ($hasMercadoPago && $coursePlatform !== 'paypal' && $coursePlatform !== 'stripe') ? 'active' : '' ?>" onclick="selectPaymentMethod('pix', 'mercadopago')">
                        <input type="radio" name="payment_method" value="pix" <?= ($hasMercadoPago && $coursePlatform !== 'paypal' && $coursePlatform !== 'stripe') ? 'checked' : '' ?>>
                        <div style="flex:1;">
                            <strong style="color: #fff; display: block; font-size: 0.95rem;">⚡ Pix Instantâneo (QR Code na Tela)</strong>
                            <span style="font-size: 0.8rem; color: var(--accent-cyan);">Aprovação em segundos e liberação imediata no seu painel</span>
                        </div>
                    </label>

                    <label class="ds-method-option" onclick="selectPaymentMethod('checkout_pro', 'mercadopago')">
                        <input type="radio" name="payment_method" value="checkout_pro">
                        <div style="flex:1;">
                            <strong style="color: #fff; display: block; font-size: 0.95rem;">💳 Cartão até 12x, Dois Cartões, Boleto ou Saldo</strong>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Pague no ambiente seguro oficial do Mercado Pago com todas as opções liberadas</span>
                        </div>
                    </label>
                    <?php endif; ?>

                    <?php if ($hasStripe): ?>
                    <label class="ds-method-option <?= ($coursePlatform === 'stripe' || (!$hasMercadoPago && !$hasPayPal)) ? 'active' : '' ?>" onclick="selectPaymentMethod('stripe', 'stripe')">
                        <input type="radio" name="payment_method" value="stripe" <?= ($coursePlatform === 'stripe' || (!$hasMercadoPago && !$hasPayPal)) ? 'checked' : '' ?>>
                        <div style="flex:1;">
                            <strong style="color: #fff; display: block; font-size: 0.95rem;">🌐 Cartão de Crédito Global / Apple Pay (Stripe)</strong>
                            <span style="font-size: 0.8rem; color: #c084fc;">Checkout seguro oficial do Stripe para cartões nacionais e internacionais</span>
                        </div>
                    </label>
                    <?php endif; ?>

                    <!-- Campo de CPF do Pagador (Obrigatório pelo Banco Central para Pix) -->
                    <div id="cpf_field_group" style="margin: 16px 0 12px 0; padding: 14px 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(69,243,255,0.25); border-radius: 8px; <?= (!$hasMercadoPago || $coursePlatform === 'paypal' || $coursePlatform === 'stripe') ? 'display:none;' : '' ?>">
                        <label for="payer_cpf" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; font-weight: 700; color: #fff; margin-bottom: 6px;">
                            <span>🆔 CPF do Pagador</span>
                            <span id="cpf_required_badge" style="color: var(--accent-cyan); font-size: 0.76rem; font-weight: 600;">(Obrigatório para Pix)</span>
                        </label>
                        <input type="text" id="payer_cpf" name="payer_cpf" value="<?= $userCpf ?>" placeholder="000.000.000-00" maxlength="14" style="width: 100%; padding: 12px 14px; background: #06090e; border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 1rem; font-family: monospace; outline: none; transition: border-color 0.2s;" oninput="formatCpfInput(this)">
                        <span style="display: block; font-size: 0.76rem; color: var(--text-muted); margin-top: 6px;">
                            Exigência do Banco Central do Brasil para emissão e liquidação instantânea de Pix.
                        </span>
                    </div>

                    <button type="submit" id="btn_submit_payment" class="ds-btn-pay" <?= (!$hasMercadoPago && !$hasStripe && !$hasPayPal) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                        <span id="btn_submit_payment_text">Finalizar Pagamento (R$ <?= $formattedTotal ?>)</span>
                    </button>
                </form>

                <!-- Box Dinâmica para Pix Instantâneo -->
                <div id="pix_result_box" class="ds-pix-box">
                    <div style="font-size: 2.2rem; margin-bottom: 8px;">📱</div>
                    <h3 style="color: #fff; font-size: 1.15rem; margin-bottom: 4px;">Pague com o Pix para liberar agora!</h3>
                    <p style="color: var(--text-muted); font-size: 0.84rem;">Abra o aplicativo do seu banco e escaneie o código ou use o Copia-e-Cola:</p>
                    <div id="pix_qr_image_wrap" style="margin: 12px 0;"></div>
                    <textarea id="pix_copia_cola_text" class="ds-pix-code-area" rows="3" readonly></textarea>
                    <button type="button" onclick="copyPixCode()" class="ds-btn-pay" style="margin-top: 8px; padding: 10px; font-size: 0.92rem; background: var(--accent-cyan); color: #000;">
                        📋 Copiar Código Pix
                    </button>
                    <p id="pix_status_hint" style="color: var(--accent-green); font-size: 0.85rem; margin-top: 12px; font-weight: 600;">
                        ⏳ Aguardando confirmação do banco...
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sistema Oficial de Notificações Toast -->
    <script src="<?= $baseUrl ?>/assets/js/core/toast.js"></script>

    <script>
        var baseUrl = <?= json_encode($baseUrl) ?>;
        var csrfToken = <?= json_encode($csrfToken) ?>;
        var courseId = <?= (int)$courseId ?>;
        var unitPrice = <?= json_encode((float)$unitPrice) ?>;
        var currentQty = <?= (int)$quantity ?>;
        var isItemRemoved = false;
        var hasAvailableGateway = <?= ($hasMercadoPago || $hasStripe || $hasPayPal) ? 'true' : 'false' ?>;
        var selectedMethod = 'pix';

        function formatBrlAmount(val) {
            return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function getCurrentFormattedTotal() {
            return formatBrlAmount(unitPrice * currentQty);
        }

        function updateOrderTotalsUI() {
            var formatted = getCurrentFormattedTotal();
            var qtyEl = document.getElementById('checkout_qty_display');
            var subEl = document.getElementById('checkout_subtotal_val');
            var totEl = document.getElementById('checkout_total_val');
            if (qtyEl) qtyEl.textContent = String(currentQty);
            if (subEl) subEl.textContent = 'R$ ' + formatted;
            if (totEl) totEl.textContent = 'R$ ' + formatted;
            selectPaymentMethod(selectedMethod, selectedGateway);
        }

        function adjustCheckoutQty(delta) {
            if (isItemRemoved) return;
            var nextQty = currentQty + delta;
            if (nextQty < 1) {
                removeCheckoutItem();
                return;
            }
            if (nextQty > 99) nextQty = 99;
            currentQty = nextQty;
            updateOrderTotalsUI();
        }

        function removeCheckoutItem() {
            isItemRemoved = true;
            var summaryEl = document.getElementById('checkout_item_summary');
            var emptyEl = document.getElementById('checkout_empty_state');
            var btn = document.getElementById('btn_submit_payment');
            if (summaryEl) summaryEl.style.display = 'none';
            if (emptyEl) emptyEl.style.display = 'block';
            if (btn) btn.disabled = true;
            showNotify('Produto removido do resumo de compra.', 'info');
        }

        function restoreCheckoutItem() {
            isItemRemoved = false;
            if (currentQty < 1) currentQty = 1;
            var summaryEl = document.getElementById('checkout_item_summary');
            var emptyEl = document.getElementById('checkout_empty_state');
            var btn = document.getElementById('btn_submit_payment');
            if (summaryEl) summaryEl.style.display = 'block';
            if (emptyEl) emptyEl.style.display = 'none';
            if (btn) btn.disabled = !hasAvailableGateway;
            updateOrderTotalsUI();
            showNotify('Produto restabelecido no pedido!', 'success');
        }

        function showNotify(msg, type) {
            type = type || 'info';
            if (window.DS && window.DS.ui && window.DS.ui.toast) {
                if (type === 'error') DS.ui.toast.error(msg);
                else if (type === 'success') DS.ui.toast.success(msg);
                else if (type === 'warning') DS.ui.toast.warning(msg);
                else DS.ui.toast.info(msg);
            } else {
                console.log('[' + type + '] ' + msg);
            }
        }

        function formatCpfInput(el) {
            var v = el.value.replace(/\D/g, '').slice(0, 11);
            if (v.length > 9) {
                el.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                el.value = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                el.value = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            } else {
                el.value = v;
            }
        }

        function formatCepAndSearch(el) {
            var v = el.value.replace(/\D/g, '').slice(0, 8);
            if (v.length > 5) {
                el.value = v.replace(/(\d{5})(\d{1,3})/, '$1-$2');
            } else {
                el.value = v;
            }
            if (v.length === 8) {
                fetch('https://viacep.com.br/ws/' + v + '/json/')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.erro) {
                            var addr = document.getElementById('shipping_address');
                            var neigh = document.getElementById('shipping_neighborhood');
                            var city = document.getElementById('shipping_city');
                            var uf = document.getElementById('shipping_state');
                            var num = document.getElementById('shipping_number');
                            if (addr && data.logradouro) addr.value = data.logradouro;
                            if (neigh && data.bairro) neigh.value = data.bairro;
                            if (city && data.localidade) city.value = data.localidade;
                            if (uf && data.uf) uf.value = data.uf;
                            if (num) num.focus();
                            showNotify('Endereço preenchido automaticamente via CEP!', 'success');
                        }
                    })
                    .catch(function() {});
            }
        }

        var selectedMethod = 'pix';
        var selectedGateway = 'mercadopago';

        function selectPaymentMethod(m, g) {
            selectedMethod = m;
            selectedGateway = g || (m === 'stripe' ? 'stripe' : (m === 'paypal' ? 'paypal' : 'mercadopago'));
            document.querySelectorAll('.ds-method-option').forEach(function(el) {
                el.classList.remove('active');
            });
            var rad = document.querySelector('input[value="' + m + '"]');
            if (rad) {
                rad.checked = true;
                rad.closest('.ds-method-option').classList.add('active');
            }

            var cpfGroup = document.getElementById('cpf_field_group');
            var btnText = document.getElementById('btn_submit_payment_text');
            var formattedTotalNow = getCurrentFormattedTotal();
            if (m === 'pix') {
                if (cpfGroup) cpfGroup.style.display = 'block';
                if (btnText) btnText.textContent = '⚡ Gerar QR Code Pix (R$ ' + formattedTotalNow + ')';
            } else if (m === 'checkout_pro') {
                if (cpfGroup) cpfGroup.style.display = 'none';
                if (btnText) btnText.textContent = '💳 Pagar com Mercado Pago (R$ ' + formattedTotalNow + ')';
            } else if (m === 'stripe') {
                if (cpfGroup) cpfGroup.style.display = 'none';
                if (btnText) btnText.textContent = '🌐 Pagar com Cartão / Stripe (R$ ' + formattedTotalNow + ')';
            } else if (m === 'paypal') {
                if (cpfGroup) cpfGroup.style.display = 'none';
                if (btnText) btnText.textContent = '🅿️ Pagar com PayPal (R$ ' + formattedTotalNow + ')';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var cpfEl = document.getElementById('payer_cpf');
            if (cpfEl && cpfEl.value) {
                formatCpfInput(cpfEl);
            }
            <?php if ($hasPayPal && ($coursePlatform === 'paypal' || (!$hasMercadoPago && !$hasStripe))): ?>
            selectPaymentMethod('paypal', 'paypal');
            <?php elseif ($hasStripe && ($coursePlatform === 'stripe' || (!$hasMercadoPago && !$hasPayPal))): ?>
            selectPaymentMethod('stripe', 'stripe');
            <?php elseif ($hasMercadoPago): ?>
            selectPaymentMethod('pix', 'mercadopago');
            <?php elseif ($hasPayPal): ?>
            selectPaymentMethod('paypal', 'paypal');
            <?php elseif ($hasStripe): ?>
            selectPaymentMethod('stripe', 'stripe');
            <?php endif; ?>
        });

        var pixPollInterval = null;

        function startPixPolling(orderRef) {
            if (pixPollInterval) clearInterval(pixPollInterval);
            pixPollInterval = setInterval(function() {
                if (!orderRef) return;
                fetch(baseUrl + '/checkout/status/' + encodeURIComponent(orderRef), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res && res.paid) {
                        clearInterval(pixPollInterval);
                        var hint = document.getElementById('pix_status_hint');
                        var isPhysical = <?= $isPhysicalOrDropship ? 'true' : 'false' ?>;
                        if (hint) {
                            hint.innerHTML = isPhysical ? '✅ Pagamento confirmado pelo banco! Pedido registrado com sucesso...' : '✅ Pagamento confirmado pelo banco! Liberando seu curso...';
                            hint.style.color = '#00d284';
                        }
                        showNotify('🎉 Pagamento Pix confirmado com sucesso! Redirecionando...', 'success');
                        setTimeout(function() {
                            window.location.href = res.redirect_url || (isPhysical ? (baseUrl + '/checkout/sucesso/' + encodeURIComponent(orderRef)) : (baseUrl + '/aluno/painel'));
                        }, 1200);
                    }
                })
                .catch(function() {});
            }, 3500);
        }

        function handlePaymentSubmit(e) {
            e.preventDefault();
            if (isItemRemoved || currentQty < 1) {
                showNotify('O item foi removido do pedido. Restabeleça o produto antes de finalizar.', 'warning');
                return;
            }
            var btn = document.getElementById('btn_submit_payment');
            var btnText = document.getElementById('btn_submit_payment_text');
            var cpfInput = document.getElementById('payer_cpf');
            var rawCpf = cpfInput ? cpfInput.value.replace(/\D/g, '') : '';

            var isPhysical = <?= $isPhysicalOrDropship ? 'true' : 'false' ?>;
            var payerNameEl = document.getElementById('payer_name');
            var payerEmailEl = document.getElementById('payer_email');
            var payerPhoneEl = document.getElementById('payer_phone');
            var shipZipEl = document.getElementById('shipping_zip');
            var shipAddrEl = document.getElementById('shipping_address');
            var shipNumEl = document.getElementById('shipping_number');
            var shipCompEl = document.getElementById('shipping_complement');
            var shipNeighEl = document.getElementById('shipping_neighborhood');
            var shipCityEl = document.getElementById('shipping_city');
            var shipStateEl = document.getElementById('shipping_state');

            if (isPhysical) {
                if (payerNameEl && !payerNameEl.value.trim()) {
                    showNotify('Informe seu nome completo para entrega.', 'warning');
                    payerNameEl.focus();
                    return;
                }
                if (payerEmailEl && !payerEmailEl.value.trim()) {
                    showNotify('Informe seu e-mail para receber o rastreio.', 'warning');
                    payerEmailEl.focus();
                    return;
                }
                if (shipZipEl && !shipZipEl.value.trim()) {
                    showNotify('Informe o CEP para entrega.', 'warning');
                    shipZipEl.focus();
                    return;
                }
                if (shipAddrEl && !shipAddrEl.value.trim()) {
                    showNotify('Informe a rua / logradouro de entrega.', 'warning');
                    shipAddrEl.focus();
                    return;
                }
                if (shipNumEl && !shipNumEl.value.trim()) {
                    showNotify('Informe o número do endereço.', 'warning');
                    shipNumEl.focus();
                    return;
                }
            }

            if (selectedMethod === 'pix' && rawCpf.length !== 11) {
                showNotify('Por favor, informe seu CPF com 11 dígitos para emissão do Pix.', 'warning');
                if (cpfInput) {
                    cpfInput.focus();
                    cpfInput.style.borderColor = '#45f3ff';
                }
                return;
            }

            btn.disabled = true;
            if (btnText) btnText.textContent = '⏳ Processando Pagamento...';

            showNotify('Conectando ao gateway de pagamento seguro...', 'info');

            var gateway = selectedGateway;

            var payload = {
                csrf_token: csrfToken,
                course_id: courseId,
                quantity: currentQty,
                gateway: gateway,
                payment_method: selectedMethod,
                payer_cpf: rawCpf,
                payer_name: payerNameEl ? payerNameEl.value.trim() : '',
                payer_email: payerEmailEl ? payerEmailEl.value.trim() : '',
                payer_phone: payerPhoneEl ? payerPhoneEl.value.trim() : '',
                shipping_zip: shipZipEl ? shipZipEl.value.trim() : '',
                shipping_address: shipAddrEl ? shipAddrEl.value.trim() : '',
                shipping_number: shipNumEl ? shipNumEl.value.trim() : '',
                shipping_complement: shipCompEl ? shipCompEl.value.trim() : '',
                shipping_neighborhood: shipNeighEl ? shipNeighEl.value.trim() : '',
                shipping_city: shipCityEl ? shipCityEl.value.trim() : '',
                shipping_state: shipStateEl ? shipStateEl.value.trim() : ''
            };

            fetch(baseUrl + '/checkout/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (parseErr) {
                        console.error('Resposta não-JSON recebida:', text);
                        throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
                    }
                });
            })
            .then(function(data) {
                btn.disabled = false;
                selectPaymentMethod(selectedMethod);

                if (data && data.success) {
                    if (data.status === 'approved') {
                        showNotify(data.message || 'Pagamento aprovado com sucesso! Redirecionando...', 'success');
                        setTimeout(function() {
                            window.location.href = data.redirect_url || (isPhysical && data.order_ref ? (baseUrl + '/checkout/sucesso/' + encodeURIComponent(data.order_ref)) : (baseUrl + '/aluno/painel'));
                        }, 1200);
                    } else if (data.payment_method === 'pix' && data.pix_copia_e_cola) {
                        showNotify('Chave Pix gerada! Escaneie o QR Code ou copie o código abaixo.', 'success');
                        var box = document.getElementById('pix_result_box');
                        box.style.display = 'block';
                        document.getElementById('pix_copia_cola_text').value = data.pix_copia_e_cola;
                        
                        var qrImgUrl = data.pix_qr_code || ('https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data=' + encodeURIComponent(data.pix_copia_e_cola));
                        document.getElementById('pix_qr_image_wrap').innerHTML = '<img src="' + qrImgUrl + '" alt="QR Code Pix" style="max-width:210px; border-radius:10px; border:2px solid rgba(69,243,255,0.4); padding:8px; background:#fff; box-shadow:0 6px 20px rgba(0,0,0,0.5); display:inline-block;">';
                        
                        box.scrollIntoView({ behavior: 'smooth' });
                        if (data.order_ref) {
                            startPixPolling(data.order_ref);
                        }
                    } else if (data.redirect_url) {
                        var targetName = (data.payment_method === 'paypal') ? 'PayPal' : ((data.payment_method === 'stripe') ? 'Stripe' : 'Mercado Pago');
                        showNotify(data.message || ('Redirecionando para o ambiente seguro do ' + targetName + '...'), 'info');
                        setTimeout(function() {
                            window.location.href = data.redirect_url;
                        }, 900);
                    } else if (data.boleto_url) {
                        showNotify('Boleto bancário gerado com sucesso!', 'success');
                        window.open(data.boleto_url, '_blank');
                    } else {
                        showNotify(data.message || 'Solicitação processada.', 'info');
                    }
                } else {
                    showNotify((data && data.message) ? data.message : 'Falha ao processar pagamento.', 'error');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                selectPaymentMethod(selectedMethod);
                showNotify('Erro de conexão ao processar pagamento: ' + err.message, 'error');
            });
        }

        function copyPixCode() {
            var txt = document.getElementById('pix_copia_cola_text');
            txt.select();
            txt.setSelectionRange(0, 99999);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(txt.value).then(function() {
                    showNotify('Código Pix copiado para a área de transferência!', 'success');
                });
            } else {
                document.execCommand('copy');
                showNotify('Código Pix copiado com sucesso!', 'success');
            }
        }
    </script>
</body>
</html>
