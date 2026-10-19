<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Services\LessonBlockRenderer;
use Throwable;

class LessonAdminController
{
    private CourseRepositoryInterface $courseRepo;
    private CourseStructureRepositoryInterface $structureRepo;
    private SessionManagerInterface $session;
    private ?ThemeManagerInterface $themeManager;
    private LessonBlockRenderer $blockRenderer;

    public function __construct(
        CourseRepositoryInterface $courseRepo,
        CourseStructureRepositoryInterface $structureRepo,
        SessionManagerInterface $session,
        ?ThemeManagerInterface $themeManager = null,
        ?LessonBlockRenderer $blockRenderer = null
    ) {
        $this->courseRepo = $courseRepo;
        $this->structureRepo = $structureRepo;
        $this->session = $session;
        $this->themeManager = $themeManager;
        $this->blockRenderer = $blockRenderer ?? new LessonBlockRenderer();
    }

    public function editor(Request $request, $id): ResponseInterface
    {
        $lessonId = (int)$id;
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            $this->session->setFlash('error', "Aula não encontrada.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $course = $this->courseRepo->findById((int)$lesson['course_id']);
        if (!$course) {
            $this->session->setFlash('error', "Curso vinculado à aula não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $moduleTitle = 'Módulo Geral';
        $allModules = $this->structureRepo->getModules((int)$course['id']);
        $allCourseLessons = $this->structureRepo->getLessons((int)$course['id']);
        if (!empty($lesson['module_id'])) {
            foreach ($allModules as $m) {
                if ((int)$m['id'] === (int)$lesson['module_id']) {
                    $moduleTitle = (string)$m['title'];
                    break;
                }
            }
        }

        // Identifica aulas anterior e próxima para o preview realista
        $prevLessonTitle = null;
        $nextLessonTitle = null;
        $flatLessons = array_values($allCourseLessons);
        foreach ($flatLessons as $idx => $fl) {
            if ((int)$fl['id'] === $lessonId) {
                if ($idx > 0) {
                    $prevLessonTitle = (string)$flatLessons[$idx - 1]['title'];
                }
                if ($idx < count($flatLessons) - 1) {
                    $nextLessonTitle = (string)$flatLessons[$idx + 1]['title'];
                }
                break;
            }
        }

        // Prepara blocos existentes
        $rawContent = (string)($lesson['content'] ?? '');
        $initialBlocks = [];

        if ($rawContent !== '') {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $initialBlocks = $decoded['blocks'] ?? (isset($decoded[0]['type']) ? $decoded : []);
            } else {
                // Se for texto simples / legado, inicializa um bloco de parágrafo com o conteúdo
                $initialBlocks = [
                    [
                        'id'   => 'b_init_1',
                        'type' => 'paragraph',
                        'data' => ['text' => $rawContent]
                    ]
                ];
            }
        }

        // Se estiver completamente vazio, inicia com título e parágrafo
        if (empty($initialBlocks)) {
            $initialBlocks = [
                [
                    'id'   => 'b_init_h',
                    'type' => 'header',
                    'data' => ['level' => 2, 'text' => (string)$lesson['title']]
                ],
                [
                    'id'   => 'b_init_p',
                    'type' => 'paragraph',
                    'data' => ['text' => 'Digite aqui o conteúdo da aula ou adicione novos blocos abaixo...']
                ]
            ];
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $siteLessonUrl = $baseUrl . '/cursos/' . urlencode((string)$course['slug']) . '/' . urlencode((string)$lesson['slug']);

        ob_start();
        ?>
        <div id="editor_root_wrapper" style="max-width: 1400px; margin: 0 auto; padding-bottom: 80px; color: var(--text-main, #fff);">
            
            <!-- HEADER BAR FIXO -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; background: var(--bg-panel, #1d2327); padding: 16px 24px; border-radius: 8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); flex-wrap:wrap; gap:15px; position:sticky; top:15px; z-index:90; box-shadow:0 8px 25px rgba(0,0,0,0.5); backdrop-filter:blur(10px);">
                <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                    <a href="<?= $baseUrl ?>/admin/academy/edit/<?= $course['id'] ?>" class="btn btn-outline" style="padding: 7px 14px; border: 1px solid #4a545a; border-radius: 6px; color: #fff; text-decoration: none; font-size: 13px;">
                        &larr; Voltar ao Curso
                    </a>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--accent-blue, #45f3ff); letter-spacing: 0.8px; font-weight: 600;">
                            <?= htmlspecialchars((string)$course['title']) ?> &bull; <?= htmlspecialchars($moduleTitle) ?>
                        </div>
                        <input type="text" id="lesson_title_input" oninput="refreshLivePreview()" value="<?= htmlspecialchars((string)$lesson['title']) ?>" style="background:transparent; border:none; border-bottom:1px dashed #4a545a; color:#fff; font-size:1.15rem; font-weight:bold; outline:none; padding:2px 4px; min-width:280px;" title="Clique para editar o título da aula">
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <!-- SELETOR DE MODO DE VISUALIZAÇÃO DINÂMICA -->
                    <div style="display:inline-flex; background:#14191c; border:1px solid #3a444a; border-radius:6px; overflow:hidden; padding:2px;">
                        <button type="button" id="btn_mode_edit" onclick="setEditorViewMode('edit')" style="padding:6px 12px; border:none; border-radius:4px; background:transparent; color:#8c8f94; font-size:12px; font-weight:600; cursor:pointer;">
                            ✏️ Blocos
                        </button>
                        <button type="button" id="btn_mode_split" onclick="setEditorViewMode('split')" style="padding:6px 12px; border:none; border-radius:4px; background:rgba(69,243,255,0.15); color:#45f3ff; font-size:12px; font-weight:600; cursor:pointer;">
                            ⚡ Dinâmico (Lado a Lado)
                        </button>
                        <button type="button" id="btn_mode_preview" onclick="setEditorViewMode('preview')" style="padding:6px 12px; border:none; border-radius:4px; background:transparent; color:#8c8f94; font-size:12px; font-weight:600; cursor:pointer;">
                            👁️ Aula Final
                        </button>
                    </div>

                    <select id="lesson_status_select" style="padding:8px 12px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; font-size:13px;">
                        <option value="published" <?= $lesson['status'] === 'published' ? 'selected' : '' ?>>Publicada</option>
                        <option value="draft" <?= $lesson['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                    </select>

                    <a href="<?= $siteLessonUrl ?>" target="_blank" class="btn btn-outline" style="padding: 8px 14px; border: 1px solid rgba(69,243,255,0.3); color: var(--accent-blue, #45f3ff); border-radius: 6px; text-decoration: none; font-size: 13px; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-external-link-alt"></i> Ver no Site
                    </a>

                    <button type="button" id="btn_save_lesson" onclick="saveLessonBlocks()" class="btn btn-activate" style="padding: 8px 20px; font-weight: 600; cursor: pointer; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fas fa-save"></i> <span id="btn_save_text">Salvar Aula</span>
                    </button>
                </div>
            </div>

            <?php if (($course['status'] ?? '') !== 'published'): ?>
                <div style="margin-bottom: 20px; padding: 12px 18px; border-radius: 8px; background: rgba(245, 166, 35, 0.12); border: 1px solid rgba(245, 166, 35, 0.4); color: #f5a623; font-size: 13px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span>
                        ⚠️ <strong>Atenção:</strong> O curso <strong><?= htmlspecialchars((string)$course['title']) ?></strong> está com status <strong>"<?= ($course['status'] ?? '') === 'soon' ? 'Em Breve' : 'Rascunho' ?>"</strong>. Enquanto o curso não estiver <strong>Publicado</strong>, o link público <em>"Ver no Site"</em> retornará 404 para visitantes.
                    </span>
                    <a href="<?= $baseUrl ?>/admin/academy/edit/<?= $course['id'] ?>" style="color: #fff; background: #f5a623; color: #000; padding: 5px 12px; border-radius: 5px; text-decoration: none; font-weight: 700; font-size: 12px;">
                        Alterar Status do Curso &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- WORKSPACE GRID (EDIÇÃO + PREVIEW AO VIVO) -->
            <div id="editor_workspace_grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items:start;">
                
                <!-- COLUNA 1: CANVAS PRINCIPAL DE BLOCOS -->
                <div id="editor_blocks_column" style="background: var(--bg-panel, #1d2327); padding: 28px; border-radius: 8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); min-height: 500px; min-width: 0;">
                    <div style="margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 14px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <div>
                            <h2 style="margin:0; font-size:1.25rem; display:flex; align-items:center; gap:10px;">
                                <i class="fas fa-cubes" style="color:var(--accent-blue, #45f3ff);"></i> Editor em Blocos da Aula
                            </h2>
                            <small style="color:var(--text-muted, #8c8f94);">Edite os blocos abaixo e acompanhe a renderização em tempo real.</small>
                        </div>
                        <span id="save_indicator" style="font-size:12px; color:var(--accent-green, #00d284); display:none;">
                            <i class="fas fa-check-circle"></i> Todas as alterações salvas
                        </span>
                    </div>

                    <!-- CONTÊINER DOS BLOCOS -->
                    <div id="blocks_canvas" style="display:flex; flex-direction:column; gap:16px;"></div>

                    <!-- BARRA INFERIOR DE ADIÇÃO DE BLOCOS -->
                    <div style="margin-top: 25px; padding: 18px; border: 2px dashed rgba(69,243,255,0.25); border-radius: 8px; background: rgba(0,0,0,0.2); text-align:center;">
                        <div style="font-size: 12px; font-weight: 600; color: var(--accent-blue, #45f3ff); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">
                            + Adicionar Novo Bloco
                        </div>
                        <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                            <button type="button" onclick="addBlock('header')" class="btn-block-add">📌 Título</button>
                            <button type="button" onclick="addBlock('paragraph')" class="btn-block-add">📝 Parágrafo</button>
                            <button type="button" onclick="addBlock('video')" class="btn-block-add">🎬 Vídeo (16:9)</button>
                            <button type="button" onclick="addBlock('code')" class="btn-block-add">💻 Código (PHP/JS)</button>
                            <button type="button" onclick="addBlock('callout')" class="btn-block-add">💡 Dica / Alerta</button>
                            <button type="button" onclick="addBlock('list')" class="btn-block-add">📋 Lista</button>
                            <button type="button" onclick="addBlock('image')" class="btn-block-add">🖼️ Imagem</button>
                            <button type="button" onclick="addBlock('delimiter')" class="btn-block-add">➖ Divisor</button>
                        </div>
                    </div>
                </div>

                <!-- COLUNA 2: PRÉ-VISUALIZAÇÃO DINÂMICA EM TEMPO REAL -->
                <div id="editor_preview_column" style="background: #0f1317; padding: 24px; border-radius: 8px; border: 1px solid rgba(69,243,255,0.22); min-height: 500px; position: sticky; top: 95px; max-height: calc(100vh - 115px); overflow-y: auto; min-width: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.35);">
                    <!-- BARRA DE CONTROLE DO PREVIEW (DESKTOP vs MOBILE + PERFIL) -->
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid rgba(69,243,255,0.15);">
                        <span style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; color:#45f3ff; display:inline-flex; align-items:center; gap:6px;">
                            <span style="width:8px; height:8px; border-radius:50%; background:#00d284; display:inline-block; box-shadow:0 0 8px #00d284;"></span>
                            Pré-visualização Ao Vivo
                        </span>

                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <!-- Alternador Desktop / Mobile -->
                            <div style="display:inline-flex; background:#14191c; border:1px solid #3a444a; border-radius:6px; overflow:hidden; padding:2px;">
                                <button type="button" id="btn_device_desktop" onclick="setPreviewDevice('desktop')" style="padding:4px 10px; border:none; border-radius:4px; background:rgba(69,243,255,0.15); color:#45f3ff; font-size:11px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                    🖥️ Desktop
                                </button>
                                <button type="button" id="btn_device_mobile" onclick="setPreviewDevice('mobile')" style="padding:4px 10px; border:none; border-radius:4px; background:transparent; color:#8c8f94; font-size:11px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                    📱 Mobile
                                </button>
                            </div>

                            <!-- Seletor de Perfil/Modelo Mobile & Tablet -->
                            <select id="mobile_profile_select" onchange="setMobileProfile(this.value)" style="display:none; padding:4px 8px; border-radius:5px; border:1px solid rgba(69,243,255,0.35); background:#14191c; color:#45f3ff; font-size:11px; font-weight:600;">
                                <optgroup label="📱 Smartphones">
                                    <option value="compact_phone">📱 Celular Compacto — 320px (iPhone SE)</option>
                                    <option value="standard" selected>📱 Smartphone Padrão — 375px (iPhone 14 / Android)</option>
                                    <option value="large_phone">📱 Smartphone Max — 430px (Pro Max / Ultra)</option>
                                </optgroup>
                                <optgroup label="📟 Tablets">
                                    <option value="tablet_portrait">📟 Tablet Retrato — 768px (iPad Mini / Air)</option>
                                    <option value="tablet_landscape">📟 Tablet Paisagem — 1024px (iPad Pro)</option>
                                </optgroup>
                            </select>
                            <span id="device_dimension_badge" style="display:none; font-family:monospace; font-size:10px; padding:3px 7px; border-radius:4px; background:rgba(69,243,255,0.08); border:1px solid rgba(69,243,255,0.2); color:#8c8f94;">375 &times; 667 px</span>
                        </div>
                    </div>

                    <!-- MOLDURA DO DISPOSITIVO (DESKTOP, SMARTPHONE OU TABLET) -->
                    <div id="preview_device_stage" style="width:100%; transition: all 0.25s ease; position:relative; overflow:hidden;">
                        <!-- Notch / Status Bar (visível apenas no modo Mobile/Tablet) -->
                        <div id="mobile_device_statusbar" style="display:none; justify-content:space-between; align-items:center; padding:8px 18px 6px 18px; background:#080a0d; border-bottom:1px solid rgba(255,255,255,0.06); font-size:11px; font-weight:700; color:#8c8f94; border-radius:26px 26px 0 0;">
                            <span>9:41</span>
                            <span id="mobile_device_notch" style="width:60px; height:5px; border-radius:10px; background:rgba(255,255,255,0.15); display:inline-block;"></span>
                            <span id="mobile_device_signal">5G 🔋</span>
                        </div>

                        <!-- HEADER DO SITE INTERATIVO (visível no modo Mobile/Tablet) -->
                        <div id="mobile_site_header" style="display:none; flex-direction:column; background:rgba(11, 12, 16, 0.95); border-bottom:1px solid rgba(69,243,255,0.15);">
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px;">
                                <div style="font-size:1.05rem; font-weight:800; color:#fff; letter-spacing:-0.5px;">
                                    Rachid<span style="color:#45f3ff;">D.com</span>
                                </div>
                                <button type="button" onclick="toggleMobilePreviewMenu('header')" style="background:rgba(69,243,255,0.1); border:1px solid rgba(69,243,255,0.3); color:#45f3ff; border-radius:6px; padding:4px 9px; font-size:14px; cursor:pointer;" title="Testar Menu Mobile do Header">
                                    &#9776;
                                </button>
                            </div>
                            <div id="mobile_site_nav_dropdown" style="display:none; flex-direction:column; padding:8px 14px 12px 14px; background:#0b0c10; border-top:1px solid rgba(255,255,255,0.06); gap:6px; font-size:12px;">
                                <div style="padding:5px 8px; color:#8c8f94; border-radius:4px;">🏠 Início</div>
                                <div style="padding:5px 8px; color:#8c8f94; border-radius:4px;">📘 OS Docs</div>
                                <div style="padding:5px 8px; color:#45f3ff; background:rgba(69,243,255,0.1); font-weight:700; border-radius:4px;">🎓 Cursos &amp; Tutoriais (Ativo)</div>
                                <div style="padding:5px 8px; color:#8c8f94; border-radius:4px;">📕 Livro SOLID</div>
                                <div style="padding:5px 8px; color:#8c8f94; border-radius:4px;">ℹ️ Sobre</div>
                            </div>
                        </div>

                        <!-- BARRA E GAVETA (DRAWER) DE AULAS INTERATIVA (visível no modo Mobile/Tablet) -->
                        <div id="mobile_course_drawer_bar" style="display:none; flex-direction:column; padding:10px 14px 0 14px;">
                            <button type="button" onclick="toggleMobilePreviewMenu('lessons')" style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:9px 12px; border-radius:8px; background:rgba(20,25,28,0.95); border:1px solid rgba(69,243,255,0.3); color:#fff; font-size:12px; font-weight:700; cursor:pointer;">
                                <span>📚 Índice de Módulos &amp; Aulas</span>
                                <span style="background:rgba(69,243,255,0.15); color:#45f3ff; padding:2px 8px; border-radius:20px; font-size:10px;">Ver Aulas (<span id="mobile_lessons_count"><?= count($flatLessons) ?></span>) &#9662;</span>
                            </button>
                            <div id="mobile_lessons_drawer" style="display:none; margin-top:8px; padding:12px; border-radius:8px; background:#0b0e12; border:1px solid rgba(69,243,255,0.25); max-height:220px; overflow-y:auto;">
                                <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#45f3ff; letter-spacing:1px; margin-bottom:8px;">
                                    <?= htmlspecialchars($moduleTitle) ?>
                                </div>
                                <?php foreach ($flatLessons as $i => $fl): 
                                    $isCur = ((int)$fl['id'] === $lessonId);
                                ?>
                                    <div style="padding:6px 8px; border-radius:5px; font-size:11px; margin-bottom:4px; display:flex; align-items:center; gap:8px; <?= $isCur ? 'background:rgba(69,243,255,0.15); color:#45f3ff; font-weight:700; border-left:3px solid #45f3ff;' : 'color:#8c8f94;' ?>">
                                        <span style="font-family:monospace; opacity:0.7;"><?= sprintf('%02d', $i + 1) ?></span>
                                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars((string)$fl['title']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="preview_device_viewport" style="padding: 8px 4px; transition: all 0.25s ease;">
                            <header id="live_preview_header" style="margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.08);">
                                <div style="font-size:12px; color:#45f3ff; margin-bottom:6px;">&larr; <?= htmlspecialchars((string)$course['title']) ?></div>
                                <h1 id="live_preview_lesson_title" style="margin:0; font-size:1.85rem; font-weight:800; color:#fff; line-height:1.25;"><?= htmlspecialchars((string)$lesson['title']) ?></h1>
                            </header>
                            <div id="live_preview_body" class="ds-lesson-blocks-content" style="line-height:1.8; color:#c5c6c7;"></div>

                            <!-- PAGINAÇÃO E FOOTER MOBILE (visível no modo Mobile/Tablet) -->
                            <div id="mobile_preview_footer_zone" style="display:none; flex-direction:column; margin-top:24px; padding-top:16px; border-top:1px solid rgba(69,243,255,0.15); gap:10px;">
                                <?php if ($prevLessonTitle): ?>
                                    <div style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.15); color:#c5c6c7; font-size:11px; text-align:center; box-sizing:border-box;">
                                        &larr; Anterior: <strong><?= htmlspecialchars($prevLessonTitle) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if ($nextLessonTitle): ?>
                                    <div style="width:100%; padding:10px 12px; border-radius:8px; background:rgba(69,243,255,0.15); border:1px solid rgba(69,243,255,0.4); color:#45f3ff; font-size:11px; font-weight:700; text-align:center; box-sizing:border-box;">
                                        Próxima: <?= htmlspecialchars($nextLessonTitle) ?> &rarr;
                                    </div>
                                <?php endif; ?>

                                <footer style="margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.06); text-align:center; color:#64748b; font-size:10px;">
                                    <div style="font-weight:800; color:#fff; font-size:11px; margin-bottom:4px;">Rachid<span style="color:#45f3ff;">D.com</span></div>
                                    <div style="display:flex; justify-content:center; gap:10px; margin-bottom:6px; color:#8c8f94;">
                                        <span>Sobre Nós</span> &bull; <span>Privacidade</span> &bull; <span>Termos</span>
                                    </div>
                                    <div>&copy; <?= date('Y') ?> RachidD. Todos os direitos reservados.</div>
                                </footer>
                            </div>
                        </div>

                        <!-- Barra inferior Home Indicator (visível apenas no modo Mobile/Tablet) -->
                        <div id="mobile_device_homebar" style="display:none; justify-content:center; align-items:center; padding:10px 0 12px 0; background:#080a0d; border-top:1px solid rgba(255,255,255,0.05); border-radius:0 0 26px 26px;">
                            <span style="width:100px; height:4px; border-radius:10px; background:rgba(255,255,255,0.22); display:inline-block;"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <style>
            .btn-block-add {
                background: #14191c;
                border: 1px solid #3a444a;
                color: #c5c6c7;
                padding: 8px 14px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            .btn-block-add:hover {
                border-color: var(--accent-blue, #45f3ff);
                color: #fff;
                background: rgba(69, 243, 255, 0.1);
                transform: translateY(-1px);
            }
            .ds-block-card {
                background: #14191c;
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 6px;
                padding: 16px;
                transition: all 0.2s ease;
            }
            .ds-block-card:hover {
                border-color: rgba(69, 243, 255, 0.3);
            }
            .ds-block-ctrl-btn {
                background: transparent;
                border: none;
                color: #8c8f94;
                cursor: pointer;
                font-size: 13px;
                padding: 4px 6px;
                border-radius: 4px;
                transition: color 0.2s;
            }
            .ds-block-ctrl-btn:hover {
                color: #fff;
                background: rgba(255,255,255,0.06);
            }
            @media (max-width: 1024px) {
                #editor_workspace_grid {
                    grid-template-columns: 1fr !important;
                }
                #editor_preview_column {
                    position: static !important;
                    max-height: none !important;
                }
            }
        </style>

        <script>
        (function() {
            var lessonId = <?= $lessonId ?>;
            var csrfToken = <?= json_encode($csrfToken) ?>;
            var baseUrl = <?= json_encode($baseUrl) ?>;
            var blocks = <?= json_encode($initialBlocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
            var currentViewMode = 'split';
            var currentDevice = 'desktop';
            var currentMobileProfile = 'standard';

            // Catálogo padrão de dispositivos Mobile & Tablet (Chrome DevTools / Apple / Android standards)
            var MOBILE_PROFILES = {
                compact_phone: {
                    id: 'compact_phone',
                    label: 'Celular Compacto (320px)',
                    dimensions: '320 \u00d7 568 px',
                    width: '320px',
                    borderRadius: '24px',
                    borderWidth: '3px',
                    signalText: '4G 🔋',
                    titleSize: '1.2rem',
                    fontSize: '0.88rem',
                    padding: '14px 12px',
                    hSizes: { 1: '1.3rem', 2: '1.12rem', 3: '1rem', 4: '0.94rem' }
                },
                standard: {
                    id: 'standard',
                    label: 'Smartphone Padrão (375px)',
                    dimensions: '375 \u00d7 667 px',
                    width: '375px',
                    borderRadius: '28px',
                    borderWidth: '3px',
                    signalText: '5G 🔋',
                    titleSize: '1.35rem',
                    fontSize: '0.94rem',
                    padding: '16px 14px',
                    hSizes: { 1: '1.45rem', 2: '1.22rem', 3: '1.08rem', 4: '1rem' }
                },
                large_phone: {
                    id: 'large_phone',
                    label: 'Smartphone Max (430px)',
                    dimensions: '430 \u00d7 932 px',
                    width: '430px',
                    borderRadius: '32px',
                    borderWidth: '4px',
                    signalText: '5G 🔋',
                    titleSize: '1.48rem',
                    fontSize: '0.97rem',
                    padding: '18px 16px',
                    hSizes: { 1: '1.58rem', 2: '1.32rem', 3: '1.15rem', 4: '1.04rem' }
                },
                tablet_portrait: {
                    id: 'tablet_portrait',
                    label: 'Tablet Retrato (768px)',
                    dimensions: '768 \u00d7 1024 px',
                    width: '768px',
                    borderRadius: '20px',
                    borderWidth: '5px',
                    signalText: 'Wi-Fi 🔋 100%',
                    titleSize: '1.68rem',
                    fontSize: '1rem',
                    padding: '24px 28px',
                    hSizes: { 1: '1.8rem', 2: '1.48rem', 3: '1.25rem', 4: '1.1rem' }
                },
                tablet_landscape: {
                    id: 'tablet_landscape',
                    label: 'Tablet Paisagem (1024px)',
                    dimensions: '1024 \u00d7 768 px',
                    width: '1024px',
                    borderRadius: '20px',
                    borderWidth: '5px',
                    signalText: 'Wi-Fi 🔋 100%',
                    titleSize: '1.8rem',
                    fontSize: '1.02rem',
                    padding: '28px 34px',
                    hSizes: { 1: '1.95rem', 2: '1.55rem', 3: '1.3rem', 4: '1.12rem' }
                }
            };

            window.toggleMobilePreviewMenu = function(target) {
                var el = document.getElementById(target === 'header' ? 'mobile_site_nav_dropdown' : 'mobile_lessons_drawer');
                if (!el) return;
                el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'flex' : 'none';
                if (target === 'lessons' && el.style.display === 'flex') {
                    el.style.display = 'block';
                }
            };

            window.setPreviewDevice = function(device) {
                currentDevice = (device === 'mobile') ? 'mobile' : 'desktop';
                var btnDesktop = document.getElementById('btn_device_desktop');
                var btnMobile = document.getElementById('btn_device_mobile');
                var profileSelect = document.getElementById('mobile_profile_select');
                var dimBadge = document.getElementById('device_dimension_badge');
                var stage = document.getElementById('preview_device_stage');
                var viewport = document.getElementById('preview_device_viewport');
                var statusbar = document.getElementById('mobile_device_statusbar');
                var signalSpan = document.getElementById('mobile_device_signal');
                var siteHeader = document.getElementById('mobile_site_header');
                var drawerBar = document.getElementById('mobile_course_drawer_bar');
                var footerZone = document.getElementById('mobile_preview_footer_zone');
                var homebar = document.getElementById('mobile_device_homebar');
                var lessonTitle = document.getElementById('live_preview_lesson_title');

                if (currentDevice === 'mobile') {
                    var profile = MOBILE_PROFILES[currentMobileProfile] || MOBILE_PROFILES.standard;
                    btnMobile.style.background = 'rgba(69,243,255,0.15)';
                    btnMobile.style.color = '#45f3ff';
                    btnDesktop.style.background = 'transparent';
                    btnDesktop.style.color = '#8c8f94';
                    if (profileSelect) profileSelect.style.display = 'inline-block';
                    if (dimBadge) {
                        dimBadge.style.display = 'inline-block';
                        dimBadge.textContent = profile.dimensions;
                    }
                    if (signalSpan) signalSpan.textContent = profile.signalText || '5G 🔋';

                    stage.style.maxWidth = profile.width;
                    stage.style.margin = '0 auto';
                    stage.style.background = '#12161b';
                    stage.style.border = (profile.borderWidth || '3px') + ' solid #2d3748';
                    stage.style.borderRadius = profile.borderRadius || '28px';
                    stage.style.boxShadow = '0 18px 40px rgba(0,0,0,0.6), 0 0 0 1px rgba(69,243,255,0.2)';

                    viewport.style.padding = profile.padding;
                    viewport.style.minHeight = '480px';
                    if (statusbar) statusbar.style.display = 'flex';
                    if (siteHeader) siteHeader.style.display = 'flex';
                    if (drawerBar) drawerBar.style.display = 'flex';
                    if (footerZone) footerZone.style.display = 'flex';
                    if (homebar) homebar.style.display = 'flex';
                    if (lessonTitle) lessonTitle.style.fontSize = profile.titleSize;
                } else {
                    btnDesktop.style.background = 'rgba(69,243,255,0.15)';
                    btnDesktop.style.color = '#45f3ff';
                    btnMobile.style.background = 'transparent';
                    btnMobile.style.color = '#8c8f94';
                    if (profileSelect) profileSelect.style.display = 'none';
                    if (dimBadge) dimBadge.style.display = 'none';

                    stage.style.maxWidth = '100%';
                    stage.style.margin = '0';
                    stage.style.background = 'transparent';
                    stage.style.border = 'none';
                    stage.style.borderRadius = '0';
                    stage.style.boxShadow = 'none';

                    viewport.style.padding = '8px 4px';
                    viewport.style.minHeight = 'auto';
                    if (statusbar) statusbar.style.display = 'none';
                    if (siteHeader) siteHeader.style.display = 'none';
                    if (drawerBar) drawerBar.style.display = 'none';
                    if (footerZone) footerZone.style.display = 'none';
                    if (homebar) homebar.style.display = 'none';
                    if (lessonTitle) lessonTitle.style.fontSize = '1.85rem';
                }

                refreshLivePreview();
            };

            window.setMobileProfile = function(profileKey) {
                if (MOBILE_PROFILES[profileKey]) {
                    currentMobileProfile = profileKey;
                    setPreviewDevice('mobile');
                }
            };

            window.setEditorViewMode = function(mode) {
                currentViewMode = mode;
                var grid = document.getElementById('editor_workspace_grid');
                var colEdit = document.getElementById('editor_blocks_column');
                var colPrev = document.getElementById('editor_preview_column');
                var btnEdit = document.getElementById('btn_mode_edit');
                var btnSplit = document.getElementById('btn_mode_split');
                var btnPrev = document.getElementById('btn_mode_preview');

                [btnEdit, btnSplit, btnPrev].forEach(function(b) {
                    if (b) {
                        b.style.background = 'transparent';
                        b.style.color = '#8c8f94';
                    }
                });

                if (mode === 'edit') {
                    grid.style.gridTemplateColumns = '1fr';
                    colEdit.style.display = 'block';
                    colPrev.style.display = 'none';
                    btnEdit.style.background = 'rgba(69,243,255,0.15)';
                    btnEdit.style.color = '#45f3ff';
                } else if (mode === 'preview') {
                    grid.style.gridTemplateColumns = '1fr';
                    colEdit.style.display = 'none';
                    colPrev.style.display = 'block';
                    colPrev.style.maxHeight = 'none';
                    btnPrev.style.background = 'rgba(69,243,255,0.15)';
                    btnPrev.style.color = '#45f3ff';
                } else {
                    grid.style.gridTemplateColumns = '1fr 1fr';
                    colEdit.style.display = 'block';
                    colPrev.style.display = 'block';
                    colPrev.style.maxHeight = 'calc(100vh - 115px)';
                    btnSplit.style.background = 'rgba(69,243,255,0.15)';
                    btnSplit.style.color = '#45f3ff';
                }
                refreshLivePreview();
            };

            function resolveVideoEmbedHtml(rawUrl) {
                rawUrl = String(rawUrl || '').trim();
                if (!rawUrl) return '';

                var ytMatch = rawUrl.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                if (ytMatch && ytMatch[1]) {
                    var ytUrl = 'https://www.youtube.com/embed/' + ytMatch[1] + '?rel=0';
                    return '<div style="position:relative; width:100%; aspect-ratio:16/9; border-radius:8px; overflow:hidden; background:#000; border:1px solid rgba(69,243,255,0.2);"><iframe src="' + ytUrl + '" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen loading="lazy"></iframe></div>';
                }

                var vimeoMatch = rawUrl.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                if (vimeoMatch && vimeoMatch[1]) {
                    var vmUrl = 'https://player.vimeo.com/video/' + vimeoMatch[1];
                    return '<div style="position:relative; width:100%; aspect-ratio:16/9; border-radius:8px; overflow:hidden; background:#000; border:1px solid rgba(69,243,255,0.2);"><iframe src="' + vmUrl + '" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen loading="lazy"></iframe></div>';
                }

                if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(rawUrl)) {
                    return '<div style="position:relative; width:100%; aspect-ratio:16/9; border-radius:8px; overflow:hidden; background:#000;"><video controls style="width:100%; height:100%; object-fit:contain;"><source src="' + escapeHtml(rawUrl) + '"></video></div>';
                }

                return '';
            }

            window.refreshLivePreview = function() {
                var titleInput = document.getElementById('lesson_title_input');
                var previewTitle = document.getElementById('live_preview_lesson_title');
                if (titleInput && previewTitle) {
                    previewTitle.textContent = titleInput.value || 'Aula sem título';
                }

                var previewBody = document.getElementById('live_preview_body');
                if (!previewBody) return;

                if (!blocks || blocks.length === 0) {
                    previewBody.innerHTML = '<p style="color:#64748b; text-align:center; padding:30px 0;">Adicione blocos à esquerda para visualizar a aula aqui em tempo real.</p>';
                    return;
                }

                var isMobile = (currentDevice === 'mobile');
                var mProfile = MOBILE_PROFILES[currentMobileProfile] || MOBILE_PROFILES.standard;
                var bodyFontSize = isMobile ? mProfile.fontSize : '1.03rem';
                var hSizes = isMobile ? mProfile.hSizes : {1: '2rem', 2: '1.6rem', 3: '1.3rem', 4: '1.1rem'};

                var htmlParts = [];
                blocks.forEach(function(b) {
                    var d = b.data || {};
                    switch(b.type) {
                        case 'header':
                            var lvl = Math.min(4, Math.max(1, parseInt(d.level || 2)));
                            htmlParts.push('<h' + lvl + ' style="color:#fff; font-size:' + (hSizes[lvl] || '1.4rem') + '; font-weight:800; margin:1.4rem 0 0.7rem 0; line-height:1.3;">' + escapeHtml(d.text || '') + '</h' + lvl + '>');
                            break;

                        case 'paragraph':
                            var pText = escapeHtml(d.text || '').replace(/\n/g, '<br>');
                            htmlParts.push('<p style="margin:0 0 1.1rem 0; line-height:1.75; color:#c5c6c7; font-size:' + bodyFontSize + '; word-break:break-word;">' + pText + '</p>');
                            break;

                        case 'video':
                            var vUrl = d.url || d.source || '';
                            var vCap = d.caption || '';
                            var embed = resolveVideoEmbedHtml(vUrl);
                            if (embed) {
                                htmlParts.push('<div style="margin:1.3rem 0;">' + embed + (vCap ? '<div style="margin-top:6px; font-size:0.8rem; color:#8c8f94; text-align:center;">' + escapeHtml(vCap) + '</div>' : '') + '</div>');
                            } else if (vUrl) {
                                htmlParts.push('<div style="margin:1.1rem 0; padding:12px; border:1px dashed rgba(69,243,255,0.3); border-radius:8px; color:#8c8f94; font-size:12px; word-break:break-all;">🔗 Vídeo: ' + escapeHtml(vUrl) + '</div>');
                            }
                            break;

                        case 'code':
                            var lang = (d.language || 'code').toUpperCase();
                            var codePad = isMobile ? '12px' : '16px';
                            var codeFont = isMobile ? '0.8rem' : '0.9rem';
                            htmlParts.push(
                                '<div style="margin:1.3rem 0; border-radius:8px; overflow:hidden; background:#0d1117; border:1px solid rgba(69,243,255,0.2);">' +
                                    '<div style="background:rgba(255,255,255,0.04); padding:6px 12px; font-size:0.72rem; font-family:monospace; color:#45f3ff; border-bottom:1px solid rgba(255,255,255,0.06);">' + escapeHtml(lang) + '</div>' +
                                    '<pre style="margin:0; padding:' + codePad + '; overflow-x:auto; font-family:Consolas,Monaco,monospace; font-size:' + codeFont + '; line-height:1.55; color:#e6edf3;"><code>' + escapeHtml(d.code || '') + '</code></pre>' +
                                '</div>'
                            );
                            break;

                        case 'callout':
                            var ctype = d.type || 'info';
                            var styles = {
                                info: { border: '#45f3ff', bg: 'rgba(69,243,255,0.07)', icon: '💡' },
                                success: { border: '#00d284', bg: 'rgba(0,210,132,0.08)', icon: '✅' },
                                warning: { border: '#f5a623', bg: 'rgba(245,166,35,0.08)', icon: '⚠️' },
                                danger: { border: '#e06c75', bg: 'rgba(224,108,117,0.08)', icon: '🛑' }
                            };
                            var st = styles[ctype] || styles.info;
                            var calloutPad = isMobile ? '12px 14px' : '16px 20px';
                            htmlParts.push(
                                '<div style="margin:1.2rem 0; padding:' + calloutPad + '; border-left:4px solid ' + st.border + '; background:' + st.bg + '; border-radius:0 8px 8px 0; display:flex; gap:10px; align-items:flex-start; font-size:' + bodyFontSize + ';">' +
                                    '<span style="font-size:1.1rem;">' + st.icon + '</span>' +
                                    '<div style="color:#fff; line-height:1.6; flex:1; word-break:break-word;">' + escapeHtml(d.text || '').replace(/\n/g, '<br>') + '</div>' +
                                '</div>'
                            );
                            break;

                        case 'list':
                            var items = Array.isArray(d.items) ? d.items : [];
                            var tag = (d.style === 'ordered') ? 'ol' : 'ul';
                            var lis = items.map(function(it) {
                                var txt = typeof it === 'string' ? it : (it.content || '');
                                return '<li style="margin-bottom:6px;">' + escapeHtml(txt) + '</li>';
                            }).join('');
                            htmlParts.push('<' + tag + ' style="margin:0 0 1.2rem 1.25rem; color:#c5c6c7; line-height:1.7; font-size:' + bodyFontSize + ';">' + lis + '</' + tag + '>');
                            break;

                        case 'image':
                            var iUrl = d.url || (d.file ? d.file.url : '') || '';
                            var iCap = d.caption || '';
                            if (iUrl) {
                                htmlParts.push(
                                    '<figure style="margin:1.4rem 0; text-align:center;">' +
                                        '<img src="' + escapeHtml(iUrl) + '" alt="' + escapeHtml(iCap) + '" style="max-width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">' +
                                        (iCap ? '<figcaption style="margin-top:6px; font-size:0.8rem; color:#8c8f94;">' + escapeHtml(iCap) + '</figcaption>' : '') +
                                    '</figure>'
                                );
                            }
                            break;

                        case 'delimiter':
                            htmlParts.push('<hr style="border:0; border-top:1px solid rgba(69,243,255,0.2); margin:1.6rem 0;">');
                            break;
                    }
                });

                previewBody.innerHTML = htmlParts.join('');
            };

            function renderCanvas() {
                var container = document.getElementById('blocks_canvas');
                container.innerHTML = '';

                if (blocks.length === 0) {
                    container.innerHTML = '<div style="padding:30px; text-align:center; color:#8c8f94; border:1px dashed rgba(255,255,255,0.1); border-radius:6px;">Nenhum bloco ainda. Clique nos botões abaixo para começar a estruturar sua aula.</div>';
                    refreshLivePreview();
                    return;
                }

                blocks.forEach(function(b, idx) {
                    var card = document.createElement('div');
                    card.className = 'ds-block-card';

                    // Barra superior do bloco
                    var header = document.createElement('div');
                    header.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;';
                    
                    var badgeTitle = getBlockBadge(b.type);
                    header.innerHTML = `
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:11px; font-weight:700; color:var(--accent-blue, #45f3ff); text-transform:uppercase; letter-spacing:0.5px;">` + badgeTitle + `</span>
                            <span style="font-size:11px; color:#64748b;">#` + (idx + 1) + `</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:4px;">
                            <button type="button" class="ds-block-ctrl-btn" onclick="moveBlock(` + idx + `, -1)" title="Mover para Cima">▲</button>
                            <button type="button" class="ds-block-ctrl-btn" onclick="moveBlock(` + idx + `, 1)" title="Mover para Baixo">▼</button>
                            <button type="button" class="ds-block-ctrl-btn" onclick="duplicateBlock(` + idx + `)" title="Duplicar">📋</button>
                            <button type="button" class="ds-block-ctrl-btn" onclick="removeBlock(` + idx + `)" style="color:#e06c75;" title="Excluir Bloco">✕</button>
                        </div>
                    `;
                    card.appendChild(header);

                    // Conteúdo específico do bloco
                    var body = document.createElement('div');
                    body.innerHTML = renderBlockInputs(b, idx);
                    card.appendChild(body);

                    container.appendChild(card);
                });

                refreshLivePreview();
            }

            function getBlockBadge(type) {
                switch(type) {
                    case 'header': return '📌 Título';
                    case 'paragraph': return '📝 Parágrafo';
                    case 'video': return '🎬 Vídeo Embed (16:9)';
                    case 'code': return '💻 Bloco de Código';
                    case 'callout': return '💡 Dica / Alerta';
                    case 'list': return '📋 Lista';
                    case 'image': return '🖼️ Imagem';
                    case 'delimiter': return '➖ Divisor';
                    default: return 'Bloco';
                }
            }

            function escapeHtml(str) {
                return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function renderBlockInputs(b, idx) {
                var d = b.data || {};

                switch(b.type) {
                    case 'header':
                        var lvl = d.level || 2;
                        return `
                            <div style="display:flex; gap:10px; align-items:center;">
                                <select onchange="updateBlockData(` + idx + `, 'level', parseInt(this.value))" style="width:110px; padding:8px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff;">
                                    <option value="1" ` + (lvl === 1 ? 'selected' : '') + `>H1 Principal</option>
                                    <option value="2" ` + (lvl === 2 ? 'selected' : '') + `>H2 Seção</option>
                                    <option value="3" ` + (lvl === 3 ? 'selected' : '') + `>H3 Subseção</option>
                                </select>
                                <input type="text" value="` + escapeHtml(d.text) + `" placeholder="Digite o cabeçalho..." oninput="updateBlockData(` + idx + `, 'text', this.value)" style="flex:1; padding:8px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:15px; font-weight:600;">
                            </div>
                        `;

                    case 'paragraph':
                        return `
                            <textarea rows="4" placeholder="Escreva o texto da aula... Suporta formatações como <strong>, <em>, <code> e links." oninput="updateBlockData(` + idx + `, 'text', this.value)" style="width:100%; padding:10px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#c5c6c7; font-size:14px; line-height:1.6; resize:vertical; box-sizing:border-box;">` + escapeHtml(d.text) + `</textarea>
                        `;

                    case 'video':
                        var url = d.url || d.source || '';
                        var cap = d.caption || '';
                        var inlineEmbed = resolveVideoEmbedHtml(url);
                        return `
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                <input type="text" value="` + escapeHtml(url) + `" placeholder="URL do Vídeo (YouTube: https://youtube.com/watch?v=... ou Vimeo / MP4)" oninput="updateVideoBlockUrl(` + idx + `, this.value)" style="width:100%; padding:8px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-family:monospace; box-sizing:border-box;">
                                <div id="inline_video_preview_` + idx + `">` + inlineEmbed + `</div>
                                <input type="text" value="` + escapeHtml(cap) + `" placeholder="Legenda do vídeo (opcional)" oninput="updateBlockData(` + idx + `, 'caption', this.value)" style="width:100%; padding:6px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#8c8f94; font-size:12px; box-sizing:border-box;">
                            </div>
                        `;

                    case 'code':
                        var lang = d.language || 'php';
                        var code = d.code || '';
                        return `
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:12px; color:#8c8f94;">Linguagem:</span>
                                    <select onchange="updateBlockData(` + idx + `, 'language', this.value)" style="padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-family:monospace; font-size:12px;">
                                        <option value="php" ` + (lang === 'php' ? 'selected' : '') + `>PHP</option>
                                        <option value="python" ` + (lang === 'python' ? 'selected' : '') + `>Python</option>
                                        <option value="javascript" ` + (lang === 'javascript' ? 'selected' : '') + `>JavaScript</option>
                                        <option value="sql" ` + (lang === 'sql' ? 'selected' : '') + `>SQL</option>
                                        <option value="html" ` + (lang === 'html' ? 'selected' : '') + `>HTML</option>
                                        <option value="css" ` + (lang === 'css' ? 'selected' : '') + `>CSS</option>
                                        <option value="json" ` + (lang === 'json' ? 'selected' : '') + `>JSON</option>
                                        <option value="bash" ` + (lang === 'bash' ? 'selected' : '') + `>Bash / Shell</option>
                                    </select>
                                </div>
                                <textarea rows="6" placeholder="// Cole seu código aqui..." oninput="updateBlockData(` + idx + `, 'code', this.value)" style="width:100%; padding:12px; border-radius:4px; border:1px solid #3a444a; background:#08090b; color:#58a6ff; font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5; resize:vertical; box-sizing:border-box;">` + escapeHtml(code) + `</textarea>
                            </div>
                        `;

                    case 'callout':
                        var ctype = d.type || 'info';
                        var ctext = d.text || '';
                        return `
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="font-size:12px; color:#8c8f94;">Tipo de Aviso:</span>
                                    <select onchange="updateBlockData(` + idx + `, 'type', this.value)" style="padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:12px;">
                                        <option value="info" ` + (ctype === 'info' ? 'selected' : '') + `>💡 Dica / Informação (Azul)</option>
                                        <option value="success" ` + (ctype === 'success' ? 'selected' : '') + `>✅ Boas Práticas (Verde)</option>
                                        <option value="warning" ` + (ctype === 'warning' ? 'selected' : '') + `>⚠️ Atenção / Cuidado (Laranja)</option>
                                        <option value="danger" ` + (ctype === 'danger' ? 'selected' : '') + `>🛑 Erro Comum (Vermelho)</option>
                                    </select>
                                </div>
                                <textarea rows="3" placeholder="Mensagem do aviso didático..." oninput="updateBlockData(` + idx + `, 'text', this.value)" style="width:100%; padding:8px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:13px; resize:vertical; box-sizing:border-box;">` + escapeHtml(ctext) + `</textarea>
                            </div>
                        `;

                    case 'list':
                        var itemsArr = Array.isArray(d.items) ? d.items : [];
                        var itemsStr = itemsArr.map(function(it) {
                            return typeof it === 'string' ? it : (it.content || '');
                        }).join("\n");
                        var style = d.style || 'unordered';
                        return `
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="font-size:12px; color:#8c8f94;">Estilo:</span>
                                    <select onchange="updateBlockData(` + idx + `, 'style', this.value)" style="padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:12px;">
                                        <option value="unordered" ` + (style === 'unordered' ? 'selected' : '') + `>Marcadores (Bulleted)</option>
                                        <option value="ordered" ` + (style === 'ordered' ? 'selected' : '') + `>Numerada (1, 2, 3...)</option>
                                    </select>
                                </div>
                                <textarea rows="4" placeholder="Um item por linha..." oninput="updateListItems(` + idx + `, this.value)" style="width:100%; padding:8px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#c5c6c7; font-size:13px; resize:vertical; box-sizing:border-box;">` + escapeHtml(itemsStr) + `</textarea>
                            </div>
                        `;

                    case 'image':
                        var imgUrl = d.url || (d.file ? d.file.url : '') || '';
                        var imgCap = d.caption || '';
                        return `
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <input type="text" value="` + escapeHtml(imgUrl) + `" placeholder="URL da imagem (ex: https://... ou /caminho/imagem.png)" oninput="updateBlockData(` + idx + `, 'url', this.value)" style="width:100%; padding:8px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-family:monospace; box-sizing:border-box;">
                                <input type="text" value="` + escapeHtml(imgCap) + `" placeholder="Legenda da imagem (opcional)" oninput="updateBlockData(` + idx + `, 'caption', this.value)" style="width:100%; padding:6px 12px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#8c8f94; font-size:12px; box-sizing:border-box;">
                            </div>
                        `;

                    case 'delimiter':
                        return `
                            <div style="padding:10px 0; text-align:center; color:#8c8f94; font-size:12px;">
                                &mdash;&mdash;&mdash; Linha Divisória de Seção &mdash;&mdash;&mdash;
                            </div>
                        `;
                }
                return '';
            }

            window.addBlock = function(type) {
                var newBlock = {
                    id: 'b_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                    type: type,
                    data: {}
                };

                switch(type) {
                    case 'header': newBlock.data = { level: 2, text: '' }; break;
                    case 'paragraph': newBlock.data = { text: '' }; break;
                    case 'video': newBlock.data = { url: '', caption: '' }; break;
                    case 'code': newBlock.data = { language: 'php', code: '' }; break;
                    case 'callout': newBlock.data = { type: 'info', text: '' }; break;
                    case 'list': newBlock.data = { style: 'unordered', items: [] }; break;
                    case 'image': newBlock.data = { url: '', caption: '' }; break;
                    case 'delimiter': newBlock.data = {}; break;
                }

                blocks.push(newBlock);
                renderCanvas();
            };

            window.removeBlock = function(idx) {
                if (confirm('Deseja excluir este bloco?')) {
                    blocks.splice(idx, 1);
                    renderCanvas();
                }
            };

            window.moveBlock = function(idx, dir) {
                var target = idx + dir;
                if (target >= 0 && target < blocks.length) {
                    var item = blocks.splice(idx, 1)[0];
                    blocks.splice(target, 0, item);
                    renderCanvas();
                }
            };

            window.duplicateBlock = function(idx) {
                var item = JSON.parse(JSON.stringify(blocks[idx]));
                item.id = 'b_' + Date.now();
                blocks.splice(idx + 1, 0, item);
                renderCanvas();
            };

            window.updateBlockData = function(idx, key, val) {
                if (blocks[idx]) {
                    if (!blocks[idx].data) blocks[idx].data = {};
                    blocks[idx].data[key] = val;
                    refreshLivePreview();
                }
            };

            window.updateVideoBlockUrl = function(idx, val) {
                if (blocks[idx]) {
                    if (!blocks[idx].data) blocks[idx].data = {};
                    blocks[idx].data.url = val;
                    var inlineBox = document.getElementById('inline_video_preview_' + idx);
                    if (inlineBox) {
                        inlineBox.innerHTML = resolveVideoEmbedHtml(val);
                    }
                    refreshLivePreview();
                }
            };

            window.updateListItems = function(idx, text) {
                if (blocks[idx]) {
                    var lines = text.split("\n").map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });
                    blocks[idx].data.items = lines;
                    refreshLivePreview();
                }
            };

            window.saveLessonBlocks = function() {
                var btn = document.getElementById('btn_save_lesson');
                var btnText = document.getElementById('btn_save_text');
                var indicator = document.getElementById('save_indicator');
                var title = document.getElementById('lesson_title_input').value;
                var status = document.getElementById('lesson_status_select').value;

                btn.disabled = true;
                btnText.textContent = 'Salvando...';

                var payload = {
                    csrf_token: csrfToken,
                    title: title,
                    status: status,
                    blocks: blocks
                };

                fetch(baseUrl + '/admin/academy/lessons/' + lessonId + '/update-content', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btnText.textContent = 'Salvar Aula';
                    if (data.success) {
                        indicator.style.display = 'inline';
                        setTimeout(function() { indicator.style.display = 'none'; }, 4000);
                        if (window.OS && window.OS.notify) {
                            window.OS.notify('Aula salva com sucesso!', 'success');
                        }
                    } else {
                        alert(data.message || 'Erro ao salvar aula.');
                    }
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btnText.textContent = 'Salvar Aula';
                    alert('Erro na comunicação com o servidor: ' + err);
                });
            };

            // Atalho de teclado: Ctrl+S / Cmd+S para salvar
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    saveLessonBlocks();
                }
            });

            renderCanvas();
        })();
        </script>
        <?php
        $html = ob_get_clean();
        return new ViewResponse($html);
    }

