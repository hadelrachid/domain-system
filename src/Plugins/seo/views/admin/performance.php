<?php
/**
 * View Administrativa do DomainSEO & Performance Dashboard.
 *
 * @var string $baseUrl
 * @var array|null $latestMobile
 * @var array|null $latestDesktop
 * @var array $history
 * @var string $defaultUrl
 * @var string $publicTestUrl
 * @var string|null $flashMsg
 * @var string|null $flashType
 * @var string $csrfToken
 */

$formatScoreColor = function (int $score): string {
    if ($score >= 90) return '#10b981'; // Verde
    if ($score >= 50) return '#f59e0b'; // Laranja
    return '#ef4444'; // Vermelho
};

$formatBadgeClass = function (int $score): array {
    if ($score >= 90) return ['bg' => 'rgba(16, 185, 129, 0.15)', 'text' => '#10b981', 'label' => 'Bom'];
    if ($score >= 50) return ['bg' => 'rgba(245, 158, 11, 0.15)', 'text' => '#f59e0b', 'label' => 'Precisa de Melhoria'];
    return ['bg' => 'rgba(239, 68, 68, 0.15)', 'text' => '#ef4444', 'label' => 'Crítico'];
};
?>

<div style="max-width: 1360px; margin: 0 auto; padding: 24px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #f1f5f9;">

    <!-- FLASH MESSAGES -->
    <?php if (!empty($flashMsg)): ?>
        <div style="background: <?= $flashType === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; border: 1px solid <?= $flashType === 'success' ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)' ?>; color: <?= $flashType === 'success' ? '#10b981' : '#ef4444' ?>; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; justify-content: space-between;">
            <div><?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 16px;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- CABEÇALHO -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 28px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 28px;">⚡</span>
                <h1 style="font-size: 24px; font-weight: 700; margin: 0; color: #fff;">DomainSEO & Performance Hub</h1>
                <span style="background: rgba(69, 243, 255, 0.12); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.3); font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                    Google PageSpeed API v5
                </span>
            </div>
            <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 14px;">
                Auditorias contínuas de Core Web Vitals e feed inteligente de diagnósticos para otimização semanal por I.A.
            </p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="<?= htmlspecialchars($publicTestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #cbd5e1; padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                <span>🌐</span> Ver no PageSpeed Web Dev
            </a>
            <button type="button" onclick="openAuditModal()" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(14, 165, 233, 0.3); transition: transform 0.15s ease;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                <span>🚀</span> Executar Auditoria do Google
            </button>
        </div>
    </div>

    <!-- CARDS DE PONTUAÇÃO (MOBILE & DESKTOP) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px;">

        <!-- CARD MOBILE -->
        <?php
        $mScore = $latestMobile ? (int)$latestMobile['score'] : 0;
        $mColor = $latestMobile ? $formatScoreColor($mScore) : '#64748b';
        $mBadge = $latestMobile ? $formatBadgeClass($mScore) : ['bg' => 'rgba(255,255,255,0.05)', 'text' => '#94a3b8', 'label' => 'Sem Dados'];
        ?>
        <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: <?= $mColor ?>;"></div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Estratégia Mobile</span>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 4px 0 0 0; color: #fff;">📱 Experiência no Celular</h3>
                </div>
                <span style="background: <?= $mBadge['bg'] ?>; color: <?= $mBadge['text'] ?>; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">
                    <?= $mBadge['label'] ?>
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; margin: 18px 0;">
                <div style="width: 90px; height: 90px; border-radius: 50%; border: 6px solid <?= $mColor ?>; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                    <span style="font-size: 28px; font-weight: 800; color: <?= $mColor ?>; line-height: 1;"><?= $latestMobile ? $mScore : '--' ?></span>
                    <span style="font-size: 10px; color: #94a3b8; margin-top: 2px;">/ 100</span>
                </div>
                <div style="flex: 1; font-size: 13px; color: #94a3b8; line-height: 1.6;">
                    <?php if ($latestMobile): ?>
                        <div><strong>URL:</strong> <?= htmlspecialchars($latestMobile['url'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($latestMobile['created_at'])) ?></div>
                        <div style="color: #cbd5e1;"><strong>Fonte:</strong> <?= $latestMobile['source'] === 'google_pagespeed_api' ? 'Google PageSpeed Oficial' : 'Profiler Nativo Local' ?></div>
                    <?php else: ?>
                        <div>Nenhuma auditoria móvel realizada ainda. Clique em "Executar Auditoria" para analisar.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CORE WEB VITALS MOBILE -->
            <div style="background: rgba(0,0,0,0.25); border-radius: 8px; padding: 12px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.04);">
                <div>
                    <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">LCP (Maior Bloco)</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;"><?= $latestMobile['lcp'] ?? '--' ?></div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">CLS (Estabilidade)</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;"><?= $latestMobile['cls'] ?? '--' ?></div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">TTFB (Servidor)</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;"><?= $latestMobile['ttfb'] ?? '--' ?></div>
                </div>
            </div>
        </div>

        <!-- CARD DESKTOP -->
        <?php
        $dScore = $latestDesktop ? (int)$latestDesktop['score'] : 0;
        $dColor = $latestDesktop ? $formatScoreColor($dScore) : '#64748b';
        $dBadge = $latestDesktop ? $formatBadgeClass($dScore) : ['bg' => 'rgba(255,255,255,0.05)', 'text' => '#94a3b8', 'label' => 'Sem Dados'];
        ?>
        <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: <?= $dColor ?>;"></div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <span style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Estratégia Desktop</span>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 4px 0 0 0; color: #fff;">💻 Experiência no Computador</h3>
                </div>
                <span style="background: <?= $dBadge['bg'] ?>; color: <?= $dBadge['text'] ?>; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">
                    <?= $dBadge['label'] ?>
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; margin: 18px 0;">
                <div style="width: 90px; height: 90px; border-radius: 50%; border: 6px solid <?= $dColor ?>; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.3);">
                    <span style="font-size: 28px; font-weight: 800; color: <?= $dColor ?>; line-height: 1;"><?= $latestDesktop ? $dScore : '--' ?></span>
                    <span style="font-size: 10px; color: #94a3b8; margin-top: 2px;">/ 100</span>
                </div>
                <div style="flex: 1; font-size: 13px; color: #94a3b8; line-height: 1.6;">
                    <?php if ($latestDesktop): ?>
                        <div><strong>URL:</strong> <?= htmlspecialchars($latestDesktop['url'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($latestDesktop['created_at'])) ?></div>
                        <div style="color: #cbd5e1;"><strong>Fonte:</strong> <?= $latestDesktop['source'] === 'google_pagespeed_api' ? 'Google PageSpeed Oficial' : 'Profiler Nativo Local' ?></div>
                    <?php else: ?>
                        <div>Nenhuma auditoria desktop realizada ainda. Clique em "Executar Auditoria" para analisar.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CORE WEB VITALS DESKTOP -->
            <div style="background: rgba(0,0,0,0.25); border-radius: 8px; padding: 12px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.04);">
                <div>
                    <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">LCP (Maior Bloco)</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;"><?= $latestDesktop['lcp'] ?? '--' ?></div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">CLS (Estabilidade)</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;"><?= $latestDesktop['cls'] ?? '--' ?></div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">TTFB (Servidor)</div>
                    <div style="font-size: 14px; font-weight: 700; color: #fff; margin-top: 2px;"><?= $latestDesktop['ttfb'] ?? '--' ?></div>
                </div>
            </div>
        </div>

    </div>

    <!-- SEÇÃO PRINCIPAL EM 2 COLUNAS -->
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 28px;">

        <!-- COLUNA 1: OPORTUNIDADES TÉCNICAS DO LIGHTHOUSE -->
        <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <span>🔍</span> Oportunidades de Otimização Identificadas
                </h3>
                <span style="font-size: 12px; color: #94a3b8;">Baseado na última auditoria Mobile</span>
            </div>

            <?php
            $activeAudit = $latestMobile ?: $latestDesktop;
            $opportunities = $activeAudit['opportunities'] ?? [];
            ?>

            <?php if (!empty($opportunities)): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($opportunities as $op): ?>
                        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 14px 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <strong style="color: #f8fafc; font-size: 14px;"><?= htmlspecialchars((string)$op['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($op['savings'])): ?>
                                    <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; white-space: nowrap;">
                                        <?= htmlspecialchars((string)$op['savings'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p style="margin: 6px 0 8px 0; font-size: 12.5px; color: #94a3b8; line-height: 1.5;">
                                <?= htmlspecialchars((string)$op['description'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <?php if (!empty($op['files'])): ?>
                                <div style="font-family: monospace; font-size: 11px; background: rgba(0,0,0,0.4); padding: 8px 10px; border-radius: 4px; color: #cbd5e1; word-break: break-all;">
                                    <?php foreach ($op['files'] as $f): ?>
                                        <div>📄 <?= htmlspecialchars((string)$f, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 36px 20px; color: #94a3b8;">
                    <span style="font-size: 32px; display: block; margin-bottom: 8px;">🎉</span>
                    <strong style="color: #fff; font-size: 15px;">Nenhum gargalo crítico detectado!</strong>
                    <p style="font-size: 13px; margin: 6px 0 0 0;">O código atual atende aos padrões de alta velocidade e carregamento rápido.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- COLUNA 2: CENTRAL DE OTIMIZAÇÃO SEMANAL PARA A I.A. -->
        <div style="background: linear-gradient(145deg, #1e1b4b 0%, #0f172a 100%); border: 1px solid rgba(129, 140, 248, 0.25); border-radius: 12px; padding: 22px; position: relative;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <span style="font-size: 20px;">🤖</span>
                <h3 style="font-size: 16px; font-weight: 700; margin: 0; color: #a5b4fc;">Central de Otimização I.A.</h3>
            </div>
            <p style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-top: 0;">
                O DomainSEO gera automaticamente instruções executáveis para que a I.A. (Antigravity / Gemini) realize correções semanais de CSS, JavaScript, imagens e template sem você precisar escrever código.
            </p>

            <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px; margin: 16px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: 700; color: #45f3ff; text-transform: uppercase;">Prompt de Auditoria para a I.A.</span>
                    <button type="button" onclick="copyAiPrompt()" id="copy_btn" style="background: rgba(69, 243, 255, 0.1); border: 1px solid rgba(69, 243, 255, 0.3); color: #45f3ff; font-size: 11px; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                        📋 Copiar Prompt
                    </button>
                </div>
                <textarea id="ai_prompt_text" readonly style="width: 100%; height: 110px; background: transparent; border: none; color: #94a3b8; font-family: monospace; font-size: 12px; resize: none; outline: none; line-height: 1.5;">Olá I.A.! Por favor, leia a última auditoria de performance do DomainSEO (em /admin/seo/ai-feed) para a loja https://rachidd.com. Verifique as oportunidades de melhoria identificadas no PageSpeed e aplique as otimizações necessárias nos arquivos CSS, scripts e imagens para elevar o score de performance.</textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <a href="<?= $baseUrl ?>/admin/seo/ai-feed" target="_blank" style="flex: 1; text-align: center; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #e2e8f0; padding: 8px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 600;">
                    Visualizar JSON da I.A.
                </a>
            </div>
        </div>

    </div>

    <!-- TABELA DE HISTÓRICO DAS AUDITORIAS -->
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px;">
        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 16px 0; color: #fff; display: flex; align-items: center; gap: 8px;">
            <span>📜</span> Histórico Recente de Auditorias
        </h3>

        <?php if (!empty($history)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); color: #94a3b8; font-size: 11px; text-transform: uppercase;">
                            <th style="padding: 10px 12px;">Data & Hora</th>
                            <th style="padding: 10px 12px;">Estratégia</th>
                            <th style="padding: 10px 12px;">Score</th>
                            <th style="padding: 10px 12px;">LCP</th>
                            <th style="padding: 10px 12px;">CLS</th>
                            <th style="padding: 10px 12px;">TTFB</th>
                            <th style="padding: 10px 12px;">Origem</th>
                            <th style="padding: 10px 12px; text-align: right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <?php
                            $rowColor = $formatScoreColor((int)$h['score']);
                            ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td style="padding: 12px; color: #f1f5f9;"><?= date('d/m/Y H:i:s', strtotime((string)$h['created_at'])) ?></td>
                                <td style="padding: 12px;">
                                    <span style="font-size: 12px; font-weight: 600; color: #cbd5e1;">
                                        <?= $h['strategy'] === 'mobile' ? '📱 Mobile' : '💻 Desktop' ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="display: inline-block; width: 32px; height: 32px; line-height: 32px; text-align: center; border-radius: 50%; font-weight: 800; font-size: 13px; background: rgba(0,0,0,0.3); border: 2px solid <?= $rowColor ?>; color: <?= $rowColor ?>;">
                                        <?= (int)$h['score'] ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #cbd5e1;"><?= htmlspecialchars((string)($h['lcp'] ?? '--'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 12px; color: #cbd5e1;"><?= htmlspecialchars((string)($h['cls'] ?? '--'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 12px; color: #cbd5e1;"><?= htmlspecialchars((string)($h['ttfb'] ?? '--'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 12px; font-size: 12px; color: #94a3b8;">
                                    <?= $h['source'] === 'google_pagespeed_api' ? 'Google PageSpeed' : 'Profiler Nativo' ?>
                                </td>
                                <td style="padding: 12px; text-align: right;">
                                    <?php if (!empty($h['raw_report_url'])): ?>
                                        <a href="<?= htmlspecialchars((string)$h['raw_report_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color: #45f3ff; text-decoration: none; font-size: 12px; font-weight: 600;">
                                            Ver no Google &rarr;
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #64748b;">--</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="font-size: 13px; color: #94a3b8; margin: 0;">Nenhum histórico disponível ainda. Dispare a primeira auditoria acima.</p>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL EXECUTAR AUDITORIA -->
<div id="audit_modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.15); border-radius: 14px; max-width: 480px; width: 90%; padding: 26px; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #fff;">Executar Nova Auditoria</h3>
            <button type="button" onclick="closeAuditModal()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form action="<?= $baseUrl ?>/admin/seo/audit" method="POST" onsubmit="showLoadingState(this)">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">URL para Análise</label>
                <input type="url" name="url" value="<?= htmlspecialchars($defaultUrl, ENT_QUOTES, 'UTF-8') ?>" required style="width: 100%; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-size: 13px; box-sizing: border-box;">
                <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 4px;">Padrão recomendado: https://rachidd.com</span>
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">Dispositivo / Estratégia</label>
                <select name="strategy" style="width: 100%; background: #06090e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 12px; color: #fff; font-size: 13px; box-sizing: border-box;">
                    <option value="both" selected>📱 Mobile + 💻 Desktop (Ambos)</option>
                    <option value="mobile">📱 Somente Mobile (Celular)</option>
                    <option value="desktop">💻 Somente Desktop (Computador)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAuditModal()" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #cbd5e1; padding: 10px 16px; border-radius: 6px; font-size: 13px; cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btn_submit_audit" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                    🚀 Iniciar Auditoria
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAuditModal() {
    var m = document.getElementById('audit_modal');
    if (m) m.style.display = 'flex';
}

function closeAuditModal() {
    var m = document.getElementById('audit_modal');
    if (m) m.style.display = 'none';
}

function showLoadingState(form) {
    var btn = document.getElementById('btn_submit_audit');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '⏳ Auditando via Google... Aguarde.';
    }
}

function copyAiPrompt() {
    var txt = document.getElementById('ai_prompt_text');
    var btn = document.getElementById('copy_btn');
    if (txt) {
        txt.select();
        txt.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(txt.value).then(function() {
            if (btn) {
                btn.innerHTML = '✓ Copiado!';
                setTimeout(function() { btn.innerHTML = '📋 Copiar Prompt'; }, 2000);
            }
        });
    }
}
</script>
