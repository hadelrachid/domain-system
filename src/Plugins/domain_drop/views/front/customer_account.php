<?php
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$customerName = (string)($customer['name'] ?? 'Cliente');
$customerEmail = (string)($customer['email'] ?? '');
$trackQuery = (string)($trackQuery ?? '');
$trackedOrder = $trackedOrder ?? null;
$trackedCodeOnly = (string)($trackedCodeOnly ?? '');
$sdkEvents = is_array($sdkEvents ?? null) ? $sdkEvents : [];
$sdkOfficialUrl = (string)($sdkOfficialUrl ?? '');

$statusStepsMap = [
    'pending'    => ['label' => 'Aguardando Pagamento / Processamento', 'step' => 1, 'color' => '#fbbf24'],
    'paid'       => ['label' => 'Pagamento Confirmado • Em Curadoria', 'step' => 2, 'color' => '#38bdf8'],
    'processing' => ['label' => 'Em Separação no Fornecedor', 'step' => 3, 'color' => '#60a5fa'],
    'shipped'    => ['label' => 'Enviado • Código de Rastreio Gerado', 'step' => 4, 'color' => '#45f3ff'],
    'delivered'  => ['label' => 'Entregue ao Destinatário', 'step' => 5, 'color' => '#00d284'],
    'cancelled'  => ['label' => 'Pedido Cancelado / Estornado', 'step' => 0, 'color' => '#f87171'],
];

