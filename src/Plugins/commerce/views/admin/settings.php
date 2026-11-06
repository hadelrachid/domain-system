<?php
/**
 * Configurações de Gateways de Pagamento - DomainCommerce
 *
 * @var string $baseUrl
 * @var array<object> $gateways
 * @var array<string, mixed> $settings
 * @var string $csrfToken
 * @var string|null $flash
 * @var string|null $error
 */
?>
<div class="ds-commerce-settings" style="padding: 28px; max-width: 1100px; margin: 0 auto; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif;">

    <!-- Cabeçalho -->
    <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 4px;">
                <a href="<?= $baseUrl ?>/admin/commerce" style="color: #45f3ff; text-decoration: none;">&larr; Voltar ao Painel DomainCommerce</a>
            </div>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px;">
                <span>⚙️</span> Configurações de Pagamentos &amp; Gateways
            </h1>
            <p style="color: #94a3b8; margin: 6px 0 0 0; font-size: 0.95rem;">
                Conecte suas credenciais de produção e sandbox para Mercado Pago, PayPal, Stripe e outros provedores.
            </p>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
        <div style="background: rgba(0, 210, 132, 0.15); border: 1px solid rgba(0, 210, 132, 0.4); color: #00d284; padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-size: 0.92rem;">
            ✓ <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-size: 0.92rem;">
            ⚠ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $baseUrl ?>/admin/commerce/settings" style="display: flex; flex-direction: column; gap: 24px;">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Bloco 1: Moeda Padrão -->
        <div style="background: #0f141c; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 24px;">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: #fff; margin: 0 0 16px 0;">🌐 Moeda e Formatação</h2>
            <div style="max-width: 320px;">
                <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Moeda Base de Cobrança</label>
                <select name="active_currency" style="width: 100%; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-size: 0.92rem;">
                    <option value="BRL" <?= ($settings['active_currency'] ?? 'BRL') === 'BRL' ? 'selected' : '' ?>>BRL - Real Brasileiro (R$)</option>
                    <option value="USD" <?= ($settings['active_currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - Dólar Americano ($)</option>
                    <option value="EUR" <?= ($settings['active_currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro (€)</option>
                </select>
            </div>
        </div>

        <!-- Bloco 2: ⭐ Mercado Pago (Gateway Nativo Principal Oficial) -->
        <div style="background: #0f141c; border: 2px solid #00d284; border-radius: 12px; padding: 26px; box-shadow: 0 4px 20px rgba(0, 210, 132, 0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #00d284; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🇧🇷</span> ⭐ Mercado Pago (Motor Nativo Principal)
                </h2>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: #cbd5e1;">
                        <input type="checkbox" name="mercadopago_sandbox" value="1" <?= !empty($settings['mercadopago_sandbox']) ? 'checked' : '' ?>>
                        Modo Sandbox (Testes)
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 700; color: #00d284;">
                        <input type="checkbox" name="mercadopago_enabled" value="1" <?= !empty($settings['mercadopago_enabled']) ? 'checked' : '' ?>>
                        Ativar Mercado Pago
                    </label>
                </div>
            </div>
            <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 18px; line-height: 1.5;">
                Gateway de pagamento oficial e preferencial com API pública completa: <strong>Pix Instantâneo</strong> (com QR Code dinâmico e Copia-e-Cola), <strong>Cartão de Crédito nacional parcelado em até 12x</strong>, <strong>Boleto Bancário</strong> e <strong>Checkout Pro Oficial</strong> com proteção contra fraude.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Access Token (Produção ou Teste <code>TEST-...</code>)</label>
                    <input type="password" id="mp_access_token_input" name="mercadopago_access_token" value="<?= htmlspecialchars((string)($settings['mercadopago_access_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="APP_USR-... ou TEST-..." style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                    <div id="mp_token_status_box" style="margin-top: 8px; font-size: 0.83rem; display: none; padding: 8px 12px; border-radius: 6px;"></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Public Key (Chave Pública do Mercado Pago)</label>
                    <input type="text" id="mp_public_key_input" name="mercadopago_public_key" value="<?= htmlspecialchars((string)($settings['mercadopago_public_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="APP_USR-... ou TEST-..." style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                    <div id="mp_pubkey_status_box" style="margin-top: 8px; font-size: 0.83rem; display: none; padding: 8px 12px; border-radius: 6px;"></div>
                </div>
            </div>

            <!-- Botão de Teste em Tempo Real das Credenciais com Ícones Verde / Vermelho -->
            <div style="margin-top: 14px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <button type="button" id="btn_test_mp" onclick="testMercadoPagoCredentials()" style="background: rgba(0, 210, 132, 0.12); color: #00d284; border: 1px solid #00d284; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <span>⚡</span> Testar Credenciais do Mercado Pago Agora
                </button>
                <span id="mp_test_spinner" style="display: none; font-size: 0.85rem; color: #94a3b8;">
                    ⏳ Conectando e validando com a API oficial do Mercado Pago...
                </span>
            </div>

            <div style="margin-top: 16px; background: rgba(0,0,0,0.3); padding: 14px 18px; border-radius: 8px; font-size: 0.85rem; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <strong>URL de Webhook IPN / Notificações:</strong><br>
                    <code style="color: #45f3ff;"><?= $baseUrl ?>/webhook/payment/mercadopago</code>
                </div>
                <div>
                    <a href="https://www.mercadopago.com.br/developers/pt/reference" target="_blank" rel="noopener noreferrer" style="color: #00d284; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(0, 210, 132, 0.4); padding: 6px 12px; border-radius: 6px; background: rgba(0, 210, 132, 0.08);">
                        <span>📖</span> Documentação Oficial Mercado Pago Developers &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Bloco 3: 🌎 PayPal (Gateway Global & Moeda Estrangeira) -->
        <div style="background: #0f141c; border: 1px solid rgba(69, 243, 255, 0.35); border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(69, 243, 255, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #45f3ff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🌎</span> PayPal (Vendas Globais &amp; Moeda Estrangeira)
                </h2>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: #cbd5e1;">
                        <input type="checkbox" name="paypal_sandbox" value="1" <?= (!isset($settings['paypal_sandbox']) || !empty($settings['paypal_sandbox'])) ? 'checked' : '' ?>>
                        Modo Sandbox (Testes)
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 700; color: #45f3ff;">
                        <input type="checkbox" name="paypal_enabled" value="1" <?= !empty($settings['paypal_enabled']) ? 'checked' : '' ?>>
                        Ativar PayPal
                    </label>
                </div>
            </div>
            <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 18px; line-height: 1.5;">
                Permite que alunos do mundo inteiro paguem com saldo PayPal ou cartões internacionais. Conecte com suas credenciais obtidas no Portal de Desenvolvedores do PayPal.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Client ID (Sandbox ou Live)</label>
                    <input type="text" id="pp_client_id_input" name="paypal_client_id" value="<?= htmlspecialchars((string)($settings['paypal_client_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="YS... ou Live Client ID" style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Client Secret (Chave Secreta)</label>
                    <input type="password" id="pp_client_secret_input" name="paypal_client_secret" value="<?= htmlspecialchars((string)($settings['paypal_client_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="E... ou Live Secret" style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                </div>
            </div>

            <div id="pp_status_box" style="margin-top: 12px; font-size: 0.85rem; display: none; padding: 10px 14px; border-radius: 6px;"></div>

            <div style="margin-top: 14px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <button type="button" id="btn_test_paypal" onclick="testPayPalCredentials()" style="background: rgba(69, 243, 255, 0.12); color: #45f3ff; border: 1px solid #45f3ff; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <span>⚡</span> Testar Credenciais do PayPal Agora
                </button>
            </div>

            <div style="margin-top: 16px; background: rgba(0,0,0,0.3); padding: 14px 18px; border-radius: 8px; font-size: 0.85rem; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <strong>URL de Webhook PayPal:</strong><br>
                    <code style="color: #45f3ff;"><?= $baseUrl ?>/webhook/payment/paypal</code>
                </div>
                <div>
                    <a href="https://developer.paypal.com/dashboard/applications" target="_blank" rel="noopener noreferrer" style="color: #45f3ff; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(69, 243, 255, 0.4); padding: 6px 12px; border-radius: 6px; background: rgba(69, 243, 255, 0.08);">
                        <span>📖</span> Obter Chaves no PayPal Developer Dashboard &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Bloco 4: 💳 Stripe (Cartões Internacionais & Wallets) -->
        <div style="background: #0f141c; border: 1px solid rgba(192, 132, 252, 0.35); border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(192, 132, 252, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #c084fc; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>💳</span> Stripe (Cartões Internacionais, Apple Pay &amp; Google Pay)
                </h2>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: #cbd5e1;">
                        <input type="checkbox" name="stripe_sandbox" value="1" <?= !empty($settings['stripe_sandbox']) ? 'checked' : '' ?>>
                        Modo Teste
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 700; color: #c084fc;">
                        <input type="checkbox" name="stripe_enabled" value="1" <?= !empty($settings['stripe_enabled']) ? 'checked' : '' ?>>
                        Ativar Stripe
                    </label>
                </div>
            </div>
            <p style="font-size: 0.9rem; color: #cbd5e1; margin-bottom: 18px; line-height: 1.5;">
                Checkout Session de alta conversão com suporte a cartões de crédito/débito globais, Apple Pay e Google Pay.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Secret Key (<code>sk_test_...</code> ou <code>sk_live_...</code>)</label>
                    <input type="password" id="stripe_secret_key_input" name="stripe_secret_key" value="<?= htmlspecialchars((string)($settings['stripe_secret_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="sk_test_..." style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Publishable Key (<code>pk_test_...</code> ou <code>pk_live_...</code>)</label>
                    <input type="text" id="stripe_publishable_key_input" name="stripe_publishable_key" value="<?= htmlspecialchars((string)($settings['stripe_publishable_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="pk_test_..." style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px;">Webhook Signing Secret (<code>whsec_...</code>)</label>
                    <input type="password" id="stripe_webhook_secret_input" name="stripe_webhook_secret" value="<?= htmlspecialchars((string)($settings['stripe_webhook_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="whsec_..." style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-family: monospace;">
                </div>
            </div>

            <div id="stripe_status_box" style="margin-top: 12px; font-size: 0.85rem; display: none; padding: 10px 14px; border-radius: 6px;"></div>

            <div style="margin-top: 14px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <button type="button" id="btn_test_stripe" onclick="testStripeCredentials()" style="background: rgba(192, 132, 252, 0.12); color: #c084fc; border: 1px solid #c084fc; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <span>⚡</span> Testar Chave Secreta do Stripe Agora
                </button>
            </div>

            <div style="margin-top: 16px; background: rgba(0,0,0,0.3); padding: 14px 18px; border-radius: 8px; font-size: 0.85rem; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <strong>URL de Webhook Stripe:</strong><br>
                    <code style="color: #c084fc;"><?= $baseUrl ?>/webhook/payment/stripe</code>
                </div>
                <div>
                    <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener noreferrer" style="color: #c084fc; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(192, 132, 252, 0.4); padding: 6px 12px; border-radius: 6px; background: rgba(192, 132, 252, 0.08);">
                        <span>📖</span> Obter Chaves no Stripe Dashboard &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Bloco 5: Plataformas de Afiliados (Hotmart, Eduzz, Monetizze & ClickBank) -->
        <div style="background: #0f141c; border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 12px; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #fbbf24; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🚀</span> Plataformas de Afiliados (Hotmart, Eduzz, Monetizze &amp; ClickBank)
                </h2>
                <span style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(245, 158, 11, 0.3);">
                    AUTO-PROVISIONAMENTO DE ALUNOS
                </span>
            </div>
            <p style="font-size: 0.9rem; color: #94a3b8; margin-bottom: 22px; line-height: 1.6;">
                Venda seus cursos pelas maiores plataformas de afiliados do Brasil e do mundo. Ao receber a notificação de venda aprovada via Webhook, o <strong>DomainCommerce</strong> cria automaticamente o usuário do aluno na plataforma e o <strong>Academy</strong> libera a matrícula no mesmo segundo!
            </p>

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- 5.1 Hotmart -->
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <strong style="color: #f97316; font-size: 1rem;">🔥 Hotmart Webhook</strong>
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; color: #cbd5e1;">
                            <input type="checkbox" name="hotmart_enabled" value="1" <?= !empty($settings['hotmart_enabled']) ? 'checked' : '' ?>>
                            Ativar Integração Hotmart
                        </label>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 0.82rem; color: #94a3b8; margin-bottom: 4px;">Token de Segurança Hotmart (Hottok)</label>
                        <input type="text" name="hotmart_hottok" value="<?= htmlspecialchars((string)($settings['hotmart_hottok'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Cole o Hottok configurado na aba Webhook da Hotmart" style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 9px 12px; color: #fff; font-family: monospace;">
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; color: #64748b;">
                        <strong>URL do Webhook para cadastrar na Hotmart:</strong><br>
                        <code style="color: #f97316;"><?= $baseUrl ?>/webhook/payment/hotmart</code>
                    </div>
                </div>

                <!-- 5.2 Eduzz -->
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <strong style="color: #3b82f6; font-size: 1rem;">🔷 Eduzz Webhook (Nutror)</strong>
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; color: #cbd5e1;">
                            <input type="checkbox" name="eduzz_enabled" value="1" <?= !empty($settings['eduzz_enabled']) ? 'checked' : '' ?>>
                            Ativar Integração Eduzz
                        </label>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 0.82rem; color: #94a3b8; margin-bottom: 4px;">Public Key / Token Eduzz</label>
                        <input type="text" name="eduzz_token" value="<?= htmlspecialchars((string)($settings['eduzz_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Chave de API / Token de Webhook da Eduzz" style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 9px 12px; color: #fff; font-family: monospace;">
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; color: #64748b;">
                        <strong>URL do Webhook para cadastrar na Eduzz:</strong><br>
                        <code style="color: #3b82f6;"><?= $baseUrl ?>/webhook/payment/eduzz</code>
                    </div>
                </div>

                <!-- 5.3 Monetizze -->
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <strong style="color: #10b981; font-size: 1rem;">🟢 Monetizze Webhook / Postback</strong>
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; color: #cbd5e1;">
                            <input type="checkbox" name="monetizze_enabled" value="1" <?= !empty($settings['monetizze_enabled']) ? 'checked' : '' ?>>
                            Ativar Integração Monetizze
                        </label>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 0.82rem; color: #94a3b8; margin-bottom: 4px;">Chave Única Monetizze</label>
                        <input type="text" name="monetizze_chave_unica" value="<?= htmlspecialchars((string)($settings['monetizze_chave_unica'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Chave única presente nas configurações da sua conta Monetizze" style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 9px 12px; color: #fff; font-family: monospace;">
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; color: #64748b;">
                        <strong>URL do Webhook para cadastrar na Monetizze:</strong><br>
                        <code style="color: #10b981;"><?= $baseUrl ?>/webhook/payment/monetizze</code>
                    </div>
                </div>

                <!-- 5.4 ClickBank -->
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <strong style="color: #eab308; font-size: 1rem;">🟡 ClickBank Instant Notification (INS)</strong>
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; color: #cbd5e1;">
                            <input type="checkbox" name="clickbank_enabled" value="1" <?= !empty($settings['clickbank_enabled']) ? 'checked' : '' ?>>
                            Ativar Integração ClickBank
                        </label>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 0.82rem; color: #94a3b8; margin-bottom: 4px;">ClickBank Secret Key</label>
                        <input type="password" name="clickbank_secret" value="<?= htmlspecialchars((string)($settings['clickbank_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Secret Key configurada no painel My Account > My Site > INS" style="width: 100%; box-sizing: border-box; background: #151c28; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 9px 12px; color: #fff; font-family: monospace;">
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-size: 0.82rem; color: #64748b;">
                        <strong>URL do Webhook para cadastrar na ClickBank:</strong><br>
                        <code style="color: #eab308;"><?= $baseUrl ?>/webhook/payment/clickbank</code>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: right; margin-top: 8px;">
            <button type="submit" style="background: linear-gradient(135deg, #00d284, #00b370); color: #000; border: none; padding: 13px 32px; border-radius: 8px; font-weight: 800; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 18px rgba(0,210,132,0.3);">
                💾 Salvar Configurações
            </button>
        </div>

    </form>

    <script>
        function notify(msg, type) {
            if (window.OS && typeof window.OS.notify === 'function') {
                window.OS.notify(msg, type);
            } else if (window.DS && window.DS.ui && typeof window.DS.ui.toast === 'function') {
                window.DS.ui.toast(msg, type);
            } else {
                console.log('[' + type + '] ' + msg);
            }
        }

        function testMercadoPagoCredentials() {
            var tokenInput = document.getElementById('mp_access_token_input');
            var pubkeyInput = document.getElementById('mp_public_key_input');
            var tokenBox = document.getElementById('mp_token_status_box');
            var pubkeyBox = document.getElementById('mp_pubkey_status_box');
            var btn = document.getElementById('btn_test_mp');
            var spinner = document.getElementById('mp_test_spinner');

            var accessToken = tokenInput ? tokenInput.value.trim() : '';
            var publicKey = pubkeyInput ? pubkeyInput.value.trim() : '';

            if (!accessToken && !publicKey) {
                notify('Preencha ao menos o Access Token ou a Public Key para realizar o teste de validação.', 'error');
                return;
            }

            btn.disabled = true;
            spinner.style.display = 'inline-block';
            tokenBox.style.display = 'none';
            pubkeyBox.style.display = 'none';

            var payload = {
                csrf_token: <?= json_encode($csrfToken) ?>,
                access_token: accessToken,
                public_key: publicKey
            };

            fetch(<?= json_encode($baseUrl) ?> + '/admin/commerce/test-mercadopago', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.disabled = false;
                spinner.style.display = 'none';

                if (!data || !data.success) {
                    var errorMsg = (data && data.message) ? data.message : 'Falha ao executar teste';
                    notify(errorMsg, 'error');
                    return;
                }

                notify('Teste de conexão com a API do Mercado Pago concluído!', 'success');

                // Renderiza Status do Access Token (Verde ou Vermelho)
                if (data.access_token) {
                    tokenBox.style.display = 'block';
                    if (data.access_token.ok) {
                        tokenBox.style.background = 'rgba(0, 210, 132, 0.12)';
                        tokenBox.style.color = '#00d284';
                        tokenBox.style.border = '1px solid rgba(0, 210, 132, 0.35)';
                        tokenBox.innerHTML = '<span style="font-size: 1.1rem; margin-right: 6px;">🟢</span><strong>OK:</strong> ' + data.access_token.message;
                    } else {
                        tokenBox.style.background = 'rgba(239, 68, 68, 0.12)';
                        tokenBox.style.color = '#ef4444';
                        tokenBox.style.border = '1px solid rgba(239, 68, 68, 0.35)';
                        tokenBox.innerHTML = '<span style="font-size: 1.1rem; margin-right: 6px;">🔴</span><strong>Erro:</strong> ' + data.access_token.message;
                    }
                }

                // Renderiza Status da Public Key (Verde ou Vermelho)
                if (data.public_key) {
                    pubkeyBox.style.display = 'block';
                    if (data.public_key.ok) {
                        pubkeyBox.style.background = 'rgba(0, 210, 132, 0.12)';
                        pubkeyBox.style.color = '#00d284';
                        pubkeyBox.style.border = '1px solid rgba(0, 210, 132, 0.35)';
                        pubkeyBox.innerHTML = '<span style="font-size: 1.1rem; margin-right: 6px;">🟢</span><strong>OK:</strong> ' + data.public_key.message;
                    } else {
                        pubkeyBox.style.background = 'rgba(239, 68, 68, 0.12)';
                        pubkeyBox.style.color = '#ef4444';
                        pubkeyBox.style.border = '1px solid rgba(239, 68, 68, 0.35)';
                        pubkeyBox.innerHTML = '<span style="font-size: 1.1rem; margin-right: 6px;">🔴</span><strong>Erro:</strong> ' + data.public_key.message;
                    }
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                spinner.style.display = 'none';
                notify('Erro de conexão ao testar credenciais: ' + err, 'error');
            });
        }

        function testPayPalCredentials() {
            var clientId = (document.getElementById('pp_client_id_input') || {}).value || '';
            var clientSecret = (document.getElementById('pp_client_secret_input') || {}).value || '';
            var box = document.getElementById('pp_status_box');
            var btn = document.getElementById('btn_test_paypal');

            if (!clientId.trim() || !clientSecret.trim()) {
                notify('Informe o Client ID e Client Secret do PayPal para testar.', 'error');
                return;
            }

            btn.disabled = true;
            btn.textContent = '⏳ Testando...';
            box.style.display = 'none';

            fetch(<?= json_encode($baseUrl) ?> + '/admin/commerce/test-paypal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    csrf_token: <?= json_encode($csrfToken) ?>,
                    client_id: clientId.trim(),
                    client_secret: clientSecret.trim()
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = '⚡ Testar Credenciais do PayPal';
                box.style.display = 'block';
                if (data && data.success && data.result) {
                    if (data.result.ok) {
                        box.style.background = 'rgba(0, 210, 132, 0.12)';
                        box.style.color = '#00d284';
                        box.style.border = '1px solid rgba(0, 210, 132, 0.35)';
                        box.innerHTML = '🟢 <strong>OK:</strong> ' + data.result.message;
                        notify('Conexão com PayPal validada com sucesso!', 'success');
                    } else {
                        box.style.background = 'rgba(239, 68, 68, 0.12)';
                        box.style.color = '#ef4444';
                        box.style.border = '1px solid rgba(239, 68, 68, 0.35)';
                        box.innerHTML = '🔴 <strong>Erro:</strong> ' + data.result.message;
                        notify(data.result.message, 'error');
                    }
                } else {
                    notify((data && data.message) ? data.message : 'Falha no teste PayPal.', 'error');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.textContent = '⚡ Testar Credenciais do PayPal';
                notify('Erro de conexão ao testar PayPal: ' + err, 'error');
            });
        }

        function testStripeCredentials() {
            var secretKey = (document.getElementById('stripe_secret_key_input') || {}).value || '';
            var box = document.getElementById('stripe_status_box');
            var btn = document.getElementById('btn_test_stripe');

            if (!secretKey.trim()) {
                notify('Informe a Secret Key do Stripe para testar.', 'error');
                return;
            }

            btn.disabled = true;
            btn.textContent = '⏳ Testando...';
            box.style.display = 'none';

            fetch(<?= json_encode($baseUrl) ?> + '/admin/commerce/test-stripe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    csrf_token: <?= json_encode($csrfToken) ?>,
                    secret_key: secretKey.trim()
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = '⚡ Testar Chave Secreta do Stripe';
                box.style.display = 'block';
                if (data && data.success && data.result) {
                    if (data.result.ok) {
                        box.style.background = 'rgba(0, 210, 132, 0.12)';
                        box.style.color = '#00d284';
                        box.style.border = '1px solid rgba(0, 210, 132, 0.35)';
                        box.innerHTML = '🟢 <strong>OK:</strong> ' + data.result.message;
                        notify('Conexão com Stripe validada com sucesso!', 'success');
                    } else {
                        box.style.background = 'rgba(239, 68, 68, 0.12)';
                        box.style.color = '#ef4444';
                        box.style.border = '1px solid rgba(239, 68, 68, 0.35)';
                        box.innerHTML = '🔴 <strong>Erro:</strong> ' + data.result.message;
                        notify(data.result.message, 'error');
                    }
                } else {
                    notify((data && data.message) ? data.message : 'Falha no teste Stripe.', 'error');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.textContent = '⚡ Testar Chave Secreta do Stripe';
                notify('Erro de conexão ao testar Stripe: ' + err, 'error');
            });
        }
    </script>

</div>
