<?php
/**
 * Template Name: Tutoriais & Academy Cursos
 * Descrição: Template preparado para receber os shortcodes de cards do plugin Academy.
 */
$safeTitle = htmlspecialchars($page['title'] ?? 'Tutoriais & Cursos', ENT_QUOTES, 'UTF-8');
?>
<section style="padding: 60px 20px;">
    <div class="container" style="max-width: 1200px; margin: 0 auto;">
        
        <header style="text-align: center; margin-bottom: 60px;">
            <div style="display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary); padding: 0.5rem 1.2rem; border-radius: 50px; font-size: 0.8rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid rgba(69, 243, 255, 0.3); margin-bottom: 1.5rem; box-shadow: 0 0 15px rgba(69, 243, 255, 0.2);">
                Academy
            </div>
            
            <h1 style="color: #fff; font-size: 3.5rem; font-weight: 800; margin-bottom: 1rem; letter-spacing: -2px; line-height: 1.1; text-shadow: 0 0 30px rgba(255, 255, 255, 0.1);">
                <?= $safeTitle ?>
            </h1>
            
            <p style="font-size: 1.15rem; color: var(--text-muted); max-width: 700px; margin: 0 auto; line-height: 1.8;">
                Bem-vindo ao centro de conhecimento. Selecione um dos nossos módulos abaixo para começar a masterizar a arquitetura de sistemas.
            </p>
        </header>

        <!-- Container principal onde os Shortcodes do Academy Cursos vão injetar os cards -->
        <div class="academy-courses-grid">
            <?= $page['content'] ?? '' ?>
        </div>
        
    </div>
</section>
