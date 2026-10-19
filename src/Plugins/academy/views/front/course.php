<?php
/**
 * View Pública: Apresentação e Índice do Curso
 *
 * @var string $title
 * @var string $desc
 * @var string $ctaButton
 * @var string $navHtml
 */
?>
<section class='ds-academy-course-overview' style='padding: 60px 20px; min-height: 75vh;'>
    <div class='container' style='max-width: 1100px; margin: 0 auto;'>
        <div class='glass-panel' style='padding: 40px; margin-bottom: 40px; border: 1px solid rgba(69, 243, 255, 0.2);'>
            <div style='display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary, #45f3ff); padding: 0.35rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 1rem;'>
                Curso Academy
            </div>
            <h1 style='color: #fff; font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;'><?= $title ?></h1>
            <p style='color: var(--text-muted, #8c8f94); font-size: 1.15rem; line-height: 1.7; max-width: 800px; margin-bottom: 20px;'><?= $desc ?></p>
            <?= $ctaButton ?>
        </div>

        <div class='glass-panel' style='padding: 30px; border: 1px solid rgba(69, 243, 255, 0.15);'>
            <h3 style='margin-bottom: 20px; color: #fff; font-size: 1.4rem;'>Índice de Módulos &amp; Aulas</h3>
            <?= $navHtml ?>
        </div>
    </div>
</section>