    public function updateContent(Request $request, $id): ResponseInterface
    {
        $lessonId = (int)$id;
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        // Aceita payload JSON ou Form POST
        $rawBody = (string)file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true);

        $token = '';
        $title = null;
        $status = null;
        $blocks = null;

        if (is_array($jsonData)) {
            $token = (string)($jsonData['csrf_token'] ?? '');
            $title = isset($jsonData['title']) ? (string)$jsonData['title'] : null;
            $status = isset($jsonData['status']) ? (string)$jsonData['status'] : null;
            $blocks = $jsonData['blocks'] ?? null;
        } else {
            $token = (string)($request->input('csrf_token') ?? '');
            $title = $request->input('title');
            $status = $request->input('status');
            $rawBlocks = $request->input('blocks_json');
            if ($rawBlocks) {
                $blocks = is_array($rawBlocks) ? $rawBlocks : json_decode((string)$rawBlocks, true);
            } elseif (is_array($request->input('blocks'))) {
                $blocks = $request->input('blocks');
            }
        }

        $expectedToken = (string)$this->session->getCsrfToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            if ($this->isAjax($request)) {
                return new Response(json_encode(['success' => false, 'message' => 'Token CSRF inválido']), 403, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash('error', "Token CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy/lessons/' . $lessonId . '/editor');
        }

        $lesson = $this->structureRepo->getLessonById($lessonId);
        if (!$lesson) {
            if ($this->isAjax($request)) {
                return new Response(json_encode(['success' => false, 'message' => 'Aula não encontrada']), 404, ['Content-Type' => 'application/json']);
            }
            $this->session->setFlash('error', "Aula não encontrada.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $contentToSave = is_array($blocks) 
            ? json_encode(['time' => time(), 'blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $updateData = [
            'source_type' => 'content',
            'content'     => $contentToSave,
        ];

        if ($title !== null && trim($title) !== '') {
            $updateData['title'] = trim($title);
        }
        if ($status !== null && in_array($status, ['published', 'draft'], true)) {
            $updateData['status'] = $status;
        }

        try {
            $this->structureRepo->updateLesson($lessonId, $updateData);

            if ($this->isAjax($request)) {
                return new Response(json_encode([
                    'success' => true,
                    'message' => 'Aula salva com sucesso!'
                ]), 200, ['Content-Type' => 'application/json']);
            }

            $this->session->setFlash('success', "Conteúdo da aula atualizado com sucesso!");
            return Response::redirect($baseUrl . '/admin/academy/lessons/' . $lessonId . '/editor');
        } catch (Throwable $e) {
            if ($this->isAjax($request)) {
                return new Response(json_encode([
                    'success' => false,
                    'message' => 'Erro ao salvar: ' . $e->getMessage()
                ]), 500, ['Content-Type' => 'application/json']);
            }

            $this->session->setFlash('error', "Erro ao salvar aula: " . $e->getMessage());
            return Response::redirect($baseUrl . '/admin/academy/lessons/' . $lessonId . '/editor');
        }
    }

    private function isAjax(Request $request): bool
    {
        $accept = (string)($request->server['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($request->server['HTTP_X_REQUESTED_WITH'] ?? '');
        $ct = (string)($request->server['CONTENT_TYPE'] ?? '');

        return str_contains($accept, 'application/json')
            || str_contains($xrw, 'XMLHttpRequest')
            || str_contains($ct, 'application/json');
    }
}
