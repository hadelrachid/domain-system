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
use DomainSystem\Plugins\academy\Services\CourseService;
use Throwable;

class CourseAdminController
{
    private CourseRepositoryInterface $repository;
    private CourseService $service;
    private SessionManagerInterface $session;
    private ?CourseStructureRepositoryInterface $structureRepo;
    private ?ThemeManagerInterface $themeManager;

    public function __construct(
        CourseRepositoryInterface $repository,
        CourseService $service,
        SessionManagerInterface $session,
        ?CourseStructureRepositoryInterface $structureRepo = null,
        ?ThemeManagerInterface $themeManager = null
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->session = $session;
        $this->structureRepo = $structureRepo;
        $this->themeManager = $themeManager;
    }

    public function index(Request $request): ResponseInterface
    {
        $courses = $this->repository->findAll();
        $csrfToken = $this->session->getCsrfToken();
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $flash = $this->session->getFlash();
        $flashType = $flash['type'] ?? null;
        $flashMsg = $flash['msg'] ?? null;

        ob_start();
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-graduation-cap" style="color:var(--accent-blue, #45f3ff);"></i> Academy (Cursos)
            </h1>
            <button onclick="document.getElementById('modalNewCourse').style.display='flex'" class="btn btn-activate" style="padding: 10px 20px; font-weight:600; cursor:pointer;">
                <i class="fas fa-plus"></i> Novo Curso
            </button>
        </div>

        <?php if (!empty($flashMsg) && $flashType === 'error'): ?>
            <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($flashMsg) ?>
            </div>
        <?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
            <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flashMsg) ?>
            </div>
        <?php endif; ?>

        <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; color: var(--text-main, #fff);">
                <thead>
                    <tr style="background: rgba(0,0,0,0.25); border-bottom: 1px solid var(--border, rgba(255,255,255,0.1));">
                        <th style="padding: 15px; text-align: left; width: 60px;">ID</th>
                        <th style="padding: 15px; text-align: left; width: 100px;">Capa</th>
                        <th style="padding: 15px; text-align: left;">Título</th>
                        <th style="padding: 15px; text-align: left;">Slug</th>
                        <th style="padding: 15px; text-align: left; width: 120px;">Status</th>
                        <th style="padding: 15px; text-align: right; width: 140px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted, #8c8f94);">Nenhum curso cadastrado ainda. Clique em "Novo Curso" para criar o primeiro.</td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr style="border-bottom: 1px solid var(--border, rgba(255,255,255,0.06));">
                                <td style="padding: 15px; color: var(--text-muted, #8c8f94);">#<?= $c['id'] ?></td>
                                <td style="padding: 15px;">
                                    <?php if (!empty($c['thumbnail'])): ?>
                                        <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1);">
                                            <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim((string)$c['thumbnail'], '/')) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; background: rgba(255,255,255,0.05); display: flex; align-items:center; justify-content:center; color: var(--text-muted, #8c8f94); font-size: 11px;">
                                            16:9
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; font-weight: 500;">
                                    <?= htmlspecialchars((string)$c['title']) ?>
                                    <?php if (!empty($c['link'])): ?>
                                        <br><a href="<?= htmlspecialchars((string)$c['link']) ?>" target="_blank" style="color: var(--accent-blue, #45f3ff); font-size: 12px; text-decoration: none;">Link externo <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; color: var(--text-muted, #8c8f94); font-family: monospace; font-size: 13px;">
                                    <?= htmlspecialchars((string)$c['slug']) ?>
                                </td>
                                <td style="padding: 15px;">
                                    <?php if ($c['status'] === 'published'): ?>
                                        <span style="background: rgba(40,167,69,0.15); color:#28a745; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(40,167,69,0.3);">Publicado</span>
                                    <?php elseif ($c['status'] === 'soon'): ?>
                                        <span style="background: rgba(240,173,78,0.15); color:#f0ad4e; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(240,173,78,0.3);">Em Breve</span>
                                    <?php else: ?>
                                        <span style="background: rgba(255,255,255,0.08); color:#8c8f94; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight:600; border: 1px solid rgba(255,255,255,0.15);">Rascunho</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: right; white-space: nowrap;">
                                    <a href="<?= $baseUrl ?>/admin/academy/edit/<?= $c['id'] ?>" class="btn btn-outline" style="display:inline-block; padding: 6px 12px; font-size: 13px; text-decoration:none; margin-right: 6px; border:1px solid #4a545a; border-radius:4px; color:#fff;" title="Editar Curso">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?= $baseUrl ?>/admin/academy/delete/<?= $c['id'] ?>" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir o curso <?= htmlspecialchars(addslashes((string)$c['title'])) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" style="background:transparent; border:1px solid rgba(220,50,50,0.4); color:#dc3232; padding:6px 12px; border-radius:4px; cursor:pointer;" title="Excluir Curso">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: rgba(69, 243, 255, 0.05); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 8px; color: var(--text-muted, #8c8f94); display:flex; align-items:flex-start; gap: 16px;">
            <i class="fas fa-info-circle" style="color: var(--accent-blue, #45f3ff); font-size: 24px; margin-top: 2px;"></i>
            <div>
                <strong style="color: #fff; font-size: 1.05rem; display:block; margin-bottom: 6px;">Como associar e exibir os cursos no site?</strong>
                <p style="margin: 0 0 10px 0; line-height: 1.6;">
                    O Academy funciona como um catálogo visual e dinâmico de cursos. Para exibi-los em qualquer página (como a página pré-configurada <strong>Tutoriais</strong>), inclua o shortcode:
                </p>
                <div style="background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; font-family: monospace; color: #45f3ff;">
                    <span>[hub_de_cursos]</span>
                    <span style="color:#8c8f94; font-size:12px;">Parâmetros opcionais: limit="6" status="published"</span>
                </div>
                <div style="margin-top: 12px;">
                    <a href="<?= $baseUrl ?>/tutoriais" target="_blank" style="color: #45f3ff; text-decoration: none; font-weight: 500;">
                        <i class="fas fa-external-link-alt"></i> Visualizar Página de Tutoriais com a Grade &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- MODAL NOVO CURSO -->
        <div id="modalNewCourse" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
            <div style="background:var(--bg-panel, #1d2327); padding:30px; border-radius:12px; width:540px; max-width:90%; color:var(--text-main, #fff); border: 1px solid var(--border, rgba(255,255,255,0.1)); max-height:90vh; overflow-y:auto;">
                <h3 style="margin-top:0; color:#fff; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-plus-circle" style="color:var(--accent-blue, #45f3ff);"></i> Cadastrar Novo Curso
                </h3>
                
                <form method="POST" action="<?= $baseUrl ?>/admin/academy/store" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Título do Curso *</label>
                        <input type="text" name="title" required placeholder="Ex: Curso Completo de PHP 8.2 e Micro-Kernel" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                    </div>
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Descrição Curta</label>
                        <textarea name="description" rows="3" placeholder="Resumo do conteúdo programático do curso..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;"></textarea>
                    </div>

                    <div style="margin-bottom:15px; display:flex; gap:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:6px; font-weight:500;">Status</label>
                            <select name="status" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                                <option value="published">Publicado</option>
                                <option value="soon" selected>Em Breve</option>
                                <option value="draft">Rascunho</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:6px; font-weight:500;">Link de Destino</label>
                            <input type="text" name="link" placeholder="https://... ou /pagina" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="margin-bottom:20px; padding:15px; background:rgba(0,0,0,0.25); border:1px solid #4a545a; border-radius:6px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Imagem de Capa (Card)</label>
                        <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="width:100%; color:#8c8f94;">
                        <small style="display:block; margin-top:6px; color:#8c8f94; font-size:12px;">
                            Padrão exigido: proporção <strong>16:9</strong> (horizontal), mín. <strong>640x360 px</strong>, máx. <strong>2MB</strong> (JPG, PNG, WEBP).
                        </small>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px;">
                        <button type="button" onclick="document.getElementById('modalNewCourse').style.display='none'" class="btn btn-outline" style="padding:10px 18px; border:1px solid #4a545a; background:transparent; color:#8c8f94; border-radius:6px; cursor:pointer;">Cancelar</button>
                        <button type="submit" class="btn btn-activate" style="padding:10px 20px; font-weight:600; cursor:pointer;">Salvar Curso</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return new ViewResponse($html);
    }

    public function edit(Request $request, $id): ResponseInterface
    {
        $course = $this->repository->findById((int)$id);
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        if (!$course) {
            $this->session->setFlash('error', "Curso não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        $csrfToken = $this->session->getCsrfToken();

        ob_start();
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-edit" style="color:var(--accent-blue, #45f3ff);"></i> Editar Curso #<?= $course['id'] ?>
            </h1>
            <a href="<?= $baseUrl ?>/admin/academy" class="btn btn-outline" style="padding: 8px 16px; border:1px solid #4a545a; border-radius:6px; color:#fff; text-decoration:none;">
                &larr; Voltar para Lista
            </a>
        </div>

        <div style="background:var(--bg-panel, #1d2327); padding:30px; border-radius:8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); max-width: 700px; color:var(--text-main, #fff);">
            <form method="POST" action="<?= $baseUrl ?>/admin/academy/update/<?= $course['id'] ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:6px; font-weight:500;">Título do Curso *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars((string)$course['title']) ?>" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:6px; font-weight:500;">Slug Atual</label>
                    <input type="text" disabled value="<?= htmlspecialchars((string)$course['slug']) ?>" style="width:100%; padding:10px; border-radius:6px; border:1px solid #333; background:#0b0c10; color:#8c8f94; box-sizing:border-box; font-family:monospace;">
                    <small style="color:#8c8f94; font-size:12px;">Se você alterar o título, o slug será regenerado automaticamente.</small>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; margin-bottom:6px; font-weight:500;">Descrição Curta</label>
                    <textarea name="description" rows="4" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;"><?= htmlspecialchars((string)($course['description'] ?? '')) ?></textarea>
                </div>

                <div style="margin-bottom:18px; display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Status</label>
                        <select name="status" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                            <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
                            <option value="soon" <?= $course['status'] === 'soon' ? 'selected' : '' ?>>Em Breve</option>
                            <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Link de Destino</label>
                        <input type="text" name="link" value="<?= htmlspecialchars((string)($course['link'] ?? '')) ?>" placeholder="https://... ou /pagina" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                    </div>
                </div>

                <div style="margin-bottom:24px; padding:18px; background:rgba(0,0,0,0.25); border:1px solid #4a545a; border-radius:6px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Imagem de Capa (Card)</label>
                    
                    <?php if (!empty($course['thumbnail'])): ?>
                        <div style="margin-bottom: 12px; display:flex; align-items:center; gap:15px;">
                            <div style="width: 140px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.15);">
                                <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim((string)$course['thumbnail'], '/')) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <span style="font-size:12px; color:#8c8f94;">Capa atual configurada.<br>Envie um novo arquivo abaixo para substituir.</span>
                        </div>
                    <?php endif; ?>

                    <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="width:100%; color:#8c8f94;">
                    <small style="display:block; margin-top:6px; color:#8c8f94; font-size:12px;">
                        Padrão exigido: proporção <strong>16:9</strong> (horizontal), mín. <strong>640x360 px</strong>, máx. <strong>2MB</strong> (JPG, PNG, WEBP).
                    </small>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <a href="<?= $baseUrl ?>/admin/academy" class="btn btn-outline" style="padding:10px 18px; border:1px solid #4a545a; background:transparent; color:#8c8f94; border-radius:6px; text-decoration:none;">Cancelar</a>
                    <button type="submit" class="btn btn-activate" style="padding:10px 22px; font-weight:600; cursor:pointer;">Atualizar Curso</button>
                </div>
            </form>
        </div>

        <?php if ($this->structureRepo !== null): ?>
            <?php
            $modules = $this->structureRepo->getModules((int)$course['id']);
            $allLessons = $this->structureRepo->getLessons((int)$course['id']);
            $availablePages = $this->themeManager ? $this->themeManager->getAvailablePages() : [];

            $lessonsByMod = [];
            $standaloneLessons = [];
            foreach ($allLessons as $l) {
                $mId = $l['module_id'] !== null ? (int)$l['module_id'] : null;
                if ($mId !== null && $mId > 0) {
                    $lessonsByMod[$mId][] = $l;
                } else {
                    $standaloneLessons[] = $l;
                }
            }

            // Monta estrutura inicial para o JS
            $initialStructure = [];
            foreach ($modules as $m) {
                $mId = (int)$m['id'];
                $initialStructure[] = [
                    'title'    => (string)$m['title'],
                    'position' => (int)$m['position'],
                    'lessons'  => array_map(function($les) {
                        return [
                            'title'       => (string)$les['title'],
                            'slug'        => (string)$les['slug'],
                            'source_type' => (string)$les['source_type'],
                            'source'      => (string)($les['source'] ?? ''),
                            'content'     => (string)($les['content'] ?? ''),
                        ];
                    }, $lessonsByMod[$mId] ?? []),
                ];
            }
            if (!empty($standaloneLessons)) {
                $initialStructure[] = [
                    'title'    => 'Módulo Geral',
                    'position' => 999,
                    'lessons'  => array_map(function($les) {
                        return [
                            'title'       => (string)$les['title'],
                            'slug'        => (string)$les['slug'],
                            'source_type' => (string)$les['source_type'],
                            'source'      => (string)($les['source'] ?? ''),
                            'content'     => (string)($les['content'] ?? ''),
                        ];
                    }, $standaloneLessons),
                ];
            }
            ?>
            <div style="margin-top:35px; background:var(--bg-panel, #1d2327); padding:30px; border-radius:8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); max-width: 900px; color:var(--text-main, #fff);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="margin:0; font-size:1.3rem; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-sitemap" style="color:var(--accent-blue, #45f3ff);"></i> Estrutura do Curso (Módulos &amp; Aulas)
                        </h2>
                        <p style="margin:5px 0 0 0; color:var(--text-muted, #8c8f94); font-size:13px;">
                            Organize os módulos e vincule as aulas a arquivos de tema (ex: <code>pages/tutoriais/*.php</code>) ou conteúdo direto.
                        </p>
                    </div>
                    <a href="<?= $baseUrl ?>/cursos/<?= urlencode((string)$course['slug']) ?>" target="_blank" class="btn btn-outline" style="padding:8px 14px; border:1px solid rgba(69,243,255,0.3); color:var(--accent-blue, #45f3ff); border-radius:6px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-external-link-alt"></i> Ver no Site
                    </a>
                </div>

                <form method="POST" action="<?= $baseUrl ?>/admin/academy/structure/<?= $course['id'] ?>" id="formCourseStructure">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="structure_json" id="structure_json_input" value="">

                    <div id="modulesContainer" style="display:flex; flex-direction:column; gap:20px;"></div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:25px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.08); flex-wrap:wrap; gap:15px;">
                        <button type="button" onclick="addModule()" class="btn btn-outline" style="padding:9px 16px; border:1px dashed var(--accent-blue, #45f3ff); color:var(--accent-blue, #45f3ff); border-radius:6px; cursor:pointer; background:transparent;">
                            <i class="fas fa-plus"></i> Adicionar Módulo
                        </button>
                        <button type="submit" onclick="return prepareStructureJson()" class="btn btn-activate" style="padding:10px 22px; font-weight:600; cursor:pointer;">
                            <i class="fas fa-save"></i> Salvar Estrutura do Curso
                        </button>
                    </div>
                </form>
            </div>

            <script>
            (function() {
                var structure = <?= json_encode($initialStructure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
                var availablePages = <?= json_encode($availablePages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];

                function renderModules() {
                    var container = document.getElementById('modulesContainer');
                    container.innerHTML = '';

                    if (structure.length === 0) {
                        container.innerHTML = '<div style="padding:24px; text-align:center; background:rgba(0,0,0,0.2); border-radius:6px; color:#8c8f94; border:1px dashed rgba(255,255,255,0.1);">Nenhum módulo criado ainda. Clique em "Adicionar Módulo" para começar.</div>';
                        return;
                    }

                    structure.forEach(function(mod, modIdx) {
                        var modCard = document.createElement('div');
                        modCard.style.cssText = 'background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:18px;';

                        // Header do Módulo
                        var modHeader = document.createElement('div');
                        modHeader.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px;';
                        modHeader.innerHTML = `
                            <div style="flex:1; display:flex; align-items:center; gap:10px;">
                                <span style="font-size:12px; font-weight:bold; color:var(--accent-blue, #45f3ff); text-transform:uppercase;">Módulo ` + (modIdx + 1) + `</span>
                                <input type="text" value="` + escapeHtml(mod.title || '') + `" placeholder="Título do Módulo" onchange="updateModTitle(` + modIdx + `, this.value)" style="flex:1; padding:8px 12px; border-radius:4px; border:1px solid #4a545a; background:#14191c; color:#fff; font-weight:600;">
                            </div>
                            <button type="button" onclick="removeModule(` + modIdx + `)" style="background:transparent; border:none; color:#e06c75; cursor:pointer; font-size:14px;" title="Excluir Módulo">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        modCard.appendChild(modHeader);

                        // Lista de Aulas
                        var lessonsList = document.createElement('div');
                        lessonsList.style.cssText = 'display:flex; flex-direction:column; gap:10px; margin-left:15px; border-left:2px solid rgba(69,243,255,0.2); padding-left:15px;';

                        (mod.lessons || []).forEach(function(lesson, lesIdx) {
                            var lesRow = document.createElement('div');
                            lesRow.style.cssText = 'background:#14191c; border:1px solid rgba(255,255,255,0.08); border-radius:4px; padding:12px; display:flex; flex-direction:column; gap:8px;';

                            // Opções de páginas do tema para o select
                            var optionsHtml = '<option value="">-- Selecione arquivo de tema (opcional) --</option>';
                            availablePages.forEach(function(p) {
                                var selected = (lesson.source === p) ? 'selected' : '';
                                optionsHtml += '<option value="' + escapeHtml(p) + '" ' + selected + '>' + escapeHtml(p) + '</option>';
                            });

                            lesRow.innerHTML = `
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                                    <div style="flex:1; display:flex; gap:10px;">
                                        <input type="text" value="` + escapeHtml(lesson.title || '') + `" placeholder="Título da Aula" onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'title', this.value)" style="flex:2; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:13px;">
                                        <input type="text" value="` + escapeHtml(lesson.slug || '') + `" placeholder="slug-da-aula" onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'slug', this.value)" style="flex:1; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#8c8f94; font-family:monospace; font-size:12px;">
                                    </div>
                                    <button type="button" onclick="removeLesson(` + modIdx + `, ` + lesIdx + `)" style="background:transparent; border:none; color:#e06c75; cursor:pointer;" title="Excluir Aula">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <span style="font-size:12px; color:#8c8f94;">Origem:</span>
                                    <select onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'source', this.value)" style="flex:1; padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-size:12px; font-family:monospace;">
                                        ` + optionsHtml + `
                                    </select>
                                </div>
                            `;
                            lessonsList.appendChild(lesRow);
                        });

                        // Botão Adicionar Aula ao Módulo
                        var addLessonBtn = document.createElement('button');
                        addLessonBtn.type = 'button';
                        addLessonBtn.className = 'btn btn-outline';
                        addLessonBtn.style.cssText = 'padding:6px 12px; border:1px dashed rgba(255,255,255,0.2); color:#8c8f94; font-size:12px; border-radius:4px; cursor:pointer; background:transparent; align-self:flex-start; margin-top:4px;';
                        addLessonBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar Aula';
                        addLessonBtn.onclick = function() { addLesson(modIdx); };
                        lessonsList.appendChild(addLessonBtn);

                        modCard.appendChild(lessonsList);
                        container.appendChild(modCard);
                    });
                }

                function escapeHtml(str) {
                    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                }

                window.addModule = function() {
                    structure.push({
                        title: 'Novo Módulo',
                        position: structure.length + 1,
                        lessons: []
                    });
                    renderModules();
                };

                window.removeModule = function(modIdx) {
                    if (confirm('Deseja excluir este módulo e todas as suas aulas?')) {
                        structure.splice(modIdx, 1);
                        renderModules();
                    }
                };

                window.updateModTitle = function(modIdx, val) {
                    if (structure[modIdx]) {
                        structure[modIdx].title = val;
                    }
                };

                window.addLesson = function(modIdx) {
                    if (structure[modIdx]) {
                        var lesCount = (structure[modIdx].lessons || []).length + 1;
                        structure[modIdx].lessons.push({
                            title: 'Aula ' + lesCount,
                            slug: 'aula-' + lesCount,
                            source_type: 'theme_file',
                            source: '',
                            content: ''
                        });
                        renderModules();
                    }
                };

                window.removeLesson = function(modIdx, lesIdx) {
                    if (structure[modIdx] && structure[modIdx].lessons) {
                        structure[modIdx].lessons.splice(lesIdx, 1);
                        renderModules();
                    }
                };

                window.updateLessonField = function(modIdx, lesIdx, field, val) {
                    if (structure[modIdx] && structure[modIdx].lessons && structure[modIdx].lessons[lesIdx]) {
                        structure[modIdx].lessons[lesIdx][field] = val;
                    }
                };

                window.prepareStructureJson = function() {
                    document.getElementById('structure_json_input').value = JSON.stringify(structure);
                    return true;
                };

                renderModules();
            })();
            </script>
        <?php endif; ?>
        <?php
        $html = ob_get_clean();
        return new ViewResponse($html);
    }

    public function updateStructure(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $courseId = (int)$id;

        $token = $request->input('csrf_token') ?? '';
        if (empty($token) || $token !== $this->session->getCsrfToken()) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
        }

        $course = $this->repository->findById($courseId);
        if (!$course) {
            $this->session->setFlash('error', "Curso não encontrado.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        if ($this->structureRepo === null) {
            $this->session->setFlash('error', "Repositório de estrutura não disponível.");
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
        }

        $rawJson = $request->input('structure_json');
        if (!empty($rawJson)) {
            $structure = json_decode((string)$rawJson, true);
            if (!is_array($structure)) {
                $this->session->setFlash('error', "Formato da estrutura inválido.");
                return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
            }
        } else {
            $structure = $request->input('structure', []);
            if (!is_array($structure)) {
                $structure = [];
            }
        }

        try {
            $this->structureRepo->replaceCourseStructure($courseId, $structure);
            $this->session->setFlash('success', "Estrutura do curso atualizada com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao atualizar estrutura: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy/edit/' . $courseId);
    }

    public function store(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $token = $request->input('csrf_token');
        if (empty($token) || $token !== $this->session->getCsrfToken()) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $file = $request->file('thumbnail');
            if ($file && empty($file['tmp_name'])) {
                $file = null;
            }

            $data = [
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'status'      => $request->input('status'),
                'link'        => $request->input('link'),
            ];

            $this->service->createCourse($data, $file);
            $this->session->setFlash('success', "Curso cadastrado com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao cadastrar curso: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy');
    }

    public function update(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $token = $request->input('csrf_token');
        if (empty($token) || $token !== $this->session->getCsrfToken()) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $file = $request->file('thumbnail');
            if ($file && empty($file['tmp_name'])) {
                $file = null;
            }

            $data = [
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'status'      => $request->input('status'),
                'link'        => $request->input('link'),
            ];

            $this->service->updateCourse((int)$id, $data, $file);
            $this->session->setFlash('success', "Curso atualizado com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao atualizar curso: " . $e->getMessage());
            return Response::redirect($baseUrl . '/admin/academy/edit/' . $id);
        }

        return Response::redirect($baseUrl . '/admin/academy');
    }

    public function delete(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $token = $request->input('csrf_token') ?? '';
        if (empty($token) || $token !== $this->session->getCsrfToken()) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $this->service->deleteCourse((int)$id);
            $this->session->setFlash('success', "Curso excluído com sucesso!");
        } catch (Throwable $e) {
            $this->session->setFlash('error', "Erro ao excluir curso: " . $e->getMessage());
        }

        return Response::redirect($baseUrl . '/admin/academy');
    }
}
