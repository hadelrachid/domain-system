<?php
/**
 * Dashboard Administrativo de Vendas - DomainCommerce
 *
 * @var string $baseUrl
 * @var array<object> $gateways
 * @var array<array<string, mixed>> $orders
 * @var array<string, mixed> $metrics
 * @var array<string, mixed> $settings
 * @var string $csrfToken
 * @var string|null $flash
 * @var string|null $error
 */
$totalSales = (float)($metrics['total_sales'] ?? 0.0);
$paidCount = (int)($metrics['paid_count'] ?? 0);
$pendingCount = (int)($metrics['pending_count'] ?? 0);
$totalOrders = (int)($metrics['total_orders'] ?? 0);
$settings = $settings ?? [];
$selectedPeriod = (string)($selectedPeriod ?? '');
$availablePeriods = is_array($availablePeriods ?? null) ? $availablePeriods : [];
$salesTimeline = is_array($salesTimeline ?? null) ? $salesTimeline : ['labels' => [], 'totals' => [], 'counts' => [], 'mode' => 'months'];
$paymentBreakdown = is_array($paymentBreakdown ?? null) ? $paymentBreakdown : [];

$breakdownSum = 0.0;
foreach ($paymentBreakdown as $b) {
    $breakdownSum += (float)($b['total'] ?? 0.0);
}

$monthNamesPt = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

$selectedPeriodLabel = null;
if (!empty($selectedPeriod)) {
    $pParts = explode('-', $selectedPeriod);
    if (count($pParts) === 2 && isset($monthNamesPt[$pParts[1]])) {
        $selectedPeriodLabel = $monthNamesPt[$pParts[1]] . ' / ' . $pParts[0];
    } else {
        $selectedPeriodLabel = $selectedPeriod;
    }
}

// Mapeamento preciso de status
$gwStatusMap = [];
$activeCount = 0;

