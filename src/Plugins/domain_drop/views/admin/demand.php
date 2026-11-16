<?php
/**
 * View Administrativa: Inteligência de Demanda, Gráfico em Tempo Real e Livro de Presença Mensal
 */
$selectedPeriod = $selectedPeriod ?? date('Y-m');
$livePulse = $livePulse ?? [];
$rankings = $rankings ?? [];
$categoryProfile = $categoryProfile ?? [];
$recentActivities = $recentActivities ?? [];
$baseUrl = $baseUrl ?? '';

$currentYear = (int)date('Y');
$previousYear = $currentYear - 1;
$monthsNames = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>
<div class="wrap" style="max-width: 1380px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">

    <!-- Cabeçalho -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 24px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span style="font-size: 1.8rem;">📊</span>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px;">Inteligência de Demanda &amp; Livro de Presença</h1>
                <span style="background: rgba(69,243,255,0.12); color: #45f3ff; border: 1px solid rgba(69,243,255,0.3); font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 999px;">
                    LIVE AUDIT
                </span>
            </div>
            <p style="color: #94a3b8; margin: 0; font-size: 0.88rem;">
                Auditor autônomo de cliques, intenção de compra de fundo de funil e mapa de interesses para o ciclo de 24h da loja.
            </p>
        </div>

        <!-- Seletor de Período Mensal (Combobox Automático: Ano Vigente e Anterior) -->
        <form method="GET" action="<?= htmlspecialchars($baseUrl . '/admin/dropship/demand') ?>" style="display: flex; align-items: center; gap: 8px;">
            <label for="period" style="font-size: 0.84rem; color: #cbd5e1; font-weight: 600;">Mês de Fechamento:</label>
            <select id="period" name="period" onchange="this.form.submit()" style="background: #0b1120; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 7px 12px; border-radius: 8px; font-size: 0.88rem; outline: none; cursor: pointer; font-weight: 600;">
                <optgroup label="Ano Vigente (<?= $currentYear ?>)" style="background: #1e293b; color: #38bdf8;">
                    <?php for ($m = 12; $m >= 1; $m--): 
                        $val = sprintf('%04d-%02d', $currentYear, $m);
                        $isCurrentMonth = ($val === date('Y-m'));
                    ?>
                        <option value="<?= $val ?>" <?= $selectedPeriod === $val ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">
                            <?= $monthsNames[$m] ?> / <?= $currentYear ?><?= $isCurrentMonth ? ' (Mês Atual)' : '' ?>
                        </option>
                    <?php endfor; ?>
                </optgroup>
                <optgroup label="Ano Anterior (<?= $previousYear ?>)" style="background: #1e293b; color: #94a3b8;">
                    <?php for ($m = 12; $m >= 1; $m--): 
                        $val = sprintf('%04d-%02d', $previousYear, $m);
                    ?>
                        <option value="<?= $val ?>" <?= $selectedPeriod === $val ? 'selected' : '' ?> style="background: #0f172a; color: #fff;">
                            <?= $monthsNames[$m] ?> / <?= $previousYear ?>
                        </option>
                    <?php endfor; ?>
                </optgroup>
            </select>
            <button type="submit" style="background: #3b82f6; color: #fff; border: none; padding: 7px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; cursor: pointer;">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Cards de Pulso em Tempo Real -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; position: relative; overflow: hidden;">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px;">Produtos com Engajamento</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-top: 6px;"><?= number_format((int)($livePulse['active_products_count'] ?? 0)) ?></div>
            <div style="font-size: 0.74rem; color: #45f3ff; margin-top: 4px;">Na vitrine ativa</div>
        </div>

        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; position: relative; overflow: hidden;">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px;">Curiosidade (Ver Detalhes)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #38bdf8; margin-top: 6px;"><?= number_format((int)($livePulse['total_detail_clicks'] ?? 0)) ?></div>
            <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px;">Cliques no card/abertura de página</div>
        </div>

        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; position: relative; overflow: hidden; border-left: 4px solid #10b981;">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px;">Intenção Máxima (Comprar)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 6px;"><?= number_format((int)($livePulse['total_buy_clicks'] ?? 0)) ?></div>
            <div style="font-size: 0.74rem; color: #34d399; margin-top: 4px;">Fundo de funil / Ida ao checkout</div>
        </div>

        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; position: relative; overflow: hidden;">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px;">Vendas Concluídas</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin-top: 6px;"><?= number_format((int)($livePulse['total_conversions'] ?? 0)) ?></div>
            <div style="font-size: 0.74rem; color: #fbbf24; margin-top: 4px;">Pagamentos confirmados</div>
        </div>

        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; position: relative; overflow: hidden; background: linear-gradient(135deg, rgba(69,243,255,0.08), rgba(99,102,241,0.08));">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: #cbd5e1; font-weight: 700; letter-spacing: 0.5px;">Score Geral de Demanda</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #45f3ff; margin-top: 6px;"><?= number_format((int)($livePulse['total_demand_score'] ?? 0)) ?></div>
            <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px;">(Conv × 10) + (Buy × 4) + (Ver × 1)</div>
        </div>
    </div>

    <!-- Seção de Gráficos de Colunas em Tempo Real -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
        
        <!-- Gráfico de Colunas: Top Produtos Mais Clicados -->
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>📊</span> Gráfico de Colunas: Produtos Mais Quentes (Tempo Real)
                </h3>
                <span style="font-size: 0.78rem; color: #94a3b8;">Base: <?= htmlspecialchars($selectedPeriod) ?></span>
            </div>

            <?php if (empty($livePulse['top_products'])): ?>
                <div style="padding: 40px; text-align: center; color: #94a3b8; font-size: 0.9rem;">
                    Nenhum clique registrado no período ainda. Os dados surgem em tempo real conforme os visitantes interagem na vitrine.
                </div>
            <?php else: ?>
                <?php
                $maxScore = 1;
                foreach ($livePulse['top_products'] as $p) {
                    if ((int)$p['demand_score'] > $maxScore) {
                        $maxScore = (int)$p['demand_score'];
                    }
                }
                ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($livePulse['top_products'] as $item): ?>
                        <?php
                        $score = (int)$item['demand_score'];
                        $pct = min(100, max(6, round(($score / $maxScore) * 100)));
                        ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 0.82rem; margin-bottom: 5px;">
                                <span style="font-weight: 600; color: #e2e8f0; max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($item['product_title']) ?>
                                </span>
                                <span style="color: #45f3ff; font-weight: 700;">
                                    <?= $score ?> pts 
                                    <span style="color: #94a3b8; font-weight: 400; font-size: 0.76rem;">(<?= (int)$item['buy_clicks'] ?> comprar / <?= (int)$item['detail_clicks'] ?> detalhes)</span>
                                </span>
                            </div>
                            <!-- Barra de Progresso / Coluna Horizontal -->
                            <div style="height: 14px; background: #0f172a; border-radius: 999px; overflow: hidden; display: flex; border: 1px solid rgba(255,255,255,0.06);">
                                <div style="width: <?= $pct ?>%; background: linear-gradient(90deg, #38bdf8, #10b981); border-radius: 999px; transition: width 0.4s ease;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Perfil de Categorias da Audiência -->
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                <span>🎯</span> Perfil dos Compradores
            </h3>
            <p style="font-size: 0.8rem; color: #94a3b8; margin: 0 0 16px 0; line-height: 1.4;">
                Revela em quais categorias o público da sua loja tem maior tendência de compra no mês.
            </p>

            <?php if (empty($categoryProfile)): ?>
                <div style="padding: 30px; text-align: center; color: #94a3b8; font-size: 0.85rem;">
                    Aguardando interações da audiência.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($categoryProfile as $catSlug => $cData): ?>
                        <div style="background: #0f172a; padding: 12px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.04); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-weight: 700; color: #fff; text-transform: capitalize; font-size: 0.88rem;"><?= htmlspecialchars($catSlug) ?></span>
                                <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 2px;">
                                    <?= (int)$cData['buy_clicks'] ?> intenções de compra • <?= (int)$cData['detail_clicks'] ?> visitas
                                </div>
                            </div>
                            <span style="font-size: 0.95rem; font-weight: 800; color: #10b981;">
                                <?= (int)$cData['score'] ?> pts
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabela: Livro de Presença e Fechamento Mensal -->
    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>📖</span> Livro de Presença de Produtos: Auditoria do Mês (<?= htmlspecialchars($selectedPeriod) ?>)
                </h3>
                <span style="font-size: 0.82rem; color: #94a3b8;">
                    Ranking dos itens com maior demanda. Estes produtos são os candidatos ideais para compra em estoque próprio e vagas cativas no ciclo de 24h.
                </span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.86rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.5px;">
                        <th style="padding: 12px 14px;">Posição</th>
                        <th style="padding: 12px 14px;">Produto / SKU</th>
                        <th style="padding: 12px 14px;">Categoria</th>
                        <th style="padding: 12px 14px; text-align: center;">Curiosidade (Detalhes)</th>
                        <th style="padding: 12px 14px; text-align: center;">Fundo de Funil (Comprar)</th>
                        <th style="padding: 12px 14px; text-align: center;">Vendas Reais</th>
                        <th style="padding: 12px 14px; text-align: center;">Taxa Intenção (CTR)</th>
                        <th style="padding: 12px 14px; text-align: right;">Demand Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rankings)): ?>
                        <tr>
                            <td colspan="8" style="padding: 30px; text-align: center; color: #94a3b8;">
                                Nenhum registro localizado para o período <?= htmlspecialchars($selectedPeriod) ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $pos = 1; foreach ($rankings as $row): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 14px; font-weight: 800; color: <?= $pos <= 3 ? '#45f3ff' : '#94a3b8' ?>;">
                                    #<?= $pos ?> <?= $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : '')) ?>
                                </td>
                                <td style="padding: 14px;">
                                    <div style="font-weight: 700; color: #fff; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($row['product_title']) ?>
                                    </div>
                                    <span style="font-size: 0.72rem; color: #64748b; font-family: monospace;">ID: <?= htmlspecialchars($row['product_id']) ?></span>
                                </td>
                                <td style="padding: 14px;">
                                    <span style="background: rgba(255,255,255,0.06); padding: 3px 8px; border-radius: 6px; font-size: 0.74rem; text-transform: capitalize; color: #cbd5e1;">
                                        <?= htmlspecialchars($row['category_slug'] ?? 'geral') ?>
                                    </span>
                                </td>
                                <td style="padding: 14px; text-align: center; color: #38bdf8; font-weight: 600;">
                                    <?= number_format((int)$row['detail_clicks']) ?>
                                </td>
                                <td style="padding: 14px; text-align: center; color: #10b981; font-weight: 700;">
                                    <?= number_format((int)$row['buy_clicks']) ?>
                                </td>
                                <td style="padding: 14px; text-align: center; color: #f59e0b; font-weight: 700;">
                                    <?= number_format((int)$row['conversions']) ?>
                                </td>
                                <td style="padding: 14px; text-align: center; font-weight: 600; color: #a855f7;">
                                    <?= number_format((float)($row['buy_intent_ratio'] ?? 0), 1) ?>%
                                </td>
                                <td style="padding: 14px; text-align: right;">
                                    <span style="background: rgba(69,243,255,0.1); border: 1px solid rgba(69,243,255,0.3); color: #45f3ff; font-weight: 800; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem;">
                                        <?= number_format((int)$row['demand_score']) ?> pts
                                    </span>
                                </td>
                            </tr>
                        <?php $pos++; endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabela: Trilha de Acessos Recentes & Identificação de Visitantes (Leads & E-mail Marketing) -->
    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px; margin-top: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>🕵️</span> Trilha de Interações Recentes: Identificação de Usuários &amp; Leads
                </h3>
                <span style="font-size: 0.82rem; color: #94a3b8;">
                    Monitoramento individual de quem clicou em <strong>Ver Detalhes</strong> ou <strong>Comprar</strong>. Discrimina clientes cadastrados para ações diretas de E-mail Marketing vs visitantes externos anônimos.
                </span>
            </div>
            <span style="background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25); font-size: 0.74rem; font-weight: 700; padding: 4px 10px; border-radius: 999px;">
                Últimos <?= count($recentActivities) ?> eventos
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.86rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.5px;">
                        <th style="padding: 12px 14px;">Data / Hora</th>
                        <th style="padding: 12px 14px;">Ação</th>
                        <th style="padding: 12px 14px;">Produto / Categoria</th>
                        <th style="padding: 12px 14px;">Perfil do Usuário</th>
                        <th style="padding: 12px 14px;">Identificação (Nome / E-mail)</th>
                        <th style="padding: 12px 14px; text-align: right;">Ação de Marketing</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivities)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                Nenhuma interação recente capturada ainda. Conforme os visitantes navegarem ou comprarem, o histórico aparecerá aqui.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $act): ?>
                            <?php 
                                $isLogged = ($act['user_type'] ?? '') === 'cliente_logado';
                                $actType = $act['action_type'] ?? '';
                            ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 14px; font-family: monospace; font-size: 0.8rem; color: #cbd5e1; white-space: nowrap;">
                                    <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($act['created_at'] ?? 'now'))) ?>
                                </td>
                                <td style="padding: 14px; white-space: nowrap;">
                                    <?php if ($actType === 'buy_click'): ?>
                                        <span style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                            🛒 Intenção de Compra
                                        </span>
                                    <?php elseif ($actType === 'conversion'): ?>
                                        <span style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                            💰 Venda Confirmada
                                        </span>
                                    <?php else: ?>
                                        <span style="background: rgba(56,189,248,0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                            👁️ Ver Detalhes
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px;">
                                    <div style="font-weight: 600; color: #fff; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($act['product_title'] ?? 'Sem título') ?>
                                    </div>
                                    <span style="font-size: 0.72rem; color: #64748b; text-transform: uppercase;">
                                        <?= htmlspecialchars($act['category_slug'] ?? 'geral') ?> • SKU: <?= htmlspecialchars($act['product_id'] ?? '') ?>
                                    </span>
                                </td>
                                <td style="padding: 14px; white-space: nowrap;">
                                    <?php if ($isLogged): ?>
                                        <span style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); padding: 3px 9px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                            <span>🟢</span> Cliente Cadastrado
                                        </span>
                                    <?php else: ?>
                                        <span style="background: rgba(148,163,184,0.15); color: #94a3b8; border: 1px solid rgba(148,163,184,0.25); padding: 3px 9px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                            <span>⚪</span> Visitante Externo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px;">
                                    <?php if ($isLogged && !empty($act['customer_email'])): ?>
                                        <div style="font-weight: 700; color: #e2e8f0;">
                                            <?= htmlspecialchars($act['customer_name'] ?: 'Cliente Cadastrado') ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #38bdf8; font-family: monospace;">
                                            <?= htmlspecialchars($act['customer_email']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #64748b; font-size: 0.8rem; font-style: italic;">
                                            Anônimo (Tráfego da Loja)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px; text-align: right; white-space: nowrap;">
                                    <?php if ($isLogged && !empty($act['customer_email'])): ?>
                                        <?php 
                                            $subject = 'Vimos seu interesse em: ' . ($act['product_title'] ?? 'produto da nossa loja');
                                            $body = "Olá " . ($act['customer_name'] ?: 'cliente') . ",\n\nNotamos seu interesse no produto '" . ($act['product_title'] ?? '') . "'. Temos condições especiais e cupom de desconto para você hoje!";
                                            $mailUrl = 'mailto:' . rawurlencode($act['customer_email']) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
                                        ?>
                                        <a href="<?= htmlspecialchars($mailUrl) ?>" style="background: #3b82f6; color: #fff; padding: 5px 11px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: background 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                            <span>✉️</span> E-mail Marketing
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.75rem;">Sem dados de contato</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
