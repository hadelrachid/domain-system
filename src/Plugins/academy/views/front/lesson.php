<?php
/**
 * View Pública: Sala de Aula (Lesson Classroom) com 3 Perfis de Layout Mobile
 *
 * @var string $courseUrl
 * @var string $courseTitle
 * @var string $lessonTitle
 * @var string $bodyContent
 * @var string $navHtml
 * @var string $paginationHtml
 * @var array<string, mixed> $adjacent
 * @var string $mobileProfile ('drawer' | 'bottom_bar' | 'cinema')
 */
$mobileProfile = in_array($mobileProfile ?? 'drawer', ['drawer', 'bottom_bar', 'cinema'], true)
    ? $mobileProfile
    : 'drawer';
$prevLesson = $adjacent['prev'] ?? null;
$nextLesson = $adjacent['next'] ?? null;
?>
<section class='ds-academy-classroom ds-mobile-profile-<?= htmlspecialchars($mobileProfile, ENT_QUOTES, 'UTF-8') ?>' data-mobile-profile='<?= htmlspecialchars($mobileProfile, ENT_QUOTES, 'UTF-8') ?>' style='padding: 40px 20px; min-height: 80vh;'>
    <div class='container' style='max-width: 1300px; margin: 0 auto;'>
        
        <!-- Barra Mobile Interativa de Índice de Aulas (visível em telas <= 900px) -->
        <div class='ds-academy-mobile-bar'>
            <button type='button' class='ds-academy-drawer-toggle' onclick='window.toggleAcademyMobileDrawer(true)' aria-label='Abrir Índice de Aulas'>
                <span>📚 Índice de Módulos &amp; Aulas</span>
                <span class='ds-drawer-pill'>Ver Aulas &#9776;</span>
            </button>
        </div>

        <!-- Backdrop do Drawer Mobile -->
        <div id='ds-academy-drawer-backdrop' class='ds-academy-drawer-backdrop' onclick='window.toggleAcademyMobileDrawer(false)'></div>

        <div class='ds-academy-grid' style='display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start;'>
            
            <!-- Sidebar / Drawer com Navegação do Curso -->
            <aside id='ds-academy-sidebar' class='ds-academy-sidebar glass-panel' style='padding: 24px; position: sticky; top: 100px; max-height: calc(100vh - 120px); overflow-y: auto;'>
                <div class='ds-academy-drawer-close-bar'>
                    <span style='font-size:0.8rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--primary, #45f3ff);'>Navegação do Curso</span>
                    <button type='button' onclick='window.toggleAcademyMobileDrawer(false)' class='ds-academy-drawer-close-btn' aria-label='Fechar menu'>✕</button>
                </div>
                <?= $navHtml ?>
            </aside>

            <!-- Conteúdo Principal da Aula -->
            <article class='ds-academy-main-article glass-panel' style='padding: 40px; min-width: 0;'>
                <header style='margin-bottom: 30px; border-bottom: 1px solid rgba(69, 243, 255, 0.15); padding-bottom: 20px;'>
                    <div style='font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;'>
                        <a href='<?= $courseUrl ?>' style='color: var(--primary, #45f3ff); text-decoration: none;'>&larr; <?= $courseTitle ?></a>
                    </div>
                    <h1 class='ds-academy-lesson-h1' style='color: #fff; font-size: 2.2rem; font-weight: 800; margin: 0;'><?= $lessonTitle ?></h1>
                </header>

                <div class='ds-academy-lesson-body' style='line-height: 1.8; color: var(--text-main, #c5c6c7);'>
                    <?= $bodyContent ?>
                </div>

                <?= $paginationHtml ?>
            </article>

        </div>

        <?php if ($mobileProfile === 'bottom_bar'): ?>
            <!-- Barra Inferior Fixa (Bottom Action Bar) exclusiva do perfil bottom_bar em telas <= 900px -->
            <div class='ds-academy-bottom-action-bar'>
                <?php if ($prevLesson): ?>
                    <a href='<?= htmlspecialchars((string)$prevLesson['url'], ENT_QUOTES, 'UTF-8') ?>' class='ds-bottom-bar-btn'>&larr; Anterior</a>
                <?php else: ?>
                    <span class='ds-bottom-bar-btn is-disabled'>&larr; Anterior</span>
                <?php endif; ?>
                <button type='button' class='ds-bottom-bar-btn is-primary' onclick='window.toggleAcademyMobileDrawer(true)'>
                    📚 Aulas
                </button>
                <?php if ($nextLesson): ?>
                    <a href='<?= htmlspecialchars((string)$nextLesson['url'], ENT_QUOTES, 'UTF-8') ?>' class='ds-bottom-bar-btn'>Próxima &rarr;</a>
                <?php else: ?>
                    <span class='ds-bottom-bar-btn is-disabled'>Próxima &rarr;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
    window.toggleAcademyMobileDrawer = function(open) {
        var sidebar = document.getElementById('ds-academy-sidebar');
        var backdrop = document.getElementById('ds-academy-drawer-backdrop');
        if (!sidebar || !backdrop) return;
        if (open) {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        } else {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    };
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.toggleAcademyMobileDrawer(false);
    });