foreach ($gateways as $gw) {
    $id = $gw->getId();
    $configured = $gw->isConfigured();
    
    if ($id === 'mercadopago') {
        $enabled = !empty($settings['mercadopago_enabled']);
        $sandbox = !empty($settings['mercadopago_sandbox']) || (method_exists($gw, 'isSandbox') && $gw->isSandbox());
        if (!$enabled) {
            $status = ['text' => 'Desativado', 'type' => 'disabled', 'dot' => '○', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)'];
        } elseif (!$configured) {
            $status = ['text' => 'Falta Credencial', 'type' => 'warning', 'dot' => '⚠️', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#fbbf24', 'border' => 'rgba(245,158,11,0.3)'];
        } elseif ($sandbox) {
            $status = ['text' => 'Sandbox (Testes)', 'type' => 'sandbox', 'dot' => '●', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b', 'border' => 'rgba(245,158,11,0.3)'];
            $activeCount++;
        } else {
            $status = ['text' => 'Ativo (Produção)', 'type' => 'live', 'dot' => '●', 'bg' => 'rgba(0,210,132,0.14)', 'color' => '#00d284', 'border' => 'rgba(0,210,132,0.4)'];
            $activeCount++;
        }
    } elseif ($id === 'paypal') {
        $enabled = !empty($settings['paypal_enabled']);
        if (!$enabled) {
            $status = ['text' => 'Desativado', 'type' => 'disabled', 'dot' => '○', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)'];
        } elseif ($configured) {
            $status = ['text' => 'Ativo (Live)', 'type' => 'live', 'dot' => '●', 'bg' => 'rgba(0,210,132,0.14)', 'color' => '#00d284', 'border' => 'rgba(0,210,132,0.4)'];
            $activeCount++;
        } else {
            $status = ['text' => 'Chaves Pendentes', 'type' => 'warning', 'dot' => '⚠️', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#fbbf24', 'border' => 'rgba(245,158,11,0.3)'];
        }
    } elseif ($id === 'stripe') {
        $enabled = !empty($settings['stripe_enabled']);
        if (!$enabled) {
            $status = ['text' => 'Desativado', 'type' => 'disabled', 'dot' => '○', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)'];
        } elseif ($configured) {
            $status = ['text' => 'Ativo (Live)', 'type' => 'live', 'dot' => '●', 'bg' => 'rgba(0,210,132,0.14)', 'color' => '#00d284', 'border' => 'rgba(0,210,132,0.4)'];
            $activeCount++;
        } else {
            $status = ['text' => 'Chaves Pendentes', 'type' => 'warning', 'dot' => '⚠️', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#fbbf24', 'border' => 'rgba(245,158,11,0.3)'];
        }
    } else {
        $enabled = !empty($settings[$id . '_enabled']);
        if (!$enabled) {
            $status = ['text' => 'Inativo', 'type' => 'disabled', 'dot' => '○', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)'];
        } elseif ($configured) {
            $status = ['text' => 'Webhook Ativo', 'type' => 'live', 'dot' => '●', 'bg' => 'rgba(0,210,132,0.14)', 'color' => '#00d284', 'border' => 'rgba(0,210,132,0.4)'];
            $activeCount++;
        } else {
            $status = ['text' => 'Token Pendente', 'type' => 'warning', 'dot' => '⚠️', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#fbbf24', 'border' => 'rgba(245,158,11,0.3)'];
        }
    }
    
    $gwStatusMap[$id] = $status;
}
?>
<style>
@media (max-width: 992px) {
    .ds-charts-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
<div class="ds-commerce-dashboard" style="padding: 24px 28px; max-width: 1400px; margin: 0 auto; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif;">

    <!-- Cabeçalho Principal -->
    <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid rgba(255,255,255,0.07); padding-bottom: 22px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                <div style="background: linear-gradient(135deg, rgba(69,243,255,0.18), rgba(0,210,132,0.18)); border: 1px solid rgba(69,243,255,0.35); width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                    🛒
                </div>
                <div>
                    <h1 style="font-size: 1.85rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px; letter-spacing: -0.5px;">
                        DomainCommerce
                        <span style="font-size: 0.72rem; background: rgba(69, 243, 255, 0.12); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.35); padding: 3px 9px; border-radius: 9999px; font-weight: 700; letter-spacing: 0.5px;">v2.6.0</span>
                    </h1>
                </div>
            </div>
            <p style="color: #94a3b8; margin: 0; font-size: 0.95rem; line-height: 1.5;">
                Painel central de liquidação financeira: checkout seguro, Pix Instantâneo, cartões de crédito parcelados e webhooks.
            </p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <a href="<?= $baseUrl ?>/admin/commerce/export<?= !empty($selectedPeriod) ? '?period=' . urlencode($selectedPeriod) : '' ?>" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(0, 210, 132, 0.12); color: #00d284; border: 1px solid rgba(0, 210, 132, 0.35); padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 700; transition: all 0.2s;" onmouseover="this.style.background='rgba(0, 210, 132, 0.22)'" onmouseout="this.style.background='rgba(0, 210, 132, 0.12)'" title="Exportar dados para Excel / CSV">
                <span>📥</span> Exportar Balanço (CSV / Excel)
            </a>
            <a href="<?= $baseUrl ?>/admin/commerce/settings" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.06); color: #f8fafc; border: 1px solid rgba(255,255,255,0.18); padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                <span>⚙️</span> Configurar Gateways
            </a>
            <a href="<?= $baseUrl ?>/admin/academy" style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, rgba(69, 243, 255, 0.18), rgba(0, 210, 132, 0.18)); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.4); padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 700; transition: all 0.2s;" onmouseover="this.style.borderColor='#45f3ff'" onmouseout="this.style.borderColor='rgba(69, 243, 255, 0.4)'">
                <span>🎓</span> Cursos Academy
            </a>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
        <div style="background: rgba(0, 210, 132, 0.12); border: 1px solid rgba(0, 210, 132, 0.35); color: #00d284; padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.94rem; display: flex; align-items: center; gap: 10px;">
            <span>✓</span> <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: #ef4444; padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.94rem; display: flex; align-items: center; gap: 10px;">
            <span>⚠</span> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($selectedPeriodLabel)): ?>
        <div style="background: rgba(69, 243, 255, 0.08); border: 1px solid rgba(69, 243, 255, 0.3); color: #45f3ff; padding: 12px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 0.92rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>📅</span>
                <span>Faturamento e métricas filtrados para o mês de: <strong style="color: #fff; font-size: 0.96rem;"><?= htmlspecialchars($selectedPeriodLabel, ENT_QUOTES, 'UTF-8') ?></strong></span>
            </div>
            <a href="<?= $baseUrl ?>/admin/commerce" style="color: #cbd5e1; font-weight: 600; text-decoration: none; font-size: 0.84rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#cbd5e1'">
                ✕ Exibir Todo o Histórico
            </a>
        </div>
    <?php endif; ?>

    <!-- Métricas / KPIs Principais -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 18px; margin-bottom: 32px;">
        
        <!-- KPI 1: Faturamento Aprovado -->
        <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(0, 210, 132, 0.28); border-top: 3px solid #00d284; border-radius: 12px; padding: 22px 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">Faturamento Aprovado</span>
                <span style="font-size: 1.3rem;">💰</span>
            </div>
            <div style="font-size: 2.1rem; font-weight: 800; color: #00d284; letter-spacing: -0.5px;">
                R$ <?= number_format($totalSales, 2, ',', '.') ?>
            </div>
            <div style="font-size: 0.82rem; color: #64748b; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="color: #00d284;">●</span> Receitas liquidadas em tempo real
            </div>
        </div>

        <!-- KPI 2: Pedidos Concluídos -->
        <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(69, 243, 255, 0.28); border-top: 3px solid #45f3ff; border-radius: 12px; padding: 22px 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">Pedidos Concluídos</span>
                <span style="font-size: 1.3rem;">🎓</span>
            </div>
            <div style="font-size: 2.1rem; font-weight: 800; color: #45f3ff; letter-spacing: -0.5px;">
                <?= $paidCount ?> <span style="font-size: 1.05rem; font-weight: 500; color: #94a3b8;">/ <?= $totalOrders ?></span>
            </div>
            <div style="font-size: 0.82rem; color: #64748b; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="color: #45f3ff;">●</span> Matrículas liberadas com sucesso
            </div>
        </div>

        <!-- KPI 3: Aguardando Pagamento -->
        <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(245, 158, 11, 0.28); border-top: 3px solid #f59e0b; border-radius: 12px; padding: 22px 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">Aguardando Pagamento</span>
                <span style="font-size: 1.3rem;">⏳</span>
            </div>
            <div style="font-size: 2.1rem; font-weight: 800; color: #f59e0b; letter-spacing: -0.5px;">
                <?= $pendingCount ?>
            </div>
            <div style="font-size: 0.82rem; color: #64748b; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="color: #f59e0b;">●</span> Pix Instantâneo e Boletos pendentes
            </div>
        </div>

        <!-- KPI 4: Conectores Ativos -->
        <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(192, 132, 252, 0.28); border-top: 3px solid #c084fc; border-radius: 12px; padding: 22px 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">Conectores Ativos</span>
                <span style="font-size: 1.3rem;">⚡</span>
            </div>
            <div style="font-size: 2.1rem; font-weight: 800; color: #fff; letter-spacing: -0.5px;">
                <?= $activeCount ?> <span style="font-size: 1.05rem; font-weight: 500; color: #94a3b8;">/ <?= count($gateways) ?></span>
            </div>
            <div style="font-size: 0.82rem; color: #64748b; margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="color: #c084fc;">●</span> Conectores habilitados no sistema
            </div>
        </div>
    </div>

    <!-- SEÇÃO DE INTELIGÊNCIA FINANCEIRA & GRÁFICOS INTERATIVOS -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 34px;" class="ds-charts-grid">
        
        <!-- CARD GRÁFICO 1: EVOLUÇÃO DE FATURAMENTO -->
        <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(255,255,255,0.09); border-radius: 14px; padding: 22px 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span>📈</span> Evolução de Faturamento
                    </h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #94a3b8;">
                        <?= !empty($selectedPeriodLabel) ? 'Curva diária de receitas em ' . htmlspecialchars($selectedPeriodLabel, ENT_QUOTES, 'UTF-8') : 'Visão mensal das receitas consolidadas' ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.76rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Total no Período</span>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #00d284;">
                        R$ <?= number_format($totalSales, 2, ',', '.') ?>
                    </div>
                </div>
            </div>

            <div style="position: relative; width: 100%; min-height: 200px; height: 220px;">
                <canvas id="dsSalesChart" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>

        <!-- CARD GRÁFICO 2: MIX DE MEIOS DE PAGAMENTO -->
        <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(255,255,255,0.09); border-radius: 14px; padding: 22px 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between;">
            <div style="margin-bottom: 14px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🥧</span> Mix de Pagamentos
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #94a3b8;">
                    Distribuição das receitas por modalidade
                </p>
            </div>

            <?php if (empty($paymentBreakdown) || $breakdownSum <= 0): ?>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; color: #64748b; text-align: center;">
                    <span style="font-size: 2.2rem; margin-bottom: 8px;">💳</span>
                    <span style="font-size: 0.88rem; color: #94a3b8;">Sem vendas aprovadas no período</span>
                </div>
            <?php else: ?>
                <div style="position: relative; width: 100%; height: 130px; margin-bottom: 14px;">
                    <canvas id="dsPaymentMixChart" style="width: 100%; height: 100%;"></canvas>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($paymentBreakdown as $b): 
                        $pct = $breakdownSum > 0 ? round(($b['total'] / $breakdownSum) * 100, 1) : 0;
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: <?= $b['color'] ?>; display: inline-block;"></span>
                                <span style="color: #cbd5e1;"><?= htmlspecialchars($b['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div>
                                <strong style="color: #fff;">R$ <?= number_format($b['total'], 2, ',', '.') ?></strong>
                                <span style="color: #94a3b8; font-size: 0.76rem; margin-left: 4px;">(<?= $pct ?>%)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- SEÇÃO 1: GATEWAYS OFICIAIS DE CHECKOUT DIRETO (CARDS ALINHADOS COM ALTURAS PERFEITAS) -->
    <div style="margin-bottom: 34px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>💳</span> Gateways Oficiais de Pagamento Direto
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 0.88rem; color: #94a3b8;">
                    Processadores integrados com suporte a Pix Instantâneo, cartões de crédito parcelados e boletos.
                </p>
            </div>
            <a href="<?= $baseUrl ?>/admin/commerce/settings" style="font-size: 0.85rem; color: #45f3ff; text-decoration: none; font-weight: 700;">
                Gerenciar Chaves de API &rarr;
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; align-items: stretch;">
            
            <!-- CARD 1: MERCADO PAGO (OFICIAL & PRINCIPAL) -->
            <?php 
                $mpStatus = $gwStatusMap['mercadopago'] ?? ['text' => 'Desativado', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)', 'dot' => '○'];
                $isMpLive = ($mpStatus['color'] === '#00d284');
            ?>
            <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 2px solid <?= $isMpLive ? '#00d284' : 'rgba(255, 255, 255, 0.12)' ?>; border-radius: 14px; padding: 24px; box-shadow: <?= $isMpLive ? '0 8px 30px rgba(0, 210, 132, 0.12)' : '0 6px 24px rgba(0,0,0,0.3)' ?>; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-sizing: border-box; transition: transform 0.2s;">
                
                <div>
                    <!-- Topo do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(0, 210, 132, 0.12); border: 1px solid rgba(0, 210, 132, 0.35); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                🇧🇷
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0; line-height: 1.2;">Mercado Pago</h3>
                                    <span style="font-size: 0.68rem; background: rgba(0,210,132,0.15); color: #00d284; border: 1px solid rgba(0,210,132,0.35); padding: 2px 7px; border-radius: 4px; font-weight: 700; letter-spacing: 0.5px;">OFICIAL</span>
                                </div>
                                <span style="font-size: 0.78rem; color: #64748b; font-family: monospace;">ID: mercadopago</span>
                            </div>
                        </div>
                        
                        <!-- Status Pill com No-Wrap -->
                        <span style="white-space: nowrap; flex-shrink: 0; font-size: 0.8rem; font-weight: 700; background: <?= $mpStatus['bg'] ?>; color: <?= $mpStatus['color'] ?>; border: 1px solid <?= $mpStatus['border'] ?>; padding: 5px 12px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 6px;">
                            <span style="font-size: 0.85rem; line-height: 1;"><?= $mpStatus['dot'] ?></span> <?= $mpStatus['text'] ?>
                        </span>
                    </div>

                    <!-- Descrição Padronizada -->
                    <p style="font-size: 0.88rem; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.5; min-height: 42px;">
                        Motor nativo oficial com API pública: Pix instantâneo com liberação automática de matrículas, cartões de crédito em até 12x e boletos.
                    </p>

                    <!-- Tags de Recursos -->
                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px;">
                        <span style="font-size: 0.74rem; background: rgba(0, 210, 132, 0.12); color: #00d284; border: 1px solid rgba(0, 210, 132, 0.3); padding: 4px 9px; border-radius: 6px; font-weight: 600;">⚡ Pix Instantâneo</span>
                        <span style="font-size: 0.74rem; background: rgba(69, 243, 255, 0.1); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.25); padding: 4px 9px; border-radius: 6px; font-weight: 600;">💳 Cartão até 12x</span>
                        <span style="font-size: 0.74rem; background: rgba(255, 255, 255, 0.06); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); padding: 4px 9px; border-radius: 6px; font-weight: 600;">📄 Boleto Bancário</span>
                        <span style="font-size: 0.74rem; background: rgba(192, 132, 252, 0.1); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.25); padding: 4px 9px; border-radius: 6px; font-weight: 600;">🛡️ Checkout Pro</span>
                    </div>
                </div>

                <!-- Rodapé Alinhado Horizontalmente com Botão de Altura Exata -->
                <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">⚡ API REST Oficial</span>
                    <a href="<?= $baseUrl ?>/admin/commerce/settings" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 0.84rem; font-weight: 700; text-decoration: none; background: rgba(0, 210, 132, 0.15); color: #00d284; border: 1px solid rgba(0, 210, 132, 0.45); transition: all 0.2s;" onmouseover="this.style.background='#00d284'; this.style.color='#000';" onmouseout="this.style.background='rgba(0, 210, 132, 0.15)'; this.style.color='#00d284';">
                        Configurar Chaves &rarr;
                    </a>
                </div>
            </div>

            <!-- CARD 2: PAYPAL (GLOBAL) -->
            <?php 
                $ppStatus = $gwStatusMap['paypal'] ?? ['text' => 'Desativado', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)', 'dot' => '○'];
                $isPpLive = ($ppStatus['color'] === '#00d284');
            ?>
            <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid <?= $isPpLive ? 'rgba(69, 243, 255, 0.4)' : 'rgba(255, 255, 255, 0.12)' ?>; border-radius: 14px; padding: 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-sizing: border-box; transition: transform 0.2s;">
                
                <div>
                    <!-- Topo do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(69, 243, 255, 0.1); border: 1px solid rgba(69, 243, 255, 0.3); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                🌎
                            </div>
                            <div>
                                <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0; line-height: 1.2;">PayPal</h3>
                                <span style="font-size: 0.78rem; color: #64748b; font-family: monospace;">ID: paypal</span>
                            </div>
                        </div>
                        
                        <span style="white-space: nowrap; flex-shrink: 0; font-size: 0.8rem; font-weight: 700; background: <?= $ppStatus['bg'] ?>; color: <?= $ppStatus['color'] ?>; border: 1px solid <?= $ppStatus['border'] ?>; padding: 5px 12px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 6px;">
                            <span style="font-size: 0.85rem; line-height: 1;"><?= $ppStatus['dot'] ?></span> <?= $ppStatus['text'] ?>
                        </span>
                    </div>

                    <p style="font-size: 0.88rem; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.5; min-height: 42px;">
                        Processamento de vendas internacionais em moedas estrangeiras (USD, EUR), liquidação global e pagamentos diretos com saldo da conta PayPal.
                    </p>

                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px;">
                        <span style="font-size: 0.74rem; background: rgba(69, 243, 255, 0.1); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.25); padding: 4px 9px; border-radius: 6px; font-weight: 600;">🌎 Vendas Globais</span>
                        <span style="font-size: 0.74rem; background: rgba(255, 255, 255, 0.06); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); padding: 4px 9px; border-radius: 6px; font-weight: 600;">💵 Dólar / Euro</span>
                        <span style="font-size: 0.74rem; background: rgba(255, 255, 255, 0.06); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); padding: 4px 9px; border-radius: 6px; font-weight: 600;">💼 Saldo em Conta</span>
                    </div>
                </div>

                <!-- Rodapé Alinhado Horizontalmente com Botão de Altura Exata -->
                <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">🌎 Multi-Moeda</span>
                    <a href="<?= $baseUrl ?>/admin/commerce/settings" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 0.84rem; font-weight: 700; text-decoration: none; background: rgba(69, 243, 255, 0.12); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.35); transition: all 0.2s;" onmouseover="this.style.background='#45f3ff'; this.style.color='#000';" onmouseout="this.style.background='rgba(69, 243, 255, 0.12)'; this.style.color='#45f3ff';">
                        Configurar Chaves &rarr;
                    </a>
                </div>
            </div>

            <!-- CARD 3: STRIPE (INTERNACIONAL) -->
            <?php 
                $stripeStatus = $gwStatusMap['stripe'] ?? ['text' => 'Desativado', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)', 'dot' => '○'];
                $isStripeLive = ($stripeStatus['color'] === '#00d284');
            ?>
            <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid <?= $isStripeLive ? 'rgba(192, 132, 252, 0.4)' : 'rgba(255, 255, 255, 0.12)' ?>; border-radius: 14px; padding: 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-sizing: border-box; transition: transform 0.2s;">
                
                <div>
                    <!-- Topo do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(192, 132, 252, 0.1); border: 1px solid rgba(192, 132, 252, 0.3); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                                💳
                            </div>
                            <div>
                                <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0; line-height: 1.2;">Stripe</h3>
                                <span style="font-size: 0.78rem; color: #64748b; font-family: monospace;">ID: stripe</span>
                            </div>
                        </div>
                        
                        <span style="white-space: nowrap; flex-shrink: 0; font-size: 0.8rem; font-weight: 700; background: <?= $stripeStatus['bg'] ?>; color: <?= $stripeStatus['color'] ?>; border: 1px solid <?= $stripeStatus['border'] ?>; padding: 5px 12px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 6px;">
                            <span style="font-size: 0.85rem; line-height: 1;"><?= $stripeStatus['dot'] ?></span> <?= $stripeStatus['text'] ?>
                        </span>
                    </div>

                    <p style="font-size: 0.88rem; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.5; min-height: 42px;">
                        Infraestrutura global para cartões de crédito internacionais, carteiras Apple Pay / Google Pay e suporte futuro a assinaturas recorrentes.
                    </p>

                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px;">
                        <span style="font-size: 0.74rem; background: rgba(192, 132, 252, 0.1); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.25); padding: 4px 9px; border-radius: 6px; font-weight: 600;">💳 Cartões Globais</span>
                        <span style="font-size: 0.74rem; background: rgba(255, 255, 255, 0.06); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); padding: 4px 9px; border-radius: 6px; font-weight: 600;">📱 Apple Pay / Google Pay</span>
                        <span style="font-size: 0.74rem; background: rgba(255, 255, 255, 0.06); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); padding: 4px 9px; border-radius: 6px; font-weight: 600;">🔄 Recorrência</span>
                    </div>
                </div>

                <!-- Rodapé Alinhado Horizontalmente com Botão de Altura Exata -->
                <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">🛡️ Proteção Radar</span>
                    <a href="<?= $baseUrl ?>/admin/commerce/settings" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 0.84rem; font-weight: 700; text-decoration: none; background: rgba(192, 132, 252, 0.12); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.35); transition: all 0.2s;" onmouseover="this.style.background='#c084fc'; this.style.color='#000';" onmouseout="this.style.background='rgba(192, 132, 252, 0.12)'; this.style.color='#c084fc';">
                        Configurar Chaves &rarr;
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- SEÇÃO 2: PLATAFORMAS DE AFILIADOS & WEBHOOKS -->
    <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(245, 158, 11, 0.28); border-radius: 14px; padding: 24px; margin-bottom: 34px; box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #fbbf24; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🚀</span> Plataformas de Afiliados (Webhooks &amp; Auto-Matrícula)
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 0.88rem; color: #94a3b8;">
                    Integração passiva via Webhooks: ao vender em plataformas externas, o aluno e a matrícula são criados no mesmo segundo.
                </p>
            </div>
            <span style="font-size: 0.75rem; background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 12px; border-radius: 9999px; font-weight: 700;">
                AUTO-PROVISIONAMENTO
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; align-items: stretch;">
            
            <?php 
            $affiliates = [
                ['id' => 'hotmart', 'name' => 'Hotmart', 'icon' => '🔥', 'color' => '#f97316', 'sub' => 'Hottok Webhook'],
                ['id' => 'eduzz', 'name' => 'Eduzz (Nutror)', 'icon' => '🔷', 'color' => '#3b82f6', 'sub' => 'Postback Webhook'],
                ['id' => 'monetizze', 'name' => 'Monetizze', 'icon' => '🟢', 'color' => '#10b981', 'sub' => 'Chave Única Postback'],
                ['id' => 'clickbank', 'name' => 'ClickBank', 'icon' => '🟡', 'color' => '#eab308', 'sub' => 'INS Secret Notification'],
            ];

            foreach ($affiliates as $aff):
                $affStatus = $gwStatusMap[$aff['id']] ?? ['text' => 'Inativo', 'bg' => 'rgba(255,255,255,0.05)', 'color' => '#94a3b8', 'border' => 'rgba(255,255,255,0.12)', 'dot' => '○'];
            ?>
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-sizing: border-box; transition: border-color 0.2s;" onmouseover="this.style.borderColor='rgba(255,255,255,0.22)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.08)'">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 1.4rem;"><?= $aff['icon'] ?></span>
                                <div>
                                    <strong style="color: #fff; font-size: 0.98rem; display: block; line-height: 1.2;"><?= $aff['name'] ?></strong>
                                    <span style="font-size: 0.78rem; color: #64748b;"><?= $aff['sub'] ?></span>
                                </div>
                            </div>
                            <span style="white-space: nowrap; flex-shrink: 0; font-size: 0.75rem; font-weight: 700; background: <?= $affStatus['bg'] ?>; color: <?= $affStatus['color'] ?>; border: 1px solid <?= $affStatus['border'] ?>; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                <span style="font-size: 0.8rem; line-height: 1;"><?= $affStatus['dot'] ?></span> <?= $affStatus['text'] ?>
                            </span>
                        </div>
                    </div>

                    <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 12px; font-size: 0.8rem;">
                        <code style="color: #64748b; font-size: 0.76rem;">/webhook/payment/<?= $aff['id'] ?></code>
                        <a href="<?= $baseUrl ?>/admin/commerce/settings" style="color: <?= $aff['color'] ?>; font-weight: 700; text-decoration: none;">Configurar &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- SEÇÃO 3: TABELA GERAL DE PEDIDOS / TRANSAÇÕES RECENTES -->
    <div style="background: linear-gradient(180deg, #131a24 0%, #0d1218 100%); border: 1px solid rgba(255,255,255,0.09); border-radius: 14px; padding: 24px; box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid rgba(255,255,255,0.07); padding-bottom: 16px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>📋</span> Pedidos e Transações Recentes
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 0.88rem; color: #94a3b8;">
                    Histórico de compras e matrículas geradas no ecossistema de cursos.
                </p>
            </div>
            
            <!-- COMBOBOX DE FILTRO POR MÊS / PERÍODO -->
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <form method="GET" action="<?= $baseUrl ?>/admin/commerce" id="dsPeriodForm" style="display: inline-flex; align-items: center; gap: 8px; margin: 0;">
                    <label for="dsPeriodSelect" style="font-size: 0.82rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Mês:</label>
                    <div style="position: relative; display: inline-flex; align-items: center;">
                        <select name="period" id="dsPeriodSelect" onchange="document.getElementById('dsPeriodForm').submit()" style="background: #151c28; color: #f8fafc; border: 1px solid rgba(69,243,255,0.35); border-radius: 8px; padding: 7px 32px 7px 12px; font-size: 0.88rem; font-weight: 600; cursor: pointer; outline: none; appearance: none; -webkit-appearance: none; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                            <option value="" <?= empty($selectedPeriod) ? 'selected' : '' ?>>🌐 Todo o Histórico (Geral)</option>
                            <?php foreach ($availablePeriods as $p): 
                                $pParts = explode('-', (string)$p);
                                $pLabel = (count($pParts) === 2 && isset($monthNamesPt[$pParts[1]])) ? ($monthNamesPt[$pParts[1]] . ' / ' . $pParts[0]) : $p;
                            ?>
                                <option value="<?= htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedPeriod === $p ? 'selected' : '' ?>>
                                    📅 <?= htmlspecialchars($pLabel, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="position: absolute; right: 10px; pointer-events: none; color: #45f3ff; font-size: 0.72rem;">▼</span>
                    </div>
                </form>

                <?php if (!empty($selectedPeriod)): ?>
                    <a href="<?= $baseUrl ?>/admin/commerce" style="font-size: 0.8rem; color: #cbd5e1; text-decoration: none; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); padding: 7px 12px; border-radius: 6px; font-weight: 600;" title="Ver todos os meses">
                        ✕ Limpar
                    </a>
                <?php endif; ?>

                <a href="<?= $baseUrl ?>/admin/commerce/export<?= !empty($selectedPeriod) ? '?period=' . urlencode($selectedPeriod) : '' ?>" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(0, 210, 132, 0.12); color: #00d284; border: 1px solid rgba(0, 210, 132, 0.35); padding: 7px 14px; border-radius: 8px; text-decoration: none; font-size: 0.84rem; font-weight: 700; transition: all 0.2s;" onmouseover="this.style.background='rgba(0, 210, 132, 0.22)'" onmouseout="this.style.background='rgba(0, 210, 132, 0.12)'" title="Baixar relatório em formato CSV compatível com Excel">
                    <span>📥</span> <?= !empty($selectedPeriodLabel) ? 'Baixar ' . htmlspecialchars($selectedPeriodLabel) : 'Baixar Todos (CSV)' ?>
                </a>

                <a href="<?= $baseUrl ?>/admin/commerce/export?year=<?= date('Y') ?>" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.06); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.15); padding: 7px 14px; border-radius: 8px; text-decoration: none; font-size: 0.84rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#cbd5e1'" title="Exportar balanço fiscal anual completo">
                    <span>📅</span> Balanço <?= date('Y') ?>
                </a>

                <div style="font-size: 0.85rem; color: #94a3b8; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); padding: 7px 14px; border-radius: 20px;">
                    Exibindo: <strong style="color: #fff;"><?= count($orders) ?></strong> pedido(s)
                </div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 56px 20px; color: #64748b;">
                <div style="font-size: 3rem; margin-bottom: 14px;">🛒</div>
                <div style="font-size: 1.15rem; color: #e2e8f0; font-weight: 700;">
                    <?= !empty($selectedPeriodLabel) ? 'Nenhum pedido registrado em ' . htmlspecialchars($selectedPeriodLabel) : 'Nenhum pedido registrado ainda' ?>
                </div>
                <p style="font-size: 0.9rem; max-width: 480px; margin: 8px auto 0 auto; line-height: 1.5; color: #94a3b8;">
                    <?= !empty($selectedPeriodLabel) 
                        ? 'Não encontramos transações para o mês selecionado. Você pode escolher outro mês no combobox acima ou clicar em "Limpar" para exibir todo o histórico.' 
                        : 'Assim que seus alunos realizarem checkouts via Pix, Cartão de Crédito ou Boleto, os pedidos aparecerão aqui instantaneamente.' ?>
                </p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.12); color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.6px;">
                            <th style="padding: 14px 16px;">Ref. / Pedido</th>
                            <th style="padding: 14px 16px;">Aluno / Comprador</th>
                            <th style="padding: 14px 16px;">Item Comprado</th>
                            <th style="padding: 14px 16px;">Valor</th>
                            <th style="padding: 14px 16px;">Gateway &amp; Método</th>
                            <th style="padding: 14px 16px;">Status</th>
                            <th style="padding: 14px 16px;">Data &amp; Hora</th>
                            <th style="padding: 14px 16px; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $status = (string)($order['status'] ?? 'pending');
                            
                            $badge = match($status) {
                                'paid' => [
                                    'label'  => 'Pago / Aprovado',
                                    'icon'   => '🟢',
                                    'bg'     => 'rgba(0, 210, 132, 0.12)',
                                    'text'   => '#00d284',
                                    'border' => 'rgba(0, 210, 132, 0.35)',
                                ],
                                'pending' => [
                                    'label'  => 'Aguardando Pagamento',
                                    'icon'   => '🟡',
                                    'bg'     => 'rgba(245, 158, 11, 0.12)',
                                    'text'   => '#f59e0b',
                                    'border' => 'rgba(245, 158, 11, 0.35)',
                                ],
                                'refunded' => [
                                    'label'  => 'Reembolsado',
                                    'icon'   => '🟣',
                                    'bg'     => 'rgba(192, 132, 252, 0.12)',
                                    'text'   => '#c084fc',
                                    'border' => 'rgba(192, 132, 252, 0.35)',
                                ],
                                'cancelled' => [
                                    'label'  => 'Cancelado',
                                    'icon'   => '🔴',
                                    'bg'     => 'rgba(239, 68, 68, 0.12)',
                                    'text'   => '#ef4444',
                                    'border' => 'rgba(239, 68, 68, 0.35)',
                                ],
                                default => [
                                    'label'  => ucfirst($status),
                                    'icon'   => '○',
                                    'bg'     => 'rgba(255, 255, 255, 0.08)',
                                    'text'   => '#cbd5e1',
                                    'border' => 'rgba(255, 255, 255, 0.15)',
                                ],
                            };

                            $methodRaw = (string)($order['payment_method'] ?? '');
                            $methodBadge = match($methodRaw) {
                                'pix'          => ['name' => 'Pix Instantâneo', 'color' => '#00d284'],
                                'credit_card'  => ['name' => 'Cartão de Crédito', 'color' => '#45f3ff'],
                                'boleto'       => ['name' => 'Boleto Bancário', 'color' => '#cbd5e1'],
                                'checkout_pro' => ['name' => 'Checkout Pro', 'color' => '#c084fc'],
                                default        => ['name' => ucfirst($methodRaw ?: 'Padrão'), 'color' => '#94a3b8'],
                            };

                            $studentName = !empty($order['user_name']) ? (string)$order['user_name'] : null;
                            $studentEmail = !empty($order['user_email']) ? (string)$order['user_email'] : null;
                        ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                
                                <td style="padding: 16px; font-weight: 700; color: #fff;">
                                    <code style="color: #45f3ff; background: rgba(69,243,255,0.08); padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; border: 1px solid rgba(69,243,255,0.2);">
                                        <?= htmlspecialchars((string)$order['order_ref'], ENT_QUOTES, 'UTF-8') ?>
                                    </code>
                                </td>

                                <td style="padding: 16px;">
                                    <?php if ($studentName): ?>
                                        <div style="font-weight: 700; color: #fff; font-size: 0.94rem;">
                                            <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if ($studentEmail): ?>
                                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                                <?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.88rem;">Aluno #<?= (int)$order['user_id'] ?></span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding: 16px; color: #e2e8f0;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 6px; font-size: 0.84rem; font-weight: 600;">
                                        🎓 <?= htmlspecialchars(ucfirst((string)$order['item_type']), ENT_QUOTES, 'UTF-8') ?> #<?= (int)$order['item_id'] ?>
                                    </span>
                                </td>

                                <td style="padding: 16px; font-weight: 800; color: #00d284; font-size: 1.05rem;">
                                    R$ <?= number_format((float)$order['total'], 2, ',', '.') ?>
                                </td>

                                <td style="padding: 16px;">
                                    <div style="font-weight: 600; color: #fff; text-transform: capitalize; font-size: 0.9rem;">
                                        <?= htmlspecialchars((string)$order['gateway'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="font-size: 0.78rem; color: <?= $methodBadge['color'] ?>; margin-top: 3px; font-weight: 600;">
                                        ● <?= htmlspecialchars($methodBadge['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>

                                <td style="padding: 16px;">
                                    <span style="white-space: nowrap; background: <?= $badge['bg'] ?>; color: <?= $badge['text'] ?>; border: 1px solid <?= $badge['border'] ?>; padding: 4px 12px; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                        <span><?= $badge['icon'] ?></span> <?= htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td style="padding: 16px; font-size: 0.84rem; color: #64748b; white-space: nowrap;">
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$order['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td style="padding: 16px; text-align: right; white-space: nowrap;">
                                    <?php if ($status === 'pending'): ?>
                                        <button type="button" onclick="window.dsConfirmApproval(<?= (int)$order['id'] ?>, '<?= htmlspecialchars((string)$order['order_ref'], ENT_QUOTES, 'UTF-8') ?>')" style="background: rgba(0, 210, 132, 0.15); color: #00d284; border: 1px solid rgba(0, 210, 132, 0.45); padding: 6px 14px; border-radius: 6px; font-size: 0.82rem; cursor: pointer; font-weight: 700; transition: all 0.2s;" onmouseover="this.style.background='#00d284'; this.style.color='#000';" onmouseout="this.style.background='rgba(0, 210, 132, 0.15)'; this.style.color='#00d284';">
                                            ✓ Aprovar
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.84rem; font-weight: 600;">Concluído</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal de Confirmação Inline Elegante -->
<div id="dsApprovalModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #0f141c; border: 1px solid rgba(69,243,255,0.3); border-radius: 14px; max-width: 480px; width: 100%; padding: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); color: #fff;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(0,210,132,0.15); border: 1px solid rgba(0,210,132,0.3); display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                ✓
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Aprovar Pedido Manualmente</h3>
                <span id="dsModalOrderRef" style="font-size: 0.82rem; color: #45f3ff; font-family: monospace;"></span>
            </div>
        </div>
        <p style="color: #94a3b8; font-size: 0.92rem; line-height: 1.5; margin: 0 0 24px 0;">
            Ao confirmar a aprovação, o status deste pedido mudará para <strong style="color: #00d284;">Pago</strong> e a matrícula do aluno será <strong style="color: #fff;">automaticamente liberada</strong> no Academy.
        </p>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="window.dsCloseApprovalModal()" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                Cancelar
            </button>
            <button type="button" id="dsModalConfirmBtn" style="background: linear-gradient(135deg, #00d284, #00b370); border: none; color: #000; padding: 10px 22px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; cursor: pointer; box-shadow: 0 4px 15px rgba(0,210,132,0.35);">
                Confirmar e Liberar
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var activeOrderId = null;

    function notify(msg, type) {
        if (window.OS && typeof window.OS.notify === 'function') {
            window.OS.notify(msg, type);
        } else if (window.DS && window.DS.ui && typeof window.DS.ui.toast === 'function') {
            window.DS.ui.toast(msg, type);
        } else {
            console.log('[' + type + '] ' + msg);
        }
    }

    window.dsConfirmApproval = function(orderId, orderRef) {
        activeOrderId = orderId;
        var modal = document.getElementById('dsApprovalModal');
        var refElem = document.getElementById('dsModalOrderRef');
        if (refElem) refElem.textContent = 'Pedido: ' + orderRef;
        if (modal) modal.style.display = 'flex';
    };

    window.dsCloseApprovalModal = function() {
        activeOrderId = null;
        var modal = document.getElementById('dsApprovalModal');
        if (modal) modal.style.display = 'none';
    };

    var confirmBtn = document.getElementById('dsModalConfirmBtn');
    if (confirmBtn) {
        confirmBtn.onclick = function() {
            if (!activeOrderId) return;
            var orderId = activeOrderId;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Aprovando...';

            var formData = new FormData();
            formData.append('status', 'paid');
            formData.append('csrf_token', <?= json_encode($csrfToken) ?>);

            fetch(<?= json_encode($baseUrl) ?> + '/admin/commerce/orders/' + orderId + '/status', {
                method: 'POST',
                headers: { 'X-CSRF-Token': <?= json_encode($csrfToken) ?> },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmar e Liberar';
                window.dsCloseApprovalModal();

                if (data && data.success) {
                    notify('Pedido #' + orderId + ' aprovado e matrícula liberada com sucesso!', 'success');
                    setTimeout(function() { window.location.reload(); }, 1000);
                } else {
                    notify('Erro: ' + (data && data.message ? data.message : 'Falha ao atualizar pedido.'), 'error');
                }
            })
            .catch(function(err) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmar e Liberar';
                window.dsCloseApprovalModal();
                notify('Erro de conexão ao aprovar pedido.', 'error');
            });
        };
    }

    // Renderização dos Gráficos com Chart.js
    var timelineData = <?= json_encode($salesTimeline, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var breakdownData = <?= json_encode($paymentBreakdown, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    function initCharts() {
        if (typeof Chart === 'undefined') return;

        // 1. Gráfico de Linha (Faturamento)
        var salesCtx = document.getElementById('dsSalesChart');
        if (salesCtx && timelineData && timelineData.labels) {
            var ctx = salesCtx.getContext('2d');
            var gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(0, 210, 132, 0.28)');
            gradient.addColorStop(1, 'rgba(0, 210, 132, 0.01)');

            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: timelineData.labels,
                    datasets: [{
                        label: 'Faturamento (R$)',
                        data: timelineData.totals,
                        borderColor: '#00d284',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#00d284',
                        pointBorderColor: '#0f141c',
                        pointBorderWidth: 2,
                        pointRadius: timelineData.labels.length > 20 ? 2 : 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f8fafc',
                            bodyColor: '#00d284',
                            borderColor: 'rgba(0, 210, 132, 0.3)',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    var val = Number(context.raw || 0);
                                    return ' R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)', borderColor: 'transparent' },
                            ticks: { color: '#64748b', font: { size: 11, family: 'system-ui' } }
                        },
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)', borderColor: 'transparent' },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11, family: 'system-ui' },
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Gráfico Donut (Mix de Pagamentos)
        var mixCtx = document.getElementById('dsPaymentMixChart');
        if (mixCtx && breakdownData && Object.keys(breakdownData).length > 0) {
            var mixLabels = [];
            var mixTotals = [];
            var mixColors = [];

            for (var key in breakdownData) {
                if (breakdownData.hasOwnProperty(key)) {
                    mixLabels.push(breakdownData[key].label);
                    mixTotals.push(breakdownData[key].total);
                    mixColors.push(breakdownData[key].color);
                }
            }

            new Chart(mixCtx, {
                type: 'doughnut',
                data: {
                    labels: mixLabels,
                    datasets: [{
                        data: mixTotals,
                        backgroundColor: mixColors,
                        borderWidth: 2,
                        borderColor: '#0f141c',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f8fafc',
                            borderColor: 'rgba(255, 255, 255, 0.15)',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    var val = Number(context.raw || 0);
                                    return ' ' + context.label + ': R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    if (typeof Chart !== 'undefined') {
        initCharts();
    } else {
        var chartScript = document.createElement('script');
        chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        chartScript.async = true;
        chartScript.onload = initCharts;
        document.head.appendChild(chartScript);
    }
})();
</script>
