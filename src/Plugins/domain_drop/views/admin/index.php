<?php
/**
 * View Administrativa: Cockpit Principal do Hub DomainDrop
 *
 * @var array<int, \DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto> $products
 * @var int $totalCount
 * @var array<int, string> $categories
 * @var float $rate
 * @var array<string, mixed> $config
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 */
?>
<div class="domain-drop-container" style="max-width: 1400px; margin: 0 auto;">

    <!-- Top Header & Breadcrumbs -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span style="color: var(--text-muted, #94a3b8); font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fas fa-boxes"></i> E-commerce
                </span>
                <span style="color: var(--text-muted, #94a3b8); font-size: 12px;">/</span>
                <span style="color: #ff4747; font-size: 13px; font-weight: 600;">DomainDrop</span>
            </div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-satellite-dish" style="color: #ff4747;"></i>
                DomainDrop Hub <span style="font-size: 13px; background: rgba(255, 71, 71, 0.15); color: #ff6b6b; padding: 2px 10px; border-radius: 12px; border: 1px solid rgba(255, 71, 71, 0.3);">AliExpress Dropship ISV</span>
            </h1>
            <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Motor autônomo de mineração de produtos virais, cálculo de margens cambiais e enriquecimento de títulos por IA.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="<?= $baseUrl ?>/admin/dropship/settings" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-sliders-h"></i> Configurações & API
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($flashMsg) && $flashType === 'error'): ?>
        <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
        </div>
    <?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
        <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Metrics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">
                Produtos no Catálogo
            </div>
            <div style="font-size: 28px; font-weight: 700; color: #fff;">
                <?= number_format($totalCount, 0, ',', '.') ?>
            </div>
            <div style="font-size: 12px; color: #5cd67d; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                <i class="fas fa-check-double"></i> Prontos para venda
            </div>
        </div>

        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">
                Câmbio Comercial (USD/BRL)
            </div>
            <div style="font-size: 28px; font-weight: 700; color: #45f3ff;">
                R$ <?= number_format($rate, 2, ',', '.') ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8); margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                <i class="fas fa-sync-alt"></i> Atualização automática (AwesomeAPI)
            </div>
        </div>

        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">
                Markup Padrão
            </div>
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">
                <?= (float)($config['default_markup_percent'] ?? 100.0) ?>%
            </div>
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8); margin-top: 6px;">
                Margem mín: R$ <?= number_format((float)($config['min_profit_margin_brl'] ?? 30.0), 2, ',', '.') ?>
            </div>
        </div>

        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">
                AliExpress AppKey
            </div>
            <div style="font-size: 22px; font-weight: 700; color: #fff; font-family: monospace;">
                <?= htmlspecialchars((string)($config['app_key'] ?? '558628')) ?>
            </div>
            <div style="font-size: 12px; color: #5cd67d; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                <i class="fas fa-shield-alt"></i> HMAC-SHA256 Ativo
            </div>
        </div>
    </div>

    <!-- 24h Autonomous Daily Rotation Cycle Banner -->
    <?php
    $adminRemSeconds = isset($cycleInfo['seconds_remaining']) ? (int)$cycleInfo['seconds_remaining'] : 86400;
    $adminH = str_pad((string)floor($adminRemSeconds / 3600), 2, '0', STR_PAD_LEFT);
    $adminM = str_pad((string)floor(($adminRemSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $adminS = str_pad((string)($adminRemSeconds % 60), 2, '0', STR_PAD_LEFT);
    ?>
    <div style="background: linear-gradient(135deg, rgba(212,175,55,0.12) 0%, rgba(20,24,30,0.95) 100%); border: 1px solid rgba(212,175,55,0.4); border-radius: 10px; padding: 22px 24px; margin-bottom: 26px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 0 16px rgba(212,175,55,0.45); flex-shrink: 0;">
                ⚡
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <span style="font-size: 16px; font-weight: 800; color: #FFE082; text-transform: uppercase; letter-spacing: 0.5px;">
                        Ciclo Autônomo 24 Horas • Rotação de 9 Categorias
                    </span>
                    <span style="background: rgba(34,197,94,0.2); color: #4ade80; border: 1px solid rgba(34,197,94,0.4); padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                        Ativo
                    </span>
                </div>
                <div style="font-size: 13px; color: #cbd5e1;">
                    Renova o catálogo automaticamente minerando produtos virais em todas as 9 categorias oficiais a cada 24 horas.
                </div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                    Último ciclo: <strong style="color: #fff;"><?= htmlspecialchars((string)($cycleInfo['last_cycle_date'] ?? 'Pendente')) ?></strong>
                    &nbsp;•&nbsp; 
                    9 Categorias: <span style="color: #F0D060;">Eletrônicos, Casa, Beleza, Ferramentas, Pet Shop, Veículos, Moda/Roupas, Lingerie/Íntima, Sex Shop (+18)</span>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <!-- Countdown Ticker -->
            <div style="display: flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.5); padding: 8px 14px; border-radius: 8px; border: 1px solid rgba(212,175,55,0.3);">
                <div style="text-align: center; min-width: 32px;">
                    <div id="adminTimerH" style="font-size: 18px; font-weight: 800; color: #fff; font-family: monospace;"><?= $adminH ?></div>
                    <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase;">Horas</div>
                </div>
                <span style="color: #F0D060; font-weight: 800;">:</span>
                <div style="text-align: center; min-width: 32px;">
                    <div id="adminTimerM" style="font-size: 18px; font-weight: 800; color: #fff; font-family: monospace;"><?= $adminM ?></div>
                    <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase;">Min</div>
                </div>
                <span style="color: #F0D060; font-weight: 800;">:</span>
                <div style="text-align: center; min-width: 32px;">
                    <div id="adminTimerS" style="font-size: 18px; font-weight: 800; color: #FFE082; font-family: monospace;"><?= $adminS ?></div>
                    <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase;">Seg</div>
                </div>
            </div>

            <!-- Manual Trigger Form -->
            <form action="<?= $baseUrl ?>/admin/dropship/run-daily-cycle" method="POST" id="formRunDailyCycle" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" id="btnRunDailyCycle" style="background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%); color: #0b0f17; border: none; padding: 11px 20px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(212,175,55,0.3); transition: transform 0.2s ease;">
                    <i class="fas fa-bolt"></i> Rodar Ciclo Diário 24h Agora
                </button>
            </form>
        </div>
    </div>

    <script>
    (function() {
        var adminRemSeconds = <?= $adminRemSeconds ?>;
        var hElem = document.getElementById('adminTimerH');
        var mElem = document.getElementById('adminTimerM');
        var sElem = document.getElementById('adminTimerS');
        var form = document.getElementById('formRunDailyCycle');
        var btn = document.getElementById('btnRunDailyCycle');

        if (form && btn) {
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Minerando 6 Categorias no AliExpress...';
            });
        }

        setInterval(function() {
            if (adminRemSeconds <= 0) {
                if (hElem) hElem.textContent = '00';
                if (mElem) mElem.textContent = '00';
                if (sElem) sElem.textContent = '00';
                return;
            }
            adminRemSeconds--;
            var h = Math.floor(adminRemSeconds / 3600);
            var m = Math.floor((adminRemSeconds % 3600) / 60);
            var s = adminRemSeconds % 60;
            if (hElem) hElem.textContent = String(h).padStart(2, '0');
            if (mElem) mElem.textContent = String(m).padStart(2, '0');
            if (sElem) sElem.textContent = String(s).padStart(2, '0');
        }, 1000);
    })();
    </script>

    <!-- Quick Operations: 1-Click Miner, Single Product Import & Browser Code Ingestion -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px;">
        
        <!-- 1-Click Mining Card -->
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 22px;">
            <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-radar" style="color: #ff4747;"></i> Mineração de Produtos em Alta
            </h3>
            <p style="margin: 0 0 16px 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Busca os produtos mais vendidos e virais do AliExpress na categoria escolhida, converte a moeda e gera títulos profissionais automaticamente.
            </p>
            <form action="<?= $baseUrl ?>/admin/dropship/mine" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div style="display: grid; grid-template-columns: 1.5fr 1fr 1.5fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 12px; color: var(--text-muted, #94a3b8); margin-bottom: 4px;">Nicho / Categoria</label>
                        <select name="category" style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                            <option value="Eletrônicos & Gadget">Eletrônicos & Gadget</option>
                            <option value="Casa Inteligente & Organização">Casa Inteligente & Organização</option>
                            <option value="Beleza, Cuidados & Estilo">Beleza, Cuidados & Estilo</option>
                            <option value="Ferramentas & Utilidades">Ferramentas & Utilidades</option>
                            <option value="Pet Shop & Pets Virais">Pet Shop & Pets Virais</option>
                            <option value="Acessórios para Veículos">Acessórios para Veículos</option>
                            <option value="Moda, Roupas & Vestuário">Moda, Roupas & Vestuário</option>
                            <option value="Moda Íntima, Lingeries & Pijamas">Moda Íntima, Lingeries & Pijamas</option>
                            <option value="Sex Shop & Produtos Adultos (+18)">🔞 Sex Shop & Produtos Adultos (+18)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; color: var(--text-muted, #94a3b8); margin-bottom: 4px;">Quantidade</label>
                        <select name="limit" style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                            <option value="5">5 itens</option>
                            <option value="10" selected>10 itens</option>
                            <option value="20">20 itens</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; color: var(--text-muted, #94a3b8); margin-bottom: 4px;">Termômetro &amp; Reputação</label>
                        <select name="sort" style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                            <option value="volume_desc" selected>🏆 Mais Vendidos (Best Sellers)</option>
                            <option value="evaluate_rate_desc">⭐ Melhor Avaliados (4.8★ - 5.0★)</option>
                            <option value="choice_desc">⚡ Lojas Oficiais &amp; Choice</option>
                        </select>
                    </div>
                </div>

                <button type="submit" style="background: linear-gradient(135deg, #ff4747 0%, #e02424 100%); border: none; color: #fff; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-bolt"></i> Iniciar Mineração 1-Click
                </button>
            </form>
        </div>

        <!-- Import by URL Card -->
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 22px;">
            <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-link" style="color: #45f3ff;"></i> Importação por Link ou ID
            </h3>
            <p style="margin: 0 0 16px 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Cole o link direto do produto no AliExpress ou o ID numérico. O sistema buscará fotos, preços, variantes e aplicará sua margem de lucro.
            </p>
            <form action="<?= $baseUrl ?>/admin/dropship/import-url" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted, #94a3b8); margin-bottom: 4px;">URL ou ID do Produto</label>
                    <input type="text" name="product_url_or_id" placeholder="https://pt.aliexpress.com/item/10050012345.html" required style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                </div>

                <button type="submit" style="background: rgba(69, 243, 255, 0.15); border: 1px solid rgba(69, 243, 255, 0.4); color: #45f3ff; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-download"></i> Importar Produto & Aplicar IA
                </button>
            </form>
        </div>

        <!-- Browser Source / Frame Ingestion Card (100% Anti-Bot Bypass) -->
        <div style="background: var(--bg-panel, #1d2327); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 22px; position: relative;">
            <div style="position: absolute; top: 16px; right: 16px; background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.4); text-transform: uppercase;">
                100% Anti-Robô
            </div>
            <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-code" style="color: #10b981;"></i> Código do Navegador
            </h3>
            <p style="margin: 0 0 12px 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
                Abra o anúncio no navegador, copie o código-fonte (Ctrl+U ou F12) e cole aqui. Evita qualquer bloqueio de robô ou verificação deslizante.
            </p>
            <form action="<?= $baseUrl ?>/admin/dropship/import-browser-source" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 11px; color: var(--text-muted, #94a3b8); margin-bottom: 2px;">Código HTML ou JSON do Anúncio</label>
                        <textarea name="raw_source" rows="2" placeholder="Cole aqui o HTML (Ctrl+U) ou JSON do anúncio..." required style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(16, 185, 129, 0.3); color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-family: monospace; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: var(--text-muted, #94a3b8); margin-bottom: 2px;">Categoria</label>
                        <select name="category" style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 7px 8px; border-radius: 6px; font-size: 12px;">
                            <option value="">Inferir Auto</option>
                            <option value="Eletrônicos & Gadget">Eletrônicos</option>
                            <option value="Casa Inteligente & Organização">Casa Inteligente</option>
                            <option value="Beleza, Cuidados & Estilo">Beleza & Estilo</option>
                            <option value="Ferramentas & Utilidades">Ferramentas</option>
                            <option value="Pet Shop & Pets Virais">Pet Shop</option>
                            <option value="Acessórios para Veículos">Veículos</option>
                        </select>
                    </div>
                </div>

                <button type="submit" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; color: #fff; padding: 9px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                    <i class="fas fa-file-import"></i> Processar Código & Importar
                </button>
            </form>
        </div>

    </div>

    <!-- Catalog Filter Bar -->
    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form action="<?= $baseUrl ?>/admin/dropship" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
            <div style="position: relative; min-width: 260px; flex: 1;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 11px; color: var(--text-muted, #94a3b8); font-size: 13px;"></i>
                <input type="text" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="Buscar por título ou ID..." style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px 8px 34px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
            </div>

            <?php if (!empty($categories)): ?>
                <select name="category" onchange="this.form.submit()" style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <button type="submit" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Filtrar
            </button>
            <?php if (!empty($_GET['q']) || !empty($_GET['category'])): ?>
                <a href="<?= $baseUrl ?>/admin/dropship" style="color: var(--text-muted, #94a3b8); font-size: 12px; text-decoration: none;">Limpar</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; align-items: center; gap: 14px;">
            <form action="<?= $baseUrl ?>/admin/dropship/sync-all-images" method="POST" style="margin: 0;" onsubmit="return confirm('Deseja sincronizar em tempo real as imagens oficiais de todos os produtos com seus links do AliExpress?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" style="background: rgba(69, 243, 255, 0.12); border: 1px solid rgba(69, 243, 255, 0.35); color: #45f3ff; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Sincronizar Todas as Imagens pelo Link
                </button>
            </form>
            <div style="font-size: 13px; color: var(--text-muted, #94a3b8);">
                Mostrando <strong style="color: #fff;"><?= count($products) ?></strong> de <?= $totalCount ?> produtos
            </div>
        </div>
    </div>

    <!-- Products Catalog Grid -->
    <?php if (empty($products)): ?>
        <div style="background: var(--bg-panel, #1d2327); border: 1px dashed var(--border, rgba(255,255,255,0.15)); border-radius: 8px; padding: 60px 20px; text-align: center;">
            <i class="fas fa-box-open" style="font-size: 48px; color: var(--text-muted, #94a3b8); margin-bottom: 16px;"></i>
            <h3 style="margin: 0 0 8px 0; color: #fff; font-size: 18px;">Nenhum produto minerado ainda</h3>
            <p style="margin: 0 0 20px 0; color: var(--text-muted, #94a3b8); font-size: 13px; max-width: 480px; margin-left: auto; margin-right: auto;">
                Utilize o botão de "Mineração 1-Click" acima ou importe diretamente via URL do AliExpress para abastecer sua vitrine de dropshipping.
            </p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; margin-bottom: 30px;">
            <?php foreach ($products as $prod): ?>
                <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                    <!-- Image with badges -->
                    <div style="position: relative; width: 100%; height: 200px; background: #0b0e11; overflow: hidden;">
                        <?php if (!empty($prod->mainImageUrl)): ?>
                            <img id="prod-main-img-<?= (int)($prod->id ?? 0) ?>" src="<?= htmlspecialchars($prod->mainImageUrl) ?>" alt="<?= htmlspecialchars($prod->title) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=600&auto=format&fit=crop&q=80';" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div id="prod-main-img-placeholder-<?= (int)($prod->id ?? 0) ?>" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted, #94a3b8);">
                                <i class="fas fa-image" style="font-size: 32px;"></i>
                            </div>
                        <?php endif; ?>

                        <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                            <?= htmlspecialchars($prod->category ?: 'Dropship') ?>
                        </div>

                        <?php $galleryCount = !empty($prod->images) ? count($prod->images) : (!empty($prod->mainImageUrl) ? 1 : 0); ?>
                        <div id="prod-imgs-badge-<?= (int)($prod->id ?? 0) ?>" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: #45f3ff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: <?= $galleryCount > 1 ? 'flex' : 'none' ?>; align-items: center; gap: 4px;">
                            <i class="fas fa-images"></i> <span id="prod-imgs-count-<?= (int)($prod->id ?? 0) ?>"><?= $galleryCount ?></span> fotos
                        </div>

                        <?php if ($prod->rating > 0): ?>
                            <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.75); color: #fbbf24; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-star"></i> <?= number_format($prod->rating, 1) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #fff; line-height: 1.4; max-height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;" title="<?= htmlspecialchars($prod->title) ?>">
                                <?= htmlspecialchars($prod->title) ?>
                            </h4>

                            <!-- Price Breakdown -->
                            <div style="background: rgba(0,0,0,0.25); border-radius: 6px; padding: 10px 12px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                                    <span style="font-size: 11px; color: var(--text-muted, #94a3b8);">Custo (USD):</span>
                                    <span id="prod-cost-usd-<?= (int)$prod->id ?>" style="font-size: 12px; color: #94a3b8; font-family: monospace;">$<?= number_format($prod->costPriceUsd, 2) ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                                    <span style="font-size: 11px; color: var(--text-muted, #94a3b8);">Custo Convertido:</span>
                                    <span id="prod-cost-brl-<?= (int)$prod->id ?>" style="font-size: 12px; color: #94a3b8; font-family: monospace;">R$ <?= number_format($prod->costPriceUsd * $rate, 2, ',', '.') ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: baseline; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 6px; margin-top: 4px;">
                                    <span style="font-size: 12px; font-weight: 600; color: #5cd67d;">Venda Sugerida:</span>
                                    <span id="prod-sale-brl-<?= (int)$prod->id ?>" style="font-size: 16px; font-weight: 700; color: #5cd67d; font-family: monospace;">R$ <?= number_format($prod->salePriceBrl, 2, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06); gap: 6px;">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <a href="<?= $baseUrl ?>/produto/<?= urlencode($prod->slug) ?>" target="_blank" style="height: 26px; padding: 0 8px; font-size: 11px; font-weight: 600; color: #45f3ff; background: rgba(69, 243, 255, 0.08); border: 1px solid rgba(69, 243, 255, 0.25); border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; box-sizing: border-box; line-height: 1;" title="Visualizar na loja">
                                    <i class="fas fa-eye"></i> Loja
                                </a>
                                <a href="https://www.aliexpress.com/item/<?= htmlspecialchars($prod->aliexpressId) ?>.html" target="_blank" rel="noopener noreferrer" style="height: 26px; padding: 0 8px; font-size: 11px; font-weight: 600; color: #ff6b6b; background: rgba(255, 107, 107, 0.08); border: 1px solid rgba(255, 107, 107, 0.25); border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; box-sizing: border-box; line-height: 1;" title="Abrir link original no AliExpress">
                                    <i class="fas fa-external-link-alt"></i> Link
                                </a>
                                <?php if ($prod->id !== null): ?>
                                    <button type="button" onclick="openPriceModal(<?= (int)$prod->id ?>, <?= htmlspecialchars(json_encode($prod->title)) ?>, <?= (float)$prod->costPriceUsd ?>, <?= (float)round($prod->costPriceUsd * $rate, 2) ?>, <?= (float)$prod->salePriceBrl ?>)" style="height: 26px; padding: 0 8px; font-size: 11px; font-weight: 600; color: #fbbf24; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; box-sizing: border-box; line-height: 1;" title="Editar Custo e Preço de Venda">
                                        <i class="fas fa-tags"></i> Preço
                                    </button>
                                    <button type="button" onclick="openImagesModal(<?= (int)$prod->id ?>)" style="height: 26px; padding: 0 8px; font-size: 11px; font-weight: 600; color: #45f3ff; background: rgba(69, 243, 255, 0.08); border: 1px solid rgba(69, 243, 255, 0.25); border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; box-sizing: border-box; line-height: 1;" title="Visualizar, editar e sincronizar imagens do produto">
                                        <i class="fas fa-images"></i> Imagens
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if ($prod->id !== null): ?>
                                <form action="<?= $baseUrl ?>/admin/dropship/delete/<?= (int)$prod->id ?>" method="POST" onsubmit="return confirm('Deseja realmente remover este produto do catálogo?');" style="margin: 0; display: inline-flex; vertical-align: middle;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button type="submit" style="height: 26px; padding: 0 8px; font-size: 11px; font-weight: 600; color: #ef4444; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; box-sizing: border-box; line-height: 1;" title="Excluir produto do catálogo">
                                        <i class="fas fa-trash-alt"></i> Excluir
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal de Edição de Preço (Clean / Responsivo / Tempo Real) -->
    <div id="priceEditModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: #111722; border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 12px; max-width: 520px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.8); overflow: hidden; animation: dsModalIn 0.2s ease;">
            
            <div style="background: rgba(245, 158, 11, 0.08); border-bottom: 1px solid rgba(255,255,255,0.08); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="background: #f59e0b; color: #000; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #fff;">Ajuste de Preço e Margem</h3>
                        <div id="modalProductTitle" style="font-size: 12px; color: var(--text-muted, #94a3b8); max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
                    </div>
                </div>
                <button type="button" onclick="closePriceModal()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 4px;">&times;</button>
            </div>

            <form id="priceEditForm" method="POST" action="" onsubmit="handlePriceSubmit(event)" style="padding: 24px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" id="modalProductId" name="product_id" value="">

                <!-- Câmbio de Referência -->
                <div style="background: rgba(69, 243, 255, 0.06); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
                    <span style="color: #94a3b8;"><i class="fas fa-exchange-alt"></i> Câmbio Atual do Sistema:</span>
                    <strong style="color: #45f3ff;">USD 1,00 = R$ <?= number_format($rate, 2, ',', '.') ?></strong>
                </div>

                <!-- Input Custo Fornecedor (BRL) -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 6px;">
                        Custo do Fornecedor (R$ na Loja do AliExpress):
                    </label>
                    <div style="display: flex; align-items: stretch; border: 1px solid rgba(255,255,255,0.18); border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.35); box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);">
                        <span style="display: inline-flex; align-items: center; justify-content: center; padding: 0 16px; background: rgba(255,255,255,0.08); color: #94a3b8; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.12); user-select: none;">
                            R$
                        </span>
                        <input type="number" step="0.01" min="0" id="modalCostBrl" name="cost_price_brl" oninput="recalcMargin()" required style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: #fff; font-size: 16px; font-weight: 700; outline: none; box-sizing: border-box; font-family: monospace;">
                    </div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 5px; display: flex; justify-content: space-between;">
                        <span>Equivalente em Dólar: <strong id="modalCostUsdHint" style="color: #fff; font-family: monospace;">$0.00</strong></span>
                        <span style="color: #f59e0b;">Atualize caso o produto esteja em promoção no AliExpress</span>
                    </div>
                </div>

                <!-- Atalhos de Markup Rápido -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 6px; font-weight: 600;">
                        Atalhos de Margem Automática sobre o Custo:
                    </div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <button type="button" onclick="applyMarkup(30)" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">+30%</button>
                        <button type="button" onclick="applyMarkup(50)" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">+50%</button>
                        <button type="button" onclick="applyMarkup(75)" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">+75%</button>
                        <button type="button" onclick="applyMarkup(100)" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: 700;">+100% (2x Custo)</button>
                        <button type="button" onclick="roundTo90()" style="background: rgba(69, 243, 255, 0.1); border: 1px solid rgba(69, 243, 255, 0.3); color: #45f3ff; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">Terminar em ,90</button>
                    </div>
                </div>

                <!-- Input Preço de Venda Final (BRL) -->
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #5cd67d; margin-bottom: 6px;">
                        Preço de Venda na Loja RachidD.com (R$):
                    </label>
                    <div style="display: flex; align-items: stretch; border: 2px solid rgba(92, 214, 125, 0.45); border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.35); box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);">
                        <span style="display: inline-flex; align-items: center; justify-content: center; padding: 0 16px; background: rgba(92, 214, 125, 0.12); color: #5cd67d; font-weight: 800; font-size: 15px; border-right: 1px solid rgba(92, 214, 125, 0.3); user-select: none;">
                            R$
                        </span>
                        <input type="number" step="0.01" min="0.01" id="modalSaleBrl" name="sale_price_brl" oninput="recalcMargin()" required style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: #5cd67d; font-size: 18px; font-weight: 800; outline: none; box-sizing: border-box; font-family: monospace;">
                    </div>
                </div>

                <!-- Painel de Lucratividade em Tempo Real -->
                <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px; margin-bottom: 22px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; text-align: center;">
                        <div style="border-right: 1px solid rgba(255,255,255,0.08); padding-right: 12px;">
                            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Lucro Bruto por Venda</div>
                            <div id="modalProfitBrl" style="font-size: 18px; font-weight: 800; color: #5cd67d; margin-top: 2px;">R$ 0,00</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Margem Real</div>
                            <div id="modalMarginPercent" style="font-size: 18px; font-weight: 800; color: #45f3ff; margin-top: 2px;">0%</div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closePriceModal()" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnSavePrice" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; color: #000; padding: 10px 22px; border-radius: 6px; font-size: 13px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-check"></i> Salvar Preço
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Galeria e Sincronização de Imagens -->
    <div id="imagesEditModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.78); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: #111722; border: 1px solid rgba(69, 243, 255, 0.35); border-radius: 12px; max-width: 720px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,0.85); overflow: hidden; animation: dsModalIn 0.2s ease;">
            <div style="background: rgba(69, 243, 255, 0.08); border-bottom: 1px solid rgba(255,255,255,0.08); padding: 16px 22px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <div style="background: #45f3ff; color: #0b0f17; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        <i class="fas fa-images"></i>
                    </div>
                    <div style="min-width: 0;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #fff;">Galeria &amp; Imagens do Produto</h3>
                        <div id="imagesModalTitle" style="font-size: 12px; color: var(--text-muted, #94a3b8); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
                    </div>
                </div>
                <button type="button" onclick="closeImagesModal()" style="background: none; border: none; color: #94a3b8; font-size: 22px; cursor: pointer; padding: 4px;">&times;</button>
            </div>

            <div style="padding: 20px 22px; overflow-y: auto; flex: 1;">
                <div id="imagesModalStatus" style="display: none; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px;"></div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <div style="font-size: 12px; color: #94a3b8;">
                        Clique em <strong style="color: #fbbf24;">Definir Capa</strong> para escolher a foto principal exibida na loja.
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a id="imagesModalAeLink" href="#" target="_blank" rel="noopener noreferrer" style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); color: #ff6b6b; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-external-link-alt"></i> Abrir no AliExpress
                        </a>
                        <button type="button" id="btnSyncImagesNow" onclick="syncImagesNow()" style="background: rgba(69, 243, 255, 0.14); border: 1px solid rgba(69, 243, 255, 0.4); color: #45f3ff; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-sync-alt"></i> Sincronizar do AliExpress
                        </button>
                    </div>
                </div>

                <div id="imagesModalGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)); gap: 12px; margin-bottom: 18px;"></div>

                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Adicionar nova foto por URL (https://...):
                    </label>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <input type="url" id="imagesModalNewUrl" placeholder="https://ae-pic-a1.aliexpress-media.com/kf/..." style="flex: 1; min-width: 220px; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.16); color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                        <button type="button" onclick="addManualImageUrl()" style="background: rgba(92,214,125,0.15); border: 1px solid rgba(92,214,125,0.4); color: #5cd67d; padding: 8px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.08); padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px; background: rgba(0,0,0,0.2);">
                <button type="button" onclick="closeImagesModal()" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    Fechar
                </button>
                <button type="button" id="btnSaveImagesGallery" onclick="handleImagesSave()" style="background: linear-gradient(135deg, #45f3ff, #0ea5e9); border: none; color: #081018; padding: 9px 20px; border-radius: 6px; font-size: 13px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Salvar Galeria
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes dsModalIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>

    <script>
        const systemExchangeRate = <?= (float)$rate ?>;
        const baseAdminUrl = '<?= $baseUrl ?>/admin/dropship';

        function openPriceModal(id, title, costUsd, costBrl, saleBrl) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalProductTitle').innerText = title;
            document.getElementById('modalProductTitle').title = title;
            document.getElementById('priceEditForm').action = baseAdminUrl + '/product/' + id + '/price';

            document.getElementById('modalCostBrl').value = parseFloat(costBrl).toFixed(2);
            document.getElementById('modalSaleBrl').value = parseFloat(saleBrl).toFixed(2);

            recalcMargin();
            var modal = document.getElementById('priceEditModal');
            modal.style.display = 'flex';
        }

        function closePriceModal() {
            var modal = document.getElementById('priceEditModal');
            modal.style.display = 'none';
        }

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePriceModal();
                closeImagesModal();
            }
        });

        document.getElementById('priceEditModal').addEventListener('click', function(e) {
            if (e.target === this) closePriceModal();
        });

        document.getElementById('imagesEditModal').addEventListener('click', function(e) {
            if (e.target === this) closeImagesModal();
        });

        function recalcMargin() {
            const costBrl = parseFloat(document.getElementById('modalCostBrl').value) || 0;
            const saleBrl = parseFloat(document.getElementById('modalSaleBrl').value) || 0;

            const costUsd = (systemExchangeRate > 0) ? (costBrl / systemExchangeRate) : 0;
            document.getElementById('modalCostUsdHint').innerText = '$' + costUsd.toFixed(2);

            const profit = saleBrl - costBrl;
            const margin = saleBrl > 0 ? ((profit / saleBrl) * 100).toFixed(1) : 0;

            const profitEl = document.getElementById('modalProfitBrl');
            const marginEl = document.getElementById('modalMarginPercent');

            profitEl.innerText = (profit >= 0 ? '+R$ ' : '-R$ ') + Math.abs(profit).toFixed(2).replace('.', ',');
            profitEl.style.color = profit >= 0 ? '#5cd67d' : '#ef4444';

            marginEl.innerText = margin + '%';
            marginEl.style.color = margin >= 0 ? '#45f3ff' : '#ef4444';
        }

        function applyMarkup(percent) {
            const costBrl = parseFloat(document.getElementById('modalCostBrl').value) || 0;
            if (costBrl > 0) {
                const newSale = costBrl * (1 + (percent / 100));
                document.getElementById('modalSaleBrl').value = newSale.toFixed(2);
                recalcMargin();
            }
        }

        function roundTo90() {
            let sale = parseFloat(document.getElementById('modalSaleBrl').value) || 0;
            if (sale > 0) {
                let rounded = Math.floor(sale) + 0.90;
                if (rounded < sale && (sale - rounded) > 0.4) {
                    rounded += 1;
                }
                document.getElementById('modalSaleBrl').value = rounded.toFixed(2);
                recalcMargin();
            }
        }

        function handlePriceSubmit(e) {
            e.preventDefault();
            const form = document.getElementById('priceEditForm');
            const id = document.getElementById('modalProductId').value;
            const btn = document.getElementById('btnSavePrice');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (data && data.success) {
                    // Atualiza os valores visuais no card na hora
                    const saleEl = document.getElementById('prod-sale-brl-' + id);
                    if (saleEl) saleEl.innerText = data.sale_formatted;

                    const costBrlEl = document.getElementById('prod-cost-brl-' + id);
                    if (costBrlEl) costBrlEl.innerText = data.cost_formatted;

                    const costUsdEl = document.getElementById('prod-cost-usd-' + id);
                    if (costUsdEl) costUsdEl.innerText = '$' + parseFloat(data.cost_price_usd).toFixed(2);

                    closePriceModal();
                    alert(data.message);
                } else {
                    alert((data && data.message) ? data.message : 'Falha ao salvar preço.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                // Fallback para envio padrão se fetch falhar
                form.submit();
            });
        }

        <?php
        $mediaMap = [];
        foreach ($products as $p) {
            if ($p->id === null) {
                continue;
            }
            $imgs = !empty($p->images) ? array_values($p->images) : [];
            if (!empty($p->mainImageUrl) && !in_array($p->mainImageUrl, $imgs, true)) {
                array_unshift($imgs, $p->mainImageUrl);
            }
            $mediaMap[(string)(int)$p->id] = [
                'id'           => (int)$p->id,
                'title'        => (string)$p->title,
                'aliexpressId' => (string)$p->aliexpressId,
                'mainImageUrl' => (string)($p->mainImageUrl ?: ($imgs[0] ?? '')),
                'images'       => $imgs,
            ];
        }
        ?>
        const dropshipCsrfToken = <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const dropshipProductsMedia = <?= json_encode($mediaMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;
        let activeImagesState = { id: null, title: '', aliexpressId: '', mainImageUrl: '', images: [] };

        function setImagesModalStatus(msg, type) {
            const el = document.getElementById('imagesModalStatus');
            if (!el) return;
            if (!msg) {
                el.style.display = 'none';
                el.textContent = '';
                return;
            }
            el.style.display = 'block';
            el.textContent = msg;
            if (type === 'error') {
                el.style.background = 'rgba(220,50,50,0.16)';
                el.style.border = '1px solid #dc3232';
                el.style.color = '#ff8080';
            } else if (type === 'info') {
                el.style.background = 'rgba(69,243,255,0.12)';
                el.style.border = '1px solid rgba(69,243,255,0.4)';
                el.style.color = '#45f3ff';
            } else {
                el.style.background = 'rgba(40,167,69,0.16)';
                el.style.border = '1px solid #28a745';
                el.style.color = '#5cd67d';
            }
        }

        function openImagesModal(id) {
            const item = dropshipProductsMedia[String(id)];
            if (!item) return;

            activeImagesState = {
                id: item.id,
                title: item.title,
                aliexpressId: item.aliexpressId,
                mainImageUrl: item.mainImageUrl,
                images: Array.isArray(item.images) ? item.images.slice() : []
            };

            document.getElementById('imagesModalTitle').textContent = activeImagesState.title;
            document.getElementById('imagesModalTitle').title = activeImagesState.title;
            document.getElementById('imagesModalAeLink').href = 'https://www.aliexpress.com/item/' + encodeURIComponent(activeImagesState.aliexpressId) + '.html';
            document.getElementById('imagesModalNewUrl').value = '';
            setImagesModalStatus('', 'info');
            renderImagesModalGrid();

            document.getElementById('imagesEditModal').style.display = 'flex';
        }

        function closeImagesModal() {
            const modal = document.getElementById('imagesEditModal');
            if (modal) modal.style.display = 'none';
        }

        function renderImagesModalGrid() {
            const grid = document.getElementById('imagesModalGrid');
            if (!grid) return;
            grid.innerHTML = '';

            if (!activeImagesState.images.length) {
                const empty = document.createElement('div');
                empty.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 26px; background: rgba(0,0,0,0.25); border: 1px dashed rgba(255,255,255,0.12); border-radius: 8px; color: #94a3b8; font-size: 13px;';
                empty.textContent = 'Nenhuma imagem cadastrada ainda. Clique em "Sincronizar do AliExpress" ou cole uma URL abaixo.';
                grid.appendChild(empty);
                return;
            }

            activeImagesState.images.forEach(function(url, idx) {
                const isMain = (url === activeImagesState.mainImageUrl);

                const card = document.createElement('div');
                card.style.cssText = 'background: #0b0f17; border: 1px solid ' + (isMain ? '#fbbf24' : 'rgba(255,255,255,0.1)') + '; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;';

                const thumbWrap = document.createElement('div');
                thumbWrap.style.cssText = 'position: relative; width: 100%; height: 120px; background: #070a10;';

                const img = document.createElement('img');
                img.src = url;
                img.alt = 'Foto ' + (idx + 1);
                img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display: block;';
                thumbWrap.appendChild(img);

                if (isMain) {
                    const badge = document.createElement('span');
                    badge.style.cssText = 'position: absolute; top: 6px; left: 6px; background: #fbbf24; color: #0b0f17; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px;';
                    badge.textContent = 'CAPA';
                    thumbWrap.appendChild(badge);
                }

                const openLink = document.createElement('a');
                openLink.href = url;
                openLink.target = '_blank';
                openLink.rel = 'noopener noreferrer';
                openLink.title = 'Abrir imagem em tamanho original';
                openLink.style.cssText = 'position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,0.7); color: #45f3ff; width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; text-decoration: none;';
                openLink.innerHTML = '<i class="fas fa-external-link-alt"></i>';
                thumbWrap.appendChild(openLink);

                const actions = document.createElement('div');
                actions.style.cssText = 'padding: 8px; display: flex; gap: 6px; justify-content: space-between; align-items: center;';

                const coverBtn = document.createElement('button');
                coverBtn.type = 'button';
                coverBtn.disabled = isMain;
                coverBtn.style.cssText = 'flex: 1; padding: 4px 6px; font-size: 11px; font-weight: 700; border-radius: 4px; cursor: ' + (isMain ? 'default' : 'pointer') + '; border: 1px solid rgba(251,191,36,0.35); background: ' + (isMain ? 'rgba(251,191,36,0.18)' : 'rgba(255,255,255,0.05)') + '; color: ' + (isMain ? '#fbbf24' : '#cbd5e1') + ';';
                coverBtn.textContent = isMain ? 'Capa Atual' : 'Definir Capa';
                coverBtn.onclick = function() {
                    activeImagesState.mainImageUrl = url;
                    renderImagesModalGrid();
                };

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.title = 'Remover foto da galeria';
                delBtn.style.cssText = 'padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 4px; cursor: pointer; border: 1px solid rgba(239,68,68,0.35); background: rgba(239,68,68,0.1); color: #ef4444;';
                delBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                delBtn.onclick = function() {
                    activeImagesState.images.splice(idx, 1);
                    if (activeImagesState.mainImageUrl === url) {
                        activeImagesState.mainImageUrl = activeImagesState.images[0] || '';
                    }
                    renderImagesModalGrid();
                };

                actions.appendChild(coverBtn);
                actions.appendChild(delBtn);
                card.appendChild(thumbWrap);
                card.appendChild(actions);
                grid.appendChild(card);
            });
        }

        function addManualImageUrl() {
            const input = document.getElementById('imagesModalNewUrl');
            const raw = (input ? input.value : '').trim();
            if (!/^https?:\/\/\S+$/i.test(raw)) {
                setImagesModalStatus('Informe uma URL válida começando com http:// ou https://', 'error');
                return;
            }
            if (!activeImagesState.images.includes(raw)) {
                activeImagesState.images.push(raw);
            }
            if (!activeImagesState.mainImageUrl) {
                activeImagesState.mainImageUrl = raw;
            }
            input.value = '';
            setImagesModalStatus('Imagem adicionada à prévia. Clique em "Salvar Galeria" para gravar.', 'info');
            renderImagesModalGrid();
        }

        function updateCardImagesUi(id, mainUrl, imagesList) {
            const imgEl = document.getElementById('prod-main-img-' + id);
            if (imgEl && mainUrl) {
                imgEl.src = mainUrl;
            }
            const badge = document.getElementById('prod-imgs-badge-' + id);
            const countEl = document.getElementById('prod-imgs-count-' + id);
            const count = Array.isArray(imagesList) ? imagesList.length : 0;
            if (countEl) countEl.textContent = String(count);
            if (badge) badge.style.display = count > 1 ? 'flex' : 'none';

            if (dropshipProductsMedia[String(id)]) {
                dropshipProductsMedia[String(id)].mainImageUrl = mainUrl;
                dropshipProductsMedia[String(id)].images = imagesList.slice();
            }
        }

        function syncImagesNow() {
            if (!activeImagesState.id) return;
            const id = activeImagesState.id;
            const btn = document.getElementById('btnSyncImagesNow');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
            setImagesModalStatus('Consultando fotos oficiais direto do AliExpress...', 'info');

            const fd = new FormData();
            fd.append('csrf_token', dropshipCsrfToken);
            fd.append('ajax', '1');

            fetch(baseAdminUrl + '/product/' + id + '/sync-images', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (data && data.success) {
                    activeImagesState.mainImageUrl = data.main_image_url || '';
                    activeImagesState.images = Array.isArray(data.images) ? data.images : [];
                    renderImagesModalGrid();
                    updateCardImagesUi(id, activeImagesState.mainImageUrl, activeImagesState.images);
                    setImagesModalStatus(data.message || 'Imagens sincronizadas com sucesso!', 'success');
                } else {
                    setImagesModalStatus((data && data.message) ? data.message : 'O AliExpress bloqueou a coleta automática neste item. Você pode adicionar URLs de fotos manualmente abaixo.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                setImagesModalStatus('Erro de comunicação ao sincronizar imagens.', 'error');
            });
        }

        function handleImagesSave() {
            if (!activeImagesState.id) return;
            const id = activeImagesState.id;
            const btn = document.getElementById('btnSaveImagesGallery');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

            const fd = new FormData();
            fd.append('csrf_token', dropshipCsrfToken);
            fd.append('ajax', '1');
            fd.append('main_image_url', activeImagesState.mainImageUrl || '');
            fd.append('images_json', JSON.stringify(activeImagesState.images || []));

            fetch(baseAdminUrl + '/product/' + id + '/images', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                if (data && data.success) {
                    activeImagesState.mainImageUrl = data.main_image_url || '';
                    activeImagesState.images = Array.isArray(data.images) ? data.images : [];
                    renderImagesModalGrid();
                    updateCardImagesUi(id, activeImagesState.mainImageUrl, activeImagesState.images);
                    setImagesModalStatus(data.message || 'Galeria salva com sucesso!', 'success');
                } else {
                    setImagesModalStatus((data && data.message) ? data.message : 'Falha ao salvar galeria.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                setImagesModalStatus('Erro de comunicação ao salvar galeria.', 'error');
            });
        }
    </script>
</div>
