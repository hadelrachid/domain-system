<?php

declare(strict_types=1);

/**
 * View Pública: Apresentação e Índice do Curso
 *
 * @var array $course
 * @var string $title
 * @var string $desc
 * @var string $ctaButton
 * @var string $navHtml
 * @var string $baseUrl
 */

$price = (float)($course['price'] ?? 0.0);
$isFree = ($price > 0.0) ? false : (isset($course['is_free']) ? (bool)$course['is_free'] : true);
$maxInstallments = isset($course['max_installments']) && (int)$course['max_installments'] > 0
    ? (int)$course['max_installments']
    : 12;

$thumb = (string)($course['thumbnail'] ?? $course['image'] ?? '');
if (!empty($thumb)) {
    if (str_starts_with($thumb, 'http://') || str_starts_with($thumb, 'https://') || str_starts_with($thumb, 'data:')) {
        $thumbUrl = $thumb;
    } else {
        $thumbUrl = $baseUrl . '/' . ltrim($thumb, '/');
    }
} else {
    $thumbUrl = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360"><rect width="100%" height="100%" fill="%23141a23"/><text x="50%" y="50%" fill="%2345f3ff" font-family="sans-serif" font-size="28" font-weight="bold" text-anchor="middle" dominant-baseline="middle">🎓 ' . rawurlencode((string)$title) . '</text></svg>';
}
?>
<section class='ds-academy-course-overview' style='padding: 60px 20px; min-height: 75vh;'>
    <div class='container' style='max-width: 1100px; margin: 0 auto;'>
        <!-- Card Hero de Apresentação com Capa e Detalhes da Compra -->
        <div class='glass-panel ds-course-hero-card' style='padding: 40px; margin-bottom: 40px; border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 16px; background: rgba(15, 20, 28, 0.85); box-shadow: 0 16px 40px rgba(0,0,0,0.6);'>
            <div class='ds-course-hero-grid' style='display: grid; grid-template-columns: 1.25fr 0.95fr; gap: 36px; align-items: center;'>
                <!-- Coluna de Apresentação e Ação -->
                <div>
                    <div style='display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary, #45f3ff); padding: 0.35rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid rgba(69, 243, 255, 0.25); margin-bottom: 14px;'>
                        Curso Academy
                    </div>

                    <h1 style='color: #fff; font-size: 2.3rem; font-weight: 800; line-height: 1.25; margin-bottom: 14px;'><?= $title ?></h1>
                    <p style='color: var(--text-muted, #94a3b8); font-size: 1.05rem; line-height: 1.65; margin-bottom: 24px;'><?= $desc ?></p>
                    
                    <div style='margin-top: 10px;'>
                        <?= $ctaButton ?>
                    </div>
                </div>

                <!-- Coluna do Card Visual com Capa e Precificação -->
                <div style='background: #090d14; border: 1px solid rgba(69, 243, 255, 0.25); border-radius: 12px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,0.55);'>
                    <div style='position: relative; width: 100%; aspect-ratio: 16/9; background: #000; overflow: hidden;'>
                        <img src='<?= htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') ?>' alt='<?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?>' style='width: 100%; height: 100%; object-fit: cover; display: block;'>
                    </div>
                    <div style='padding: 22px;'>
                        <?php if ($isFree && $price <= 0.0): ?>
                            <div style='display: flex; align-items: center; justify-content: space-between;'>
                                <span style='background: rgba(40,167,69,0.15); color: #2ecc71; font-weight: 800; font-size: 0.9rem; padding: 6px 14px; border-radius: 6px; border: 1px solid rgba(46,204,113,0.3);'>
                                    ACESSO GRATUITO
                                </span>
                                <span style='color: var(--text-muted, #94a3b8); font-size: 0.85rem;'>Acesso Imediato</span>
                            </div>
                        <?php else: ?>
                            <div style='margin-bottom: 14px;'>
                                <div style='font-size: 2rem; font-weight: 800; color: #00d284;'>
                                    R$ <?= number_format($price, 2, ',', '.') ?>
                                </div>
                                <?php if ($maxInstallments > 1): ?>
                                    <?php $instVal = $price / $maxInstallments; ?>
                                    <div style='font-size: 0.92rem; color: var(--text-muted, #94a3b8); margin-top: 2px;'>
                                        em até <strong style='color: #45f3ff;'><?= $maxInstallments ?>x de R$ <?= number_format($instVal, 2, ',', '.') ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style='border-top: 1px solid rgba(255,255,255,0.08); padding-top: 14px; display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem; color: var(--text-muted, #94a3b8);'>
                                <div>⚡ Liberação imediata via Pix ou Cartão</div>
                                <div>📜 Certificado de Conclusão Incluso</div>
                                <div>♾️ Acesso Vitalício às Aulas e Atualizações</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade de Conteúdo & Grade Curricular -->
        <div class='glass-panel' style='padding: 30px; border: 1px solid rgba(69, 243, 255, 0.15); border-radius: 12px;'>
            <h3 style='margin-bottom: 20px; color: #fff; font-size: 1.4rem;'>Índice de Módulos &amp; Aulas</h3>
            <?= $navHtml ?>
        </div>
    </div>
</section>

<style>
@media (max-width: 860px) {
    .ds-course-hero-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