$reasonLabels = [
    'duvida_entrega'     => 'Dúvida sobre Prazo / Rastreio / Entrega',
    'observacao_pedido'  => 'Observação / Instrução sobre o Pedido',
    'duvida_produto'     => 'Dúvida sobre Funcionamento / Garantia',
    'produto_danificado' => 'Reclamação: Produto com Dano / Avaria',
    'entrega_atrasada'   => 'Reclamação: Entrega Atrasada / Extravio',
    'item_errado'        => 'Reclamação: Item Errado / Peça Faltando',
    'arrependimento'     => 'Devolução Grátis / Garantia 7 a 15 dias',
    'outro'              => 'Outro Assunto / Solicitação',
];
?>
<div style="max-width: 1120px; margin: 40px auto; padding: 0 18px; color: #e2e8f0;">
    <!-- Topo da Conta -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; background: linear-gradient(145deg, #101724 0%, #0b0f17 100%); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 16px; padding: 24px 28px; margin-bottom: 24px;">
        <div>
            <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.8px; color: #F0D060; text-transform: uppercase;">Espaço do Cliente • Rastreio, Pedidos &amp; Atendimento</span>
            <h1 style="margin: 6px 0 4px 0; font-size: 24px; font-weight: 800; color: #fff;">Olá, <?= $e($customerName) ?></h1>
            <p style="margin: 0; font-size: 13px; color: #94a3b8;">E-mail: <?= $e($customerEmail) ?></p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="#rastreio-cliente" style="padding: 10px 16px; border-radius: 9px; background: rgba(69,243,255,0.12); border: 1px solid rgba(69,243,255,0.35); color: #45f3ff; font-weight: 700; text-decoration: none; font-size: 13px;">🛰️ Rastrear Encomenda</a>
            <a href="<?= $e($baseUrl) ?>/loja" style="padding: 10px 16px; border-radius: 9px; background: rgba(212,175,55,0.14); border: 1px solid rgba(212,175,55,0.4); color: #F0D060; font-weight: 700; text-decoration: none; font-size: 13px;">🛍️ Continuar Comprando</a>
            <a href="<?= $e($baseUrl) ?>/sair" style="padding: 10px 16px; border-radius: 9px; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: #fca5a5; font-weight: 700; text-decoration: none; font-size: 13px;">Sair</a>
        </div>
    </div>

    <?php if (!empty($flash['msg'])): ?>
        <?php $isErr = ($flash['type'] ?? '') === 'error'; ?>
        <div style="margin-bottom: 20px; padding: 14px 18px; border-radius: 10px; font-size: 13.5px; background: <?= $isErr ? 'rgba(239,68,68,0.14)' : 'rgba(16,185,129,0.14)' ?>; border: 1px solid <?= $isErr ? 'rgba(239,68,68,0.45)' : 'rgba(16,185,129,0.45)' ?>; color: <?= $isErr ? '#fca5a5' : '#6ee7b7' ?>;">
            <?= $e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- BLOCO INTEGRADO: ACOMPANHAMENTO & RASTREIO DO PRODUTO DENTRO DA ÁREA DO CLIENTE -->
    <div id="rastreio-cliente" style="background: linear-gradient(145deg, rgba(15, 23, 36, 0.96) 0%, rgba(9, 13, 22, 0.98) 100%); border: 1px solid rgba(69, 243, 255, 0.3); border-radius: 14px; padding: 24px; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
            <div>
                <span style="display: inline-block; padding: 3px 10px; border-radius: 99px; background: rgba(69,243,255,0.12); border: 1px solid rgba(69,243,255,0.35); color: #45f3ff; font-size: 11px; font-weight: 700; margin-bottom: 6px;">
                    🛰️ Rastreamento Integrado (Correios + Cainiao / AliExpress SDK)
                </span>
                <h2 style="margin: 0; font-size: 19px; font-weight: 800; color: #fff;">Acompanhamento de Entrega</h2>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #94a3b8;">
                    Consulte o andamento do seu pedido ou informe um código de rastreio diretamente aqui na sua conta.
                </p>
            </div>

            <form method="GET" action="<?= $e($baseUrl) ?>/minha-conta#rastreio-cliente" style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1; max-width: 440px;">
                <input type="text" name="rastrear" value="<?= $e($trackQuery) ?>" placeholder="Código do pedido (ORD-...) ou rastreio (NM...BR)"
                       style="flex: 1; min-width: 200px; padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(69,243,255,0.35); background: #06090e; color: #fff; font-size: 13px;">
                <button type="submit" style="padding: 10px 16px; border: none; border-radius: 8px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 13px; cursor: pointer;">
                    🔍 Rastrear
                </button>
            </form>
        </div>

        <?php if ($trackedOrder !== null): ?>
            <?php
            $tStatus = strtolower((string)($trackedOrder['status'] ?? 'pending'));
            $tMeta = $statusStepsMap[$tStatus] ?? ['label' => strtoupper($tStatus), 'step' => 1, 'color' => '#94a3b8'];
            $tStep = (int)$tMeta['step'];
            $tCode = trim((string)($trackedOrder['tracking_code'] ?? ''));
            $tRef = (string)($trackedOrder['order_ref'] ?? '');
            ?>
            <div style="background: rgba(0,0,0,0.38); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 14px;">
                    <div>
                        <span style="font-size: 11.5px; color: #94a3b8;">Pedido em Acompanhamento:</span>
                        <strong style="font-size: 15px; color: #F0D060; font-family: monospace; margin-left: 6px;"><?= $e($tRef) ?></strong>
                        <?php if (!empty($trackedOrder['product_title'])): ?>
                            <span style="font-size: 13px; color: #cbd5e1; margin-left: 8px;">— <?= $e($trackedOrder['product_title']) ?></span>
                        <?php endif; ?>
                    </div>
                    <span style="padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; color: <?= $tMeta['color'] ?>; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12);">
                        ● <?= $e($tMeta['label']) ?>
                    </span>
                </div>

                <!-- 5 Etapas Visuais do Rastreio -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-bottom: 14px;">
                    <?php
                    $stepTitles = [
                        1 => '1. Pedido Registrado',
                        2 => '2. Pago & Validado',
                        3 => '3. Separação Fornecedor',
                        4 => '4. Em Transporte',
                        5 => '5. Entregue',
                    ];
                    foreach ($stepTitles as $idx => $sTitle):
                        $done = $tStep >= $idx;
                    ?>
                        <div style="padding: 10px 8px; border-radius: 8px; text-align: center; font-size: 12px; font-weight: 700; border: 1px solid <?= $done ? 'rgba(0,210,132,0.4)' : 'rgba(255,255,255,0.08)' ?>; background: <?= $done ? 'rgba(0,210,132,0.1)' : 'rgba(255,255,255,0.02)' ?>; color: <?= $done ? '#6ee7b7' : '#64748b' ?>;">
                            <?= $e($sTitle) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 13px;">
                        <span style="color: #94a3b8;">Código de Rastreio:</span>
                        <?php if ($tCode !== ''): ?>
                            <strong style="color: #45f3ff; font-family: monospace; margin-left: 6px;"><?= $e($tCode) ?></strong>
                        <?php else: ?>
                            <span style="color: #fbbf24; margin-left: 6px;">Aguardando emissão da etiqueta (24h a 48h úteis)</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($tCode !== ''): ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="https://rastreamento.correios.com.br/app/index.php?objetos=<?= rawurlencode($tCode) ?>" target="_blank" rel="noopener noreferrer"
                               style="padding: 8px 14px; border-radius: 8px; background: rgba(212,175,55,0.14); border: 1px solid rgba(212,175,55,0.45); color: #F0D060; font-size: 12px; font-weight: 700; text-decoration: none;">
                                📮 Rastrear nos Correios ↗
                            </a>
                            <a href="https://global.cainiao.com/detail.htm?mailNoList=<?= rawurlencode($tCode) ?>" target="_blank" rel="noopener noreferrer"
                               style="padding: 8px 14px; border-radius: 8px; background: rgba(69,243,255,0.1); border: 1px solid rgba(69,243,255,0.35); color: #45f3ff; font-size: 12px; font-weight: 700; text-decoration: none;">
                                🌐 Rastrear via Cainiao / AliExpress ↗
                            </a>
                            <?php if ($sdkOfficialUrl !== ''): ?>
                                <a href="<?= $e($sdkOfficialUrl) ?>" target="_blank" rel="noopener noreferrer"
                                   style="padding: 8px 14px; border-radius: 8px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.18); color: #e2e8f0; font-size: 12px; font-weight: 700; text-decoration: none;">
                                    🔗 Portal Transportadora ↗
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($sdkEvents)): ?>
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.08);">
                        <div style="font-size: 12px; font-weight: 700; color: #45f3ff; margin-bottom: 8px;">🛰️ Eventos de Movimentação (SDK Logístico):</div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <?php foreach ($sdkEvents as $ev): ?>
                                <div style="padding: 8px 10px; border-radius: 6px; background: rgba(255,255,255,0.03); border-left: 3px solid #45f3ff; font-size: 12px;">
                                    <span style="color: #94a3b8;"><?= $e($ev['event_date'] ?? '') ?></span> —
                                    <strong style="color: #fff;"><?= $e($ev['event_desc'] ?? '') ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($trackedCodeOnly !== ''): ?>
            <div style="background: rgba(0,0,0,0.38); border: 1px solid rgba(69,243,255,0.35); border-radius: 12px; padding: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <div style="font-size: 13px; color: #fff; font-weight: 700;">Código consultado: <span style="color: #45f3ff; font-family: monospace;"><?= $e($trackedCodeOnly) ?></span></div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Consulte diretamente o objeto nos Correios ou na rede logística internacional Cainiao:</div>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="https://rastreamento.correios.com.br/app/index.php?objetos=<?= rawurlencode($trackedCodeOnly) ?>" target="_blank" rel="noopener noreferrer"
                       style="padding: 8px 14px; border-radius: 8px; background: rgba(212,175,55,0.14); border: 1px solid rgba(212,175,55,0.45); color: #F0D060; font-size: 12px; font-weight: 700; text-decoration: none;">
                        📮 Consultar nos Correios ↗
                    </a>
                    <a href="https://global.cainiao.com/detail.htm?mailNoList=<?= rawurlencode($trackedCodeOnly) ?>" target="_blank" rel="noopener noreferrer"
                       style="padding: 8px 14px; border-radius: 8px; background: rgba(69,243,255,0.1); border: 1px solid rgba(69,243,255,0.35); color: #45f3ff; font-size: 12px; font-weight: 700; text-decoration: none;">
                        🌐 Consultar na Cainiao ↗
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div style="padding: 16px; border-radius: 10px; background: rgba(0,0,0,0.28); border: 1px dashed rgba(255,255,255,0.12); color: #94a3b8; font-size: 13px;">
                Assim que você realizar um pedido, o passo a passo de entrega e o código de rastreio aparecerão automaticamente aqui. Você também pode digitar qualquer código no campo acima para consultar.
            </div>
        <?php endif; ?>
    </div>

    <!-- Grid Principal: Meus Pedidos + Atendimento (Reclamações, Observações e Dúvidas) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; align-items: start;">
        <!-- Coluna 1: Meus Pedidos -->
        <div style="background: rgba(15, 23, 36, 0.9); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px;">
            <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 800; color: #fff;">📦 Meus Pedidos (<?= count($orders ?? []) ?>)</h2>

            <?php if (empty($orders)): ?>
                <div style="padding: 28px 18px; text-align: center; background: rgba(0,0,0,0.28); border-radius: 10px; border: 1px dashed rgba(255,255,255,0.12);">
                    <p style="margin: 0 0 12px 0; color: #94a3b8; font-size: 13.5px;">Nenhum pedido vinculado a <?= $e($customerEmail) ?> até o momento.</p>
                    <a href="<?= $e($baseUrl) ?>/loja" style="display: inline-block; padding: 9px 16px; border-radius: 8px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; text-decoration: none; font-size: 13px;">Explorar Ofertas</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($orders as $ord): ?>
                        <?php
                        $sla = is_array($ord['sla'] ?? null) ? $ord['sla'] : [];
                        $barColor = match ($sla['tone'] ?? 'info') {
                            'success' => '#10b981',
                            'danger'  => '#ef4444',
                            'warning' => '#f59e0b',
                            'amber'   => '#f59e0b',
                            default   => '#3b82f6',
                        };
                        $oRef = (string)($ord['order_ref'] ?? '');
                        ?>
                        <div style="padding: 16px; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <strong style="color: #F0D060; font-family: monospace; font-size: 14px;"><?= $e($oRef) ?></strong>
                                <span style="font-size: 12px; font-weight: 700; color: #5cd67d;">R$ <?= number_format((float)($ord['total_paid_brl'] ?? 0), 2, ',', '.') ?></span>
                            </div>

                            <?php if (!empty($ord['product_title'])): ?>
                                <div style="font-size: 13px; color: #e2e8f0; margin-top: 4px;"><?= $e($ord['product_title']) ?></div>
                            <?php endif; ?>

                            <div style="margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: #cbd5e1; margin-bottom: 5px;">
                                    <span><?= $e($sla['label'] ?? 'Em processamento') ?></span>
                                    <span><?= (int)($sla['expected_days'] ?? 7) ?> dias SLA</span>
                                </div>
                                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.08); border-radius: 99px; overflow: hidden;">
                                    <div style="width: <?= (int)($sla['bar_percent'] ?? 0) ?>%; height: 100%; background: <?= $barColor ?>; transition: width 0.3s ease;"></div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                                <span style="font-size: 11.5px; color: #94a3b8;">
                                    Rastreio: <?= !empty($ord['tracking_code']) ? $e($ord['tracking_code']) : 'Em preparação' ?>
                                </span>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <a href="<?= $e($baseUrl) ?>/minha-conta?rastrear=<?= $e(rawurlencode($oRef)) ?>#rastreio-cliente" style="font-size: 12px; font-weight: 700; color: #45f3ff; text-decoration: none; padding: 5px 10px; border-radius: 6px; border: 1px solid rgba(69,243,255,0.35); background: rgba(69,243,255,0.1);">
                                        🛰️ Rastrear
                                    </a>
                                    <a href="<?= $e($baseUrl) ?>/minha-conta?pedido=<?= $e(rawurlencode($oRef)) ?>#form-reclamacao" style="font-size: 12px; font-weight: 700; color: #fca5a5; text-decoration: none; padding: 5px 10px; border-radius: 6px; border: 1px solid rgba(239,68,68,0.35); background: rgba(239,68,68,0.1);">
                                        💬 Dúvida / Reclamar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Coluna 2: Formulário de Reclamação, Observações ou Dúvidas -->
        <div id="form-reclamacao" style="background: rgba(15, 23, 36, 0.9); border: 1px solid rgba(212, 175, 55, 0.25); border-radius: 14px; padding: 24px;">
            <h2 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: #fff;">💬 Reclamações, Observações &amp; Dúvidas</h2>
            <p style="margin: 0 0 18px 0; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                Envie dúvidas sobre entrega, observações sobre seu pedido ou abra uma reclamação/devolução anexando fotos ou PDF (até 4 arquivos de 5 MB cada).
            </p>

            <form method="POST" action="<?= $e($baseUrl) ?>/minha-conta/reclamar" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken ?? '') ?>">

                <div>
                    <label for="rec-order" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">1. Pedido da Sua Conta (Opcional)</label>
                    <select id="rec-order" name="order_ref" style="width: 100%; box-sizing: border-box; padding: 11px 12px; background: #090d14; border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 13.5px;">
                        <option value="GERAL" <?= empty($orders) || ($selectedOrderRef ?? '') === '' || ($selectedOrderRef ?? '') === 'GERAL' ? 'selected' : '' ?>>
                            💬 Sem pedido listado na conta (Informar código ou produto abaixo opcionalmente)
                        </option>
                        <?php foreach ($orders ?? [] as $ord): ?>
                            <?php $ref = (string)($ord['order_ref'] ?? ''); ?>
                            <option value="<?= $e($ref) ?>" <?= ($selectedOrderRef ?? '') === $ref ? 'selected' : '' ?>>
                                📦 <?= $e($ref) ?> — R$ <?= number_format((float)($ord['total_paid_brl'] ?? 0), 2, ',', '.') ?> (<?= $e($ord['status'] ?? 'paid') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Vinculação Opcional de Código de Pedido/Rastreio Manual e Produto da Loja -->
                <div style="padding: 12px; border-radius: 10px; background: rgba(0,0,0,0.28); border: 1px solid rgba(69,243,255,0.2); display: flex; flex-direction: column; gap: 12px;">
                    <div style="font-size: 11.5px; color: #45f3ff; font-weight: 700;">
                        🔗 Vincular Compra ou Produto Manualmente (Opcional — ajuda a identificar o item ou ordem)
                    </div>

                    <div>
                        <label for="rec-manual-order" style="display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 5px;">
                            Código da Ordem / Pedido ou Rastreio (Opcional)
                        </label>
                        <input id="rec-manual-order" type="text" name="manual_order_ref" maxlength="60"
                               placeholder="Ex: ORD-2026... ou rastreio NM123456789BR (deixe em branco se não tiver)"
                               style="width: 100%; box-sizing: border-box; padding: 10px 12px; background: #090d14; border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 13px;">
                    </div>

                    <div>
                        <label for="rec-product" style="display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 5px;">
                            Produto da Loja Relacionado (Opcional)
                        </label>
                        <select id="rec-product" name="catalog_product_id" style="width: 100%; box-sizing: border-box; padding: 10px 12px; background: #090d14; border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 13px;">
                            <option value="0">-- Nenhum produto específico / Não sei informar --</option>
                            <?php foreach ($catalogProducts ?? [] as $prodDto): ?>
                                <?php $pId = (int)($prodDto->id ?? 0); ?>
                                <?php if ($pId > 0): ?>
                                    <option value="<?= $pId ?>" <?= ((int)($selectedProductId ?? 0)) === $pId ? 'selected' : '' ?>>
                                        #<?= $pId ?> — <?= $e(mb_strimwidth($prodDto->title, 0, 58, '...')) ?> (R$ <?= number_format($prodDto->salePriceBrl, 2, ',', '.') ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="rec-reason" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Tipo de Atendimento / Motivo *</label>
                    <select id="rec-reason" name="reason" required style="width: 100%; box-sizing: border-box; padding: 11px 12px; background: #090d14; border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 13.5px;">
                        <optgroup label="Dúvidas &amp; Observações">
                            <option value="duvida_entrega">Dúvida sobre Prazo, Rastreio ou Entrega</option>
                            <option value="observacao_pedido">Observação ou Instrução sobre o Pedido</option>
                            <option value="duvida_produto">Dúvida sobre Funcionamento / Garantia do Produto</option>
                        </optgroup>
                        <optgroup label="Reclamações &amp; Devoluções">
                            <option value="produto_danificado" <?= !empty($orders) ? 'selected' : '' ?>>Produto chegou com dano / avaria</option>
                            <option value="entrega_atrasada">Entrega atrasada / pacote perdido</option>
                            <option value="item_errado">Item errado ou com peças faltando</option>
                            <option value="arrependimento">Devolução grátis / Garantia 7 a 15 dias</option>
                            <option value="outro">Outro assunto / Solicitação</option>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label for="rec-desc" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Mensagem, Observação ou Relato *</label>
                    <textarea id="rec-desc" name="description" rows="4" required minlength="10" placeholder="Escreva sua dúvida, observação ou explique detalhadamente o problema encontrado..." style="width: 100%; box-sizing: border-box; padding: 11px 12px; background: #090d14; border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; color: #fff; font-size: 13.5px; resize: vertical;"></textarea>
                </div>

                <div>
                    <label for="rec-files" style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px;">Anexar Fotos ou Comprovante PDF (opcional, máx. 4 arquivos)</label>
                    <input id="rec-files" type="file" name="evidences[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" style="width: 100%; box-sizing: border-box; padding: 10px; background: rgba(0,0,0,0.4); border: 1px dashed rgba(212,175,55,0.45); border-radius: 8px; color: #cbd5e1; font-size: 12.5px;">
                </div>

                <button type="submit" style="padding: 13px 18px; border: none; border-radius: 9px; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; font-weight: 800; font-size: 14px; cursor: pointer;">
                    Enviar Solicitação ao Atendimento ➔
                </button>
            </form>
        </div>
    </div>

    <!-- Histórico de Reclamações, Observações e Dúvidas -->
    <?php if (!empty($disputes)): ?>
        <div style="margin-top: 28px; background: rgba(15, 23, 36, 0.9); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px;">
            <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 800; color: #fff;">📋 Meus Chamados, Observações &amp; Reclamações</h2>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($disputes as $disp): ?>
                    <?php
                    $rKey = (string)($disp['reason'] ?? 'outro');
                    $rLabel = $reasonLabels[$rKey] ?? $rKey;
                    ?>
                    <div style="padding: 14px 16px; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <div>
                                <strong style="color: #F0D060; font-family: monospace;">Pedido: <?= $e($disp['order_ref'] ?? '') ?></strong>
                                <span style="font-size: 12px; color: #94a3b8; margin-left: 8px;">• <?= $e($rLabel) ?></span>
                            </div>
                            <span style="font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 99px; background: <?= ($disp['mediation_status'] ?? 'open') === 'sent' ? 'rgba(16,185,129,0.16)' : 'rgba(245,158,11,0.16)' ?>; color: <?= ($disp['mediation_status'] ?? 'open') === 'sent' ? '#6ee7b7' : '#fcd34d' ?>;">
                                <?= ($disp['mediation_status'] ?? 'open') === 'sent' ? 'Respondido / Acionado Fornecedor' : 'Em Atendimento' ?>
                            </span>
                        </div>
                        <p style="margin: 8px 0; font-size: 13px; color: #cbd5e1;"><?= nl2br($e($disp['description'] ?? '')) ?></p>
                        <?php if (!empty($disp['attachments'])): ?>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                                <?php foreach ($disp['attachments'] as $att): ?>
                                    <a href="<?= $e($baseUrl) ?>/dropship/disputes/attachment/<?= (int)($disp['id'] ?? 0) ?>/<?= $e(rawurlencode((string)($att['filename'] ?? ''))) ?>" target="_blank" style="font-size: 11.5px; color: #93c5fd; background: rgba(59,130,246,0.14); border: 1px solid rgba(59,130,246,0.35); padding: 4px 10px; border-radius: 6px; text-decoration: none;">
                                        📎 <?= $e($att['original_name'] ?? $att['filename'] ?? 'Anexo') ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
