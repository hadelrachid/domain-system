<?php
/**
 * View Pública: Catálogo de Cursos Academy
 *
 * @var string $catalogHtml
 */
?>
<section class='ds-academy-catalog-section' style='padding: 60px 20px; min-height: 70vh;'>
    <div class='container' style='max-width: 1200px; margin: 0 auto;'>
        <header style='text-align: center; margin-bottom: 50px;'>
            <div style='display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary, #45f3ff); padding: 0.5rem 1.2rem; border-radius: 50px; font-size: 0.8rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid rgba(69, 243, 255, 0.3); margin-bottom: 1.5rem;'>
                Academy
            </div>
            <h1 style='font-size: 3rem; font-weight: 800; margin-bottom: 1rem; color: #fff; letter-spacing: -1.5px;'>
                Cursos &amp; Tutoriais
            </h1>
            <p style='font-size: 1.15rem; color: var(--text-muted, #8c8f94); max-width: 680px; margin: 0 auto; line-height: 1.7;'>
                Aprenda engenharia de software avançada, padrões arquiteturais e práticas modernas de desenvolvimento.
            </p>
        </header>
        <?= $catalogHtml ?>
    </div>
</section>
