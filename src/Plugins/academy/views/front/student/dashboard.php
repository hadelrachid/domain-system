<?php

declare(strict_types=1);

/**
 * View do Cockpit / Painel do Aluno (Academy Student Workspace).
 */
$baseUrl = $baseUrl ?? '';
$studentName = htmlspecialchars((string)($studentName ?? 'Aluno'), ENT_QUOTES, 'UTF-8');
$studentUsername = !empty($studentUsername) ? htmlspecialchars((string)$studentUsername, ENT_QUOTES, 'UTF-8') : null;
$enrolledCourses = $enrolledCourses ?? [];
$availableCourses = $availableCourses ?? [];
$studentFiles = $studentFiles ?? [];
$csrfToken = htmlspecialchars((string)($csrfToken ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cockpit do Aluno | Academy LMS</title>
    <style>
        :root {
            --bg-body: #06090e;
            --bg-card: #0f141c;
            --bg-card-hover: #141c28;
            --border-color: rgba(69, 243, 255, 0.18);
            --accent-cyan: #45f3ff;
            --accent-green: #00d284;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); min-height: 100vh; padding: 24px 16px; }
        .ds-student-container { max-width: 1100px; margin: 0 auto; }
        .ds-student-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; padding-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 30px; }
        .ds-student-title h1 { font-size: 1.75rem; font-weight: 800; color: #fff; }
        .ds-student-title p { font-size: 0.95rem; color: var(--text-muted); margin-top: 4px; }
        .ds-student-nav { display: flex; gap: 10px; align-items: center; }
        .ds-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 6px; font-size: 0.88rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
        .ds-btn-outline { background: rgba(255,255,255,0.04); color: #cbd5e1; border-color: rgba(255,255,255,0.12); }
        .ds-btn-outline:hover { background: rgba(255,255,255,0.09); color: #fff; }
        .ds-btn-primary { background: linear-gradient(135deg, #00d284, #00b370); color: #000; font-weight: 700; box-shadow: 0 4px 15px rgba(0,210,132,0.25); }
        .ds-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,210,132,0.35); }
        .ds-section-title { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .ds-courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .ds-course-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.25s ease; box-shadow: 0 8px 24px rgba(0,0,0,0.35); }
        .ds-course-card:hover { transform: translateY(-3px); border-color: rgba(69,243,255,0.4); background: var(--bg-card-hover); }
        .ds-course-header { margin-bottom: 16px; }
        .ds-course-title { font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
        .ds-course-desc { font-size: 0.86rem; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .ds-progress-wrapper { margin: 16px 0; }
        .ds-progress-labels { display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; }
        .ds-progress-bar-bg { width: 100%; height: 8px; background: rgba(255,255,255,0.08); border-radius: 999px; overflow: hidden; }
        .ds-progress-bar-fill { height: 100%; background: linear-gradient(90deg, #45f3ff, #00d284); border-radius: 999px; transition: width 0.4s ease; }
        .ds-course-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.06); }
        .ds-empty-state { text-align: center; padding: 48px 20px; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 12px; grid-column: 1 / -1; }
        .ds-storage-box { background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 22px; }
    </style>
</head>
<body>
    <div class="ds-student-container">
        <header class="ds-student-header">
            <div class="ds-student-title">
                <h1>🎓 Cockpit do Aluno</h1>
                <?php if (!empty($isAdmin)): ?>
                    <p>Bem-vindo(a), <strong><?= $studentName ?></strong><?php if ($studentUsername): ?> <span style="color: var(--accent-cyan); font-weight: 600;">(@<?= $studentUsername ?>)</span><?php endif; ?> <span style="background: rgba(0, 210, 132, 0.15); color: #00d284; font-size: 0.75rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; border: 1px solid rgba(0, 210, 132, 0.3); margin-left: 6px;">👑 Administrador</span> — Acesso total e irrestrito a todos os cursos e aulas.</p>
                <?php else: ?>
                    <p>Bem-vindo(a) de volta, <strong><?= $studentName ?></strong><?php if ($studentUsername): ?> <span style="color: var(--accent-cyan); font-weight: 600;">(@<?= $studentUsername ?>)</span><?php endif; ?>! Bons estudos.</p>
                <?php endif; ?>
            </div>
            <nav class="ds-student-nav">
                <?php if (!empty($isAdmin)): ?>
                    <a href="<?= $baseUrl ?>/admin" class="ds-btn ds-btn-outline" style="border-color: rgba(69,243,255,0.4); color: #45f3ff;">⚙️ Painel Admin</a>
                <?php endif; ?>
                <a href="<?= $baseUrl ?>/cursos" class="ds-btn ds-btn-outline">🌐 Ver Catálogo</a>
                <a href="<?= $baseUrl ?>/aluno/logout" class="ds-btn ds-btn-outline" style="color: #ef4444;">🚪 Sair</a>
            </nav>
        </header>

        <?php if (!empty($flash) && !empty($flash['msg'])): ?>
            <div style="margin-bottom: 24px; padding: 14px 20px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 12px; background: <?= ($flash['type'] ?? '') === 'error' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(0, 210, 132, 0.15)' ?>; color: <?= ($flash['type'] ?? '') === 'error' ? '#ef4444' : '#00d284' ?>; border: 1px solid <?= ($flash['type'] ?? '') === 'error' ? 'rgba(239, 68, 68, 0.3)' : 'rgba(0, 210, 132, 0.3)' ?>;">
                <span style="font-size: 1.2rem;"><?= ($flash['type'] ?? '') === 'error' ? '⚠️' : '🎉' ?></span>
                <span><?= htmlspecialchars((string)$flash['msg'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <section>
            <h2 class="ds-section-title">📚 Meus Cursos Matriculados</h2>
            <div class="ds-courses-grid">
                <?php if (empty($enrolledCourses)): ?>
                    <div class="ds-empty-state">
                        <div style="font-size: 3rem; margin-bottom: 12px;">🎒</div>
                        <h3 style="color: #fff; font-size: 1.25rem; margin-bottom: 6px;">Você ainda não possui cursos matriculados</h3>
                        <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 480px; margin: 0 auto 20px auto;">
                            Explore nosso catálogo e comece a estudar com apostilas completas em PDF, videoaulas e códigos interativos.
                        </p>
                        <a href="<?= $baseUrl ?>/cursos" class="ds-btn ds-btn-primary">Explorar Cursos Disponíveis &rarr;</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($enrolledCourses as $item): ?>
                        <?php 
                            $c = $item['course']; 
                            $pct = (float)$item['progress_pct'];
                            $resumeUrl = $item['resume_url'] ?: $item['course_url'];
                            $cRawThumb = (string)($c['thumbnail'] ?? $c['image'] ?? '');
                            if (!empty($cRawThumb)) {
                                $cThumb = (str_starts_with($cRawThumb, 'http://') || str_starts_with($cRawThumb, 'https://') || str_starts_with($cRawThumb, 'data:'))
                                    ? $cRawThumb
                                    : $baseUrl . '/' . ltrim($cRawThumb, '/');
                            } else {
                                $cThumb = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360"><rect width="100%" height="100%" fill="%23141a23"/><text x="50%" y="50%" fill="%2345f3ff" font-family="sans-serif" font-size="28" font-weight="bold" text-anchor="middle" dominant-baseline="middle">🎓 '.rawurlencode((string)$c['title']).'</text></svg>';
                            }
                        ?>
                        <article class="ds-course-card">
                            <div style="width: 100%; aspect-ratio: 16/9; background: #000; overflow: hidden; border-radius: 8px; margin-bottom: 14px; border: 1px solid rgba(255,255,255,0.06);">
                                <img src="<?= htmlspecialchars($cThumb, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
                            </div>
                            <div class="ds-course-header">
                                <h3 class="ds-course-title"><?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="ds-course-desc"><?= htmlspecialchars(strip_tags((string)($c['description'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>

                            <div>
                                <div class="ds-progress-wrapper">
                                    <div class="ds-progress-labels">
                                        <span style="color: var(--text-muted);"><?= (int)$item['completed_count'] ?> de <?= (int)$item['total_lessons'] ?> aulas</span>
                                        <span style="color: var(--accent-cyan);"><?= $pct ?>%</span>
                                    </div>
                                    <div class="ds-progress-bar-bg">
                                        <div class="ds-progress-bar-fill" style="width: <?= min(100.0, max(0.0, $pct)) ?>%;"></div>
                                    </div>
                                </div>

                                <div class="ds-course-footer">
                                    <a href="<?= htmlspecialchars($item['course_url'], ENT_QUOTES, 'UTF-8') ?>" class="ds-btn ds-btn-outline" style="font-size: 0.8rem;">Ver Índice</a>
                                    <a href="<?= htmlspecialchars($resumeUrl, ENT_QUOTES, 'UTF-8') ?>" class="ds-btn ds-btn-primary">Continuar Aula &rarr;</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($availableCourses)): ?>
        <section style="margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                <h2 class="ds-section-title" style="margin-bottom: 0;">
                    🛒 Cursos Disponíveis para Compra &amp; Novas Formações
                </h2>
                <span style="font-size: 0.85rem; color: #45f3ff; background: rgba(69,243,255,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(69,243,255,0.25);">
                    Liberados na sua conta instantaneamente
                </span>
            </div>

            <div class="ds-courses-grid">
                <?php foreach ($availableCourses as $ac): ?>
                    <?php 
                        $price = (float)($ac['price'] ?? 0.0);
                        $isFree = isset($ac['is_free']) ? (bool)$ac['is_free'] : ($price <= 0.0);
                        $maxInst = (int)($ac['max_installments'] ?? 12);
                        if ($maxInst <= 0) $maxInst = 12;

                        $plat = strtolower(trim((string)($ac['external_platform'] ?? '')));
                        $checkoutUrl = trim((string)($ac['external_checkout_url'] ?? ''));
                        if ($checkoutUrl === '') {
                            $checkoutUrl = trim((string)($ac['link'] ?? ''));
                        }

                        // Define o destino do botão de compra
                        if ($isFree || $price <= 0.0) {
                            $btnLabel = "Acessar Gratuitamente &rarr;";
                            $btnTarget = $baseUrl . '/cursos/' . urlencode((string)$ac['slug']);
                            $isExternal = false;
                        } elseif ($checkoutUrl !== '' && (str_starts_with($checkoutUrl, 'http://') || str_starts_with($checkoutUrl, 'https://'))) {
                            $platName = match($plat) {
                                'hotmart'   => 'Hotmart',
                                'eduzz'     => 'Eduzz',
                                'monetizze' => 'Monetizze',
                                'clickbank' => 'ClickBank',
                                default     => 'Plataforma Oficial',
                            };
                            $btnLabel = "Comprar via {$platName} &rarr;";
                            $btnTarget = $checkoutUrl;
                            $isExternal = true;
                        } else {
                            $btnLabel = "Comprar";
                            $btnTarget = $baseUrl . '/checkout/curso/' . (int)$ac['id'];
                            $isExternal = false;
                        }

                        $acRawThumb = (string)($ac['thumbnail'] ?? $ac['image'] ?? '');
                        if (!empty($acRawThumb)) {
                            $acThumb = (str_starts_with($acRawThumb, 'http://') || str_starts_with($acRawThumb, 'https://') || str_starts_with($acRawThumb, 'data:'))
                                ? $acRawThumb
                                : $baseUrl . '/' . ltrim($acRawThumb, '/');
                        } else {
                            $acThumb = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360"><rect width="100%" height="100%" fill="%23141a23"/><text x="50%" y="50%" fill="%2345f3ff" font-family="sans-serif" font-size="28" font-weight="bold" text-anchor="middle" dominant-baseline="middle">🎓 '.rawurlencode((string)$ac['title']).'</text></svg>';
                        }
                    ?>
                    <article class="ds-course-card" style="border-color: rgba(0, 210, 132, 0.25); background: #0c1219;">
                        <div style="width: 100%; aspect-ratio: 16/9; background: #000; overflow: hidden; border-radius: 8px; margin-bottom: 14px; border: 1px solid rgba(255,255,255,0.06);">
                            <img src="<?= htmlspecialchars($acThumb, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)$ac['title'], ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
                        </div>
                        <div class="ds-course-header">
                            <h3 class="ds-course-title"><?= htmlspecialchars((string)$ac['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="ds-course-desc"><?= htmlspecialchars(strip_tags((string)($ac['description'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div>
                            <div style="margin: 14px 0; padding: 12px; background: rgba(0,0,0,0.3); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                                <?php if ($isFree || $price <= 0.0): ?>
                                    <div style="color: #2ecc71; font-weight: 800; font-size: 1rem;">GRATUITO</div>
                                    <div style="color: var(--text-muted); font-size: 0.8rem;">Acesso Livre</div>
                                <?php else: ?>
                                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                        <div style="color: #00d284; font-weight: 800; font-size: 1.25rem;">
                                            R$ <?= number_format($price, 2, ',', '.') ?>
                                        </div>
                                        <?php if ($maxInst > 1): ?>
                                            <?php $instVal = $price / $maxInst; ?>
                                            <div style="color: #45f3ff; font-size: 0.82rem; font-weight: 600;">
                                                ou <?= $maxInst ?>x de R$ <?= number_format($instVal, 2, ',', '.') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="ds-course-footer">
                                <a href="<?= $baseUrl ?>/cursos/<?= urlencode((string)$ac['slug']) ?>" class="ds-btn ds-btn-outline" style="font-size: 0.8rem;">Ver Detalhes</a>
                                <a href="<?= htmlspecialchars($btnTarget, ENT_QUOTES, 'UTF-8') ?>" <?= $isExternal ? 'target="_blank"' : '' ?> class="ds-btn ds-btn-primary" style="font-size: 0.85rem;">
                                    <?= $btnLabel ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="ds-storage-box">
            <h2 class="ds-section-title" style="margin-bottom: 10px;">📁 Minha Pasta Privada de Estudos</h2>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px;">
                Seu espaço seguro e individual para anotações de aulas e resumos pessoais. Acesso estritamente privado.
            </p>
            <?php if (empty($studentFiles)): ?>
                <div style="padding: 18px; text-align: center; color: var(--text-muted); font-size: 0.88rem; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    📝 Suas anotações feitas dentro das aulas aparecerão organizadas aqui.
                </div>
            <?php else: ?>
                <ul style="list-style: none;">
                    <?php foreach ($studentFiles as $f): ?>
                        <li style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.88rem;">
                            <span>📄 <?= htmlspecialchars((string)$f['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="color: var(--text-muted);"><?= number_format($f['size'] / 1024, 1) ?> KB</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