</script>

<style>
    .ds-academy-mobile-bar {
        display: none;
        margin-bottom: 18px;
    }
    .ds-academy-bottom-action-bar {
        display: none;
    }
    .ds-academy-drawer-toggle {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 13px 18px;
        border-radius: 10px;
        background: rgba(20, 25, 28, 0.92);
        border: 1px solid rgba(69, 243, 255, 0.3);
        color: #fff;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
    }
    .ds-drawer-pill {
        background: rgba(69, 243, 255, 0.15);
        color: var(--primary, #45f3ff);
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.78rem;
        font-weight: 800;
    }
    .ds-academy-drawer-backdrop {
        display: none;
    }
    .ds-academy-drawer-close-bar {
        display: none;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 14px;
        margin-bottom: 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .ds-academy-drawer-close-btn {
        background: rgba(255, 255, 255, 0.08);
        border: none;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
    }
    .course-nav {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .course-nav-header {
        font-weight: 800;
        font-size: 1.1rem;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(69, 243, 255, 0.15);
    }
    .course-nav-home-link {
        color: #fff;
        text-decoration: none;
        transition: color 0.2s;
    }
    .course-nav-home-link:hover {
        color: var(--primary, #45f3ff);
    }
    .course-nav-module-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--primary, #45f3ff);
        margin: 12px 0 8px 0;
    }
    .course-nav-lessons {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .course-nav-lesson-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        color: var(--text-muted, #8c8f94);
        font-size: 0.92rem;
        transition: all 0.2s;
    }
    .course-nav-lesson-link:hover {
        background: rgba(69, 243, 255, 0.08);
        color: #fff;
    }
    .course-nav-lesson-item.is-active .course-nav-lesson-link {
        background: rgba(69, 243, 255, 0.15);
        color: var(--primary, #45f3ff);
        font-weight: 600;
        border-left: 3px solid var(--primary, #45f3ff);
    }
    .lesson-index {
        font-family: monospace;
        font-size: 0.8rem;
        opacity: 0.7;
    }
    @media (max-width: 900px) {
        .ds-academy-classroom {
            padding: 20px 12px !important;
        }
        .ds-academy-mobile-bar {
            display: block;
            position: sticky;
            top: 72px;
            z-index: 80;
        }
        .ds-academy-grid {
            grid-template-columns: 1fr !important;
        }
        .ds-academy-sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            width: 84vw !important;
            max-width: 340px !important;
            max-height: 100vh !important;
            z-index: 1200 !important;
            border-radius: 0 16px 16px 0 !important;
            background: #11161b !important;
            transform: translateX(-105%);
            transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 15px 0 40px rgba(0, 0, 0, 0.75);
        }
        .ds-academy-sidebar.is-open {
            transform: translateX(0);
        }
        .ds-academy-drawer-backdrop.is-open {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1150;
        }
        .ds-academy-drawer-close-bar {
            display: flex;
        }
        .ds-academy-main-article {
            padding: 22px 16px !important;
        }
        .ds-academy-lesson-h1 {
            font-size: 1.55rem !important;
        }
        .ds-academy-pagination {
            flex-direction: column !important;
        }
        .ds-academy-pagination > div,
        .ds-academy-pagination a {
            width: 100% !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
        /* Perfil Mobile 2: Barra Inferior Fixa (bottom_bar) */
        .ds-mobile-profile-bottom_bar {
            padding-bottom: 84px !important;
        }
        .ds-mobile-profile-bottom_bar .ds-academy-bottom-action-bar {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 14px;
            background: rgba(11, 14, 18, 0.96);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(69, 243, 255, 0.25);
            z-index: 1100;
            box-shadow: 0 -8px 25px rgba(0, 0, 0, 0.6);
        }
        .ds-bottom-bar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 8px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e6edf3;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .ds-bottom-bar-btn.is-primary {
            background: rgba(69, 243, 255, 0.16);
            border-color: rgba(69, 243, 255, 0.45);
            color: #45f3ff;
        }
        .ds-bottom-bar-btn.is-disabled {
            opacity: 0.35;
            pointer-events: none;
        }
        /* Perfil Mobile 3: Modo Cinema Sticky 16:9 (cinema) */
        .ds-mobile-profile-cinema .ds-block-video-wrapper {
            position: sticky;
            top: 64px;
            z-index: 95;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.75);
        }
    }
</style>
