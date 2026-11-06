<?php
/**
 * View Administrativa: Lista de Cursos Academy
 *
 * @var array<int, array<string, mixed>> $courses
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 * @var \DomainSystem\Core\Contracts\PublicStorageInterface|null $storage
 */
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-graduation-cap" style="color:var(--accent-blue, #45f3ff);"></i> Academy (Cursos)
    </h1>
    <div style="display: flex; gap: 10px;">
        <a href="<?= $baseUrl ?>/admin/academy/students" class="btn" style="padding: 10px 18px; font-weight:600; text-decoration:none; background: rgba(69, 243, 255, 0.12); border: 1px solid var(--accent-blue, #45f3ff); color: var(--accent-blue, #45f3ff); border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fas fa-user-graduate"></i> Gestão de Alunos
        </a>
        <button onclick="document.getElementById('modalNewCourse').style.display='flex'" class="btn btn-activate" style="padding: 10px 20px; font-weight:600; cursor:pointer;">
            <i class="fas fa-plus"></i> Novo Curso
        </button>
    </div>
</div>

<?php if (!empty($flashMsg) && $flashType === 'error'): ?>
    <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars((string)$flashMsg) ?>
    </div>
<?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
    <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars((string)$flashMsg) ?>
    </div>
<?php endif; ?>

<div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; color: var(--text-main, #fff);">
        <thead>
            <tr style="background: rgba(0,0,0,0.25); border-bottom: 1px solid var(--border, rgba(255,255,255,0.1));">
                <th style="padding: 15px; text-align: left; width: 60px;">ID</th>
                <th style="padding: 15px; text-align: left; width: 100px;">Capa</th>
                <th style="padding: 15px; text-align: left;">Título</th>
                <th style="padding: 15px; text-align: left;">Slug</th>
                <th style="padding: 15px; text-align: left; width: 110px;">Preço</th>
                <th style="padding: 15px; text-align: left; width: 120px;">Status</th>
                <th style="padding: 15px; text-align: right; width: 220px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($courses)): ?>
                <tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted, #8c8f94);">Nenhum curso cadastrado ainda. Clique em "Novo Curso" para criar o primeiro.</td></tr>
            <?php else: ?>
                <?php foreach ($courses as $c): ?>
                    <tr style="border-bottom: 1px solid var(--border, rgba(255,255,255,0.06));">
                        <td style="padding: 15px; color: var(--text-muted, #8c8f94);">#<?= (int)$c['id'] ?></td>
                        <td style="padding: 15px;">
                            <?php if (!empty($c['thumbnail'])): ?>
                                <?php $thumbSrc = isset($storage) && $storage ? $storage->getPublicUrl((string)$c['thumbnail']) : $baseUrl . '/' . ltrim((string)$c['thumbnail'], '/'); ?>
                                <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1);">
                                    <img src="<?= htmlspecialchars($thumbSrc) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php else: ?>
                                <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; background: rgba(255,255,255,0.05); display: flex; align-items:center; justify-content:center; color: var(--text-muted, #8c8f94); font-size: 11px;">
                                    16:9
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; font-weight: 500;">
                            <?= htmlspecialchars((string)$c['title']) ?>
                            <?php if (!empty($c['link'])): ?>
                                <br><a href="<?= htmlspecialchars((string)$c['link']) ?>" target="_blank" style="color: var(--accent-blue, #45f3ff); font-size: 12px; text-decoration: none;">Link externo <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; color: var(--text-muted, #8c8f94); font-family: monospace; font-size: 13px;">
                            <?= htmlspecialchars((string)$c['slug']) ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php $price = (float)($c['price'] ?? 0.0); $isFree = ($price > 0.0) ? false : !empty($c['is_free']); ?>
                            <?php if ($isFree): ?>
                                <span style="background: rgba(69, 243, 255, 0.12); color:#45f3ff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(69, 243, 255, 0.3);">Gratuito</span>
                            <?php else: ?>
                                <span style="background: rgba(0, 210, 132, 0.15); color:#00d284; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:700; border: 1px solid rgba(0, 210, 132, 0.3);">
                                    R$ <?= number_format($price, 2, ',', '.') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php if ($c['status'] === 'published'): ?>
                                <span style="background: rgba(40,167,69,0.15); color:#28a745; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(40,167,69,0.3);">Publicado</span>
                            <?php elseif ($c['status'] === 'soon'): ?>
                                <span style="background: rgba(240,173,78,0.15); color:#f0ad4e; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(240,173,78,0.3);">Em Breve</span>
                            <?php else: ?>
                                <span style="background: rgba(255,255,255,0.08); color:#8c8f94; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(255,255,255,0.15);">Rascunho</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; vertical-align: middle;">
                                <a href="<?= $baseUrl ?>/admin/academy/edit/<?= (int)$c['id'] ?>" class="btn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px; height: 32px !important; padding: 0 14px !important; box-sizing: border-box !important; line-height: 1 !important; font-size: 11px !important; font-weight: 600; text-decoration: none; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 !important; vertical-align: middle !important;" title="Editar Curso">
                                    <i class="fas fa-edit" style="font-size: 11px;"></i>
                                    <span>Editar</span>
                                </a>
                                <form method="POST" action="<?= $baseUrl ?>/admin/academy/delete/<?= (int)$c['id'] ?>" class="ds-confirm-delete-form" data-confirm-title="<?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?>" style="display: inline-flex; margin: 0; padding: 0; vertical-align: middle; align-items: center;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" class="btn btn-deactivate" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px; height: 32px !important; padding: 0 14px !important; box-sizing: border-box !important; line-height: 1 !important; font-size: 11px !important; font-weight: 600; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 !important; vertical-align: middle !important;" title="Excluir Curso">
                                        <i class="fas fa-trash-alt" style="font-size: 11px;"></i>
                                        <span>Excluir</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.ds-confirm-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var title = form.getAttribute('data-confirm-title') || '';
        if (!confirm('Tem certeza que deseja excluir o curso "' + title + '"?')) {
            e.preventDefault();
        }
    });
});
</script>

<div style="margin-top: 30px; padding: 20px; background: rgba(69, 243, 255, 0.05); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 8px; color: var(--text-muted, #8c8f94); display:flex; align-items:flex-start; gap: 16px;">
    <i class="fas fa-info-circle" style="color: var(--accent-blue, #45f3ff); font-size: 24px; margin-top: 2px;"></i>
    <div>
        <strong style="color: #fff; font-size: 1.05rem; display:block; margin-bottom: 6px;">Como associar e exibir os cursos no site?</strong>
        <p style="margin: 0 0 10px 0; line-height: 1.6;">
            O Academy funciona como um catálogo visual e dinâmico de cursos. Para exibi-los em qualquer página (como a página pré-configurada <strong>Tutoriais</strong>), inclua o shortcode:
        </p>
        <div style="background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; font-family: monospace; color: #45f3ff;">
            <span>[hub_de_cursos]</span>
            <span style="color:#8c8f94; font-size:12px;">Parâmetros opcionais: limit="6" status="published"</span>
        </div>
        <div style="margin-top: 12px;">
            <a href="<?= $baseUrl ?>/tutoriais" target="_blank" style="color: #45f3ff; text-decoration: none; font-weight: 500;">
                <i class="fas fa-external-link-alt"></i> Visualizar Página de Tutoriais com a Grade &rarr;
            </a>
        </div>
    </div>
</div>

<!-- MODAL NOVO CURSO -->
<div id="modalNewCourse" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:var(--bg-panel, #1d2327); padding:30px; border-radius:12px; width:540px; max-width:90%; color:var(--text-main, #fff); border: 1px solid var(--border, rgba(255,255,255,0.1)); max-height:90vh; overflow-y:auto;">
        <h3 style="margin-top:0; color:#fff; display:flex; align-items:center; gap:10px;">
            <i class="fas fa-plus-circle" style="color:var(--accent-blue, #45f3ff);"></i> Cadastrar Novo Curso
        </h3>
        
        <form method="POST" action="<?= $baseUrl ?>/admin/academy/store" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:6px; font-weight:500;">Título do Curso *</label>
                <input type="text" name="title" required placeholder="Ex: Curso Completo de PHP 8.2 e Micro-Kernel" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
            </div>
            
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:6px; font-weight:500;">Descrição Curta</label>
                <textarea name="description" rows="3" placeholder="Resumo do conteúdo programático do curso..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;"></textarea>
            </div>

            <div style="margin-bottom:15px; display:flex; gap:15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:6px; font-weight:500;">Status</label>
                    <select name="status" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                        <option value="published" selected>Publicado</option>
                        <option value="soon">Em Breve</option>
                        <option value="draft">Rascunho</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:6px; font-weight:500;">Link de Destino</label>
                    <input type="text" name="link" placeholder="https://... ou /pagina" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                </div>
            </div>

            <!-- Preço de Venda, Parcelamento e Modo Gratuito / Combobox Oficial -->
            <div style="margin-bottom:15px; padding:16px; background:rgba(0,210,132,0.05); border:1px solid rgba(0,210,132,0.25); border-radius:8px;">
                <div style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap; margin-bottom:12px;">
                    <div style="flex:1; min-width:220px;">
                        <label style="display:block; margin-bottom:6px; font-weight:700; color:#45f3ff; font-size:13px;">
                            📌 Modalidade de Acesso do Curso
                        </label>
                        <select name="is_free" id="new_course_is_free" onchange="toggleNewCoursePricingMode(this.value)" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; font-weight:700; font-size:13px; box-sizing:border-box;">
                            <option value="1" selected>🟢 Gratuito (Acesso Livre / Sem Cobrança)</option>
                            <option value="0">💳 Pago (Venda com Checkout / Matrícula)</option>
                        </select>
                    </div>
                    <div id="new_box_price" style="flex:1; min-width:140px; display:none;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; color:#00d284; font-size:13px;">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" min="0" id="new_course_price" name="price" value="0.00" placeholder="10.00" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#00d284; font-weight:700; box-sizing:border-box;">
                    </div>
                    <div id="new_box_installments" style="flex:1; min-width:140px; display:none;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; color:#45f3ff; font-size:13px;">Máximo de Parcelas</label>
                        <select name="max_installments" id="new_course_installments" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                            <option value="1">1x à vista</option>
                            <option value="2">Até 2x</option>
                            <option value="3">Até 3x</option>
                            <option value="6">Até 6x</option>
                            <option value="10">Até 10x</option>
                            <option value="12" selected>Até 12x (Recomendado)</option>
                            <option value="18">Até 18x</option>
                            <option value="24">Até 24x</option>
                        </select>
                    </div>
                </div>
                <div id="new_box_free_notice" style="padding:10px 14px; background:rgba(69,243,255,0.06); border:1px solid rgba(69,243,255,0.2); border-radius:6px; font-size:12px; line-height:1.5; color:#cbd5e1;">
                    ℹ️ <strong>Curso Gratuito:</strong> Todos os visitantes e alunos cadastrados terão acesso livre e irrestrito a todas as aulas deste curso, sem necessidade de pagamento ou checkout.
                </div>
            </div>

            <!-- Integração com Plataformas Externas / Afiliados -->
            <div id="new_box_payment_options_wrapper" style="margin-bottom:15px; padding:14px; background:rgba(69,243,255,0.04); border:1px solid rgba(69,243,255,0.2); border-radius:6px; display:none;">
                <label style="display:block; margin-bottom:8px; font-weight:600; color:#45f3ff; font-size:13px;">
                    💳 Método de Cobrança &amp; Checkout (Gateways Nativos ou Plataformas de Afiliados)
                </label>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                    <div style="flex:1; min-width:240px;">
                        <label style="display:block; margin-bottom:4px; font-size:12px; color:#8c8f94;">Provedor de Checkout / Pagamento</label>
                        <select name="external_platform" id="new_course_platform_select" onchange="toggleNewCoursePlatform(this.value)" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box; font-size:13px;">
                            <optgroup label="💳 Checkout Nativo no seu Site (DomainCommerce)">
                                <option value="" selected>⭐ Checkout Multigateway Completo (Pix, Mercado Pago, Stripe &amp; PayPal)</option>
                                <option value="mercadopago">Mercado Pago Oficial (PIX Instantâneo, Cartão até 12x, Boleto &amp; Checkout Pro)</option>
                                <option value="stripe">Stripe Oficial (Cartão de Crédito Global, Apple Pay &amp; Google Pay)</option>
                                <option value="paypal">PayPal Oficial (Saldo &amp; Cartão Internacional)</option>
                            </optgroup>
                            <optgroup label="🚀 Plataformas de Afiliados Externas">
                                <option value="hotmart">Hotmart (Checkout Externo)</option>
                                <option value="eduzz">Eduzz (Checkout Externo)</option>
                                <option value="monetizze">Monetizze (Checkout Externo)</option>
                                <option value="clickbank">ClickBank (Checkout Internacional)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div id="new_box_external_product_id" style="flex:1; min-width:160px; display:none;">
                        <label style="display:block; margin-bottom:4px; font-size:12px; color:#8c8f94;">ID / Código do Produto</label>
                        <input type="text" name="external_product_id" placeholder="Ex: 1234567" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box; font-size:13px;">
                    </div>
                </div>
                <div id="new_box_external_checkout_url" style="margin-bottom:8px; display:none;">
                    <label style="display:block; margin-bottom:4px; font-size:12px; color:#8c8f94;">Link Direto de Checkout Externo</label>
                    <input type="text" name="external_checkout_url" placeholder="https://pay.hotmart.com/... ou https://sun.eduzz.com/..." style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box; font-size:13px;">
                </div>

                <div id="new_box_native_tip" style="padding:8px 12px; background:rgba(0,210,132,0.08); border:1px solid rgba(0,210,132,0.25); border-radius:6px; font-size:12px; line-height:1.4; color:#cbd5e1;">
                    💡 <strong>Checkout Nativo:</strong> O aluno compra diretamente no seu site com opções de <strong>PIX Instantâneo</strong>, <strong>Mercado Pago</strong>, <strong>Stripe</strong> ou <strong>PayPal</strong>. Todas as chaves e provedores ativos são configurados em <a href="<?= $baseUrl ?>/admin/commerce/settings" target="_blank" style="color:#00d284; font-weight:700; text-decoration:underline;">Admin &rarr; Commerce &rarr; Configurações</a>.
                </div>

                <!-- Box de Credenciais PayPal (Exibido ao escolher PayPal) -->
                <div id="new_box_paypal_settings" style="display:none; margin-top:12px; padding:14px; background:#0d1520; border:1px solid #38bdf8; border-radius:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:6px;">
                        <strong style="color:#38bdf8; font-size:13px;">🅿️ Chaves e Token da sua Conta PayPal</strong>
                        <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank" rel="noopener noreferrer" style="color:#38bdf8; font-size:11px; text-decoration:underline;">🔗 Abrir developer.paypal.com para copiar suas chaves &rarr;</a>
                    </div>
                    <p style="font-size:11.5px; color:#94a3b8; margin:0 0 10px 0; line-height:1.4;">
                        O PayPal utiliza o <strong>Client ID</strong> para identificar sua conta e o <strong>Client Secret</strong> (token seguro) para autorizar cobranças.
                    </p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:8px;">
                        <div>
                            <label style="display:block; font-size:11px; color:#94a3b8; margin-bottom:4px;">Client ID da sua Conta</label>
                            <input type="text" name="paypal_client_id" value="<?= htmlspecialchars((string)($commerceSettings['paypal_client_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="YS..." style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; font-family:monospace; font-size:12px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-size:11px; color:#94a3b8; margin-bottom:4px;">Client Secret (Token de Autorização)</label>
                            <input type="password" name="paypal_client_secret" value="<?= htmlspecialchars((string)($commerceSettings['paypal_client_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="E..." style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; font-family:monospace; font-size:12px; box-sizing:border-box;">
                        </div>
                    </div>
                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:#cbd5e1; cursor:pointer;">
                        <input type="checkbox" name="paypal_sandbox" value="1" <?= (!isset($commerceSettings['paypal_sandbox']) || !empty($commerceSettings['paypal_sandbox'])) ? 'checked' : '' ?>>
                        Modo Sandbox (Ambiente de Testes da PayPal)
                    </label>
                </div>

                <!-- Box de Credenciais Stripe (Exibido ao escolher Stripe) -->
                <div id="new_box_stripe_settings" style="display:none; margin-top:12px; padding:14px; background:#171124; border:1px solid #c084fc; border-radius:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:6px;">
                        <strong style="color:#c084fc; font-size:13px;">💳 Chaves e Token da sua Conta Stripe</strong>
                        <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener noreferrer" style="color:#c084fc; font-size:11px; text-decoration:underline;">🔗 Abrir dashboard.stripe.com/apikeys &rarr;</a>
                    </div>
                    <p style="font-size:11.5px; color:#94a3b8; margin:0 0 10px 0; line-height:1.4;">
                        O Stripe utiliza a <strong>Secret Key</strong> (token privado da sua conta) e a <strong>Publishable Key</strong> para aceitar cartões e Apple/Google Pay.
                    </p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:8px;">
                        <div>
                            <label style="display:block; font-size:11px; color:#94a3b8; margin-bottom:4px;">Secret Key (sk_test_... ou sk_live_...)</label>
                            <input type="password" name="stripe_secret_key" value="<?= htmlspecialchars((string)($commerceSettings['stripe_secret_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="sk_test_..." style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; font-family:monospace; font-size:12px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-size:11px; color:#94a3b8; margin-bottom:4px;">Publishable Key (pk_test_... ou pk_live_...)</label>
                            <input type="text" name="stripe_publishable_key" value="<?= htmlspecialchars((string)($commerceSettings['stripe_publishable_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="pk_test_..." style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; font-family:monospace; font-size:12px; box-sizing:border-box;">
                        </div>
                    </div>
                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:#cbd5e1; cursor:pointer;">
                        <input type="checkbox" name="stripe_sandbox" value="1" <?= !empty($commerceSettings['stripe_sandbox']) ? 'checked' : '' ?>>
                        Modo Teste (Test Mode do Stripe)
                    </label>
                </div>

                <script>
                    function toggleNewCoursePricingMode(val) {
                        const isFree = (val === '1' || val === 1);
                        const priceBox = document.getElementById('new_box_price');
                        const instBox = document.getElementById('new_box_installments');
                        const freeNotice = document.getElementById('new_box_free_notice');
                        const payWrapper = document.getElementById('new_box_payment_options_wrapper');
                        const priceInput = document.getElementById('new_course_price');

                        if (isFree) {
                            if (priceBox) priceBox.style.display = 'none';
                            if (instBox) instBox.style.display = 'none';
                            if (freeNotice) freeNotice.style.display = 'block';
                            if (payWrapper) payWrapper.style.display = 'none';
                            if (priceInput) priceInput.value = '0.00';
                        } else {
                            if (priceBox) priceBox.style.display = 'block';
                            if (instBox) instBox.style.display = 'block';
                            if (freeNotice) freeNotice.style.display = 'none';
                            if (payWrapper) payWrapper.style.display = 'block';
                            if (priceInput && (parseFloat(priceInput.value) <= 0 || priceInput.value === '')) {
                                priceInput.value = '10.00';
                            }
                        }
                    }

                    function toggleNewCoursePlatform(val) {
                        const isExt = ['hotmart', 'eduzz', 'monetizze', 'clickbank'].includes(val);
                        const isPp = (val === 'paypal');
                        const isStripe = (val === 'stripe');

                        const idB = document.getElementById('new_box_external_product_id');
                        const urlB = document.getElementById('new_box_external_checkout_url');
                        const tipB = document.getElementById('new_box_native_tip');
                        const ppBox = document.getElementById('new_box_paypal_settings');
                        const stripeBox = document.getElementById('new_box_stripe_settings');

                        if (idB) idB.style.display = isExt ? 'block' : 'none';
                        if (urlB) urlB.style.display = isExt ? 'block' : 'none';
                        if (ppBox) ppBox.style.display = isPp ? 'block' : 'none';
                        if (stripeBox) stripeBox.style.display = isStripe ? 'block' : 'none';

                        if (tipB) {
                            tipB.style.display = isExt ? 'none' : 'block';
                            if (val === 'stripe') {
                                tipB.innerHTML = '💡 <strong>Stripe Selecionado:</strong> O aluno pagará via Cartão de Crédito Global / Apple Pay diretamente pelo Stripe Checkout. Configure suas chaves acima ou em <a href="<?= $baseUrl ?>/admin/commerce/settings" target="_blank" style="color:#00d284; font-weight:700; text-decoration:underline;">Commerce &rarr; Configurações</a>.';
                            } else if (val === 'paypal') {
                                tipB.innerHTML = '💡 <strong>PayPal Selecionado:</strong> O aluno pagará com Saldo PayPal ou Cartão Internacional via API oficial v2 do PayPal. Configure suas chaves acima ou em <a href="<?= $baseUrl ?>/admin/commerce/settings" target="_blank" style="color:#00d284; font-weight:700; text-decoration:underline;">Commerce &rarr; Configurações</a>.';
                            } else if (val === 'mercadopago') {
                                tipB.innerHTML = '💡 <strong>Mercado Pago Selecionado:</strong> O aluno pagará com Pix Instantâneo (QR Code na tela), Cartão até 12x ou Boleto via Mercado Pago Oficial. Credenciais em <a href="<?= $baseUrl ?>/admin/commerce/settings" target="_blank" style="color:#00d284; font-weight:700; text-decoration:underline;">Commerce &rarr; Configurações</a>.';
                            } else {
                                tipB.innerHTML = '💡 <strong>Checkout Multigateway:</strong> O aluno terá na tela de compra as opções ativas: Pix Instantâneo, Mercado Pago, Stripe e PayPal. Credenciais em <a href="<?= $baseUrl ?>/admin/commerce/settings" target="_blank" style="color:#00d284; font-weight:700; text-decoration:underline;">Commerce &rarr; Configurações</a>.';
                            }
                        }
                    }
                </script>
            </div>

            <div style="margin-bottom:20px; padding:15px; background:rgba(0,0,0,0.25); border:1px solid #4a545a; border-radius:6px; box-sizing:border-box; width:100%; max-width:100%; overflow:hidden;">
                <label style="display:block; margin-bottom:6px; font-weight:500;">Imagem de Capa (Card)</label>
                <input type="file" id="course_new_thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="width:100%; max-width:100%; box-sizing:border-box; color:#8c8f94;">
                
                <div id="course_new_thumb_preview" style="display:none; margin-top:12px; align-items:center; gap:12px; flex-wrap:wrap;">
                    <div style="width:140px; aspect-ratio:16/9; border-radius:4px; overflow:hidden; background:#000; border:1px solid var(--accent-green, #00d284); flex-shrink:0;">
                        <img id="course_new_thumb_preview_img" src="" alt="Prévia" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <span style="font-size:12px; color:var(--accent-green, #00d284);">Nova imagem selecionada pronta para envio.</span>
                </div>

                <script>
                    document.getElementById('course_new_thumbnail')?.addEventListener('change', function(e) {
                        const file = e.target.files && e.target.files[0];
                        const previewBox = document.getElementById('course_new_thumb_preview');
                        const previewImg = document.getElementById('course_new_thumb_preview_img');
                        if (file && previewBox && previewImg) {
                            const reader = new FileReader();
                            reader.onload = function(ev) {
                                previewImg.src = ev.target.result;
                                previewBox.style.display = 'flex';
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                </script>

                <small style="display:block; margin-top:6px; color:#8c8f94; font-size:12px;">
                    Padrão exigido: proporção <strong>16:9</strong> (horizontal), mín. <strong>640x360 px</strong>, máx. <strong>2MB</strong> (JPG, PNG, WEBP).
                </small>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px;">
                <button type="button" onclick="document.getElementById('modalNewCourse').style.display='none'" class="btn btn-outline" style="padding:10px 18px; border:1px solid #4a545a; background:transparent; color:#8c8f94; border-radius:6px; cursor:pointer;">Cancelar</button>
                <button type="submit" class="btn btn-activate" style="padding:10px 20px; font-weight:600; cursor:pointer;">Salvar Curso</button>
            </div>
        </form>
    </div>
</div>
