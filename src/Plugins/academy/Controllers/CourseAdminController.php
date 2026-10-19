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
use DomainSystem\Core\Contracts\PublicStorageInterface;
use DomainSystem\Plugins\academy\Services\CourseService;
use Throwable;

class CourseAdminController
{
    private CourseRepositoryInterface $repository;
    private CourseService $service;
    private SessionManagerInterface $session;
    private ?CourseStructureRepositoryInterface $structureRepo;
    private ?ThemeManagerInterface $themeManager;
    private ?PublicStorageInterface $storage;

    public function __construct(
        CourseRepositoryInterface $repository,
        CourseService $service,
        SessionManagerInterface $session,
        ?CourseStructureRepositoryInterface $structureRepo = null,
        ?ThemeManagerInterface $themeManager = null,
        ?PublicStorageInterface $storage = null
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->session = $session;
        $this->structureRepo = $structureRepo;
        $this->themeManager = $themeManager;
        $this->storage = $storage;
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
                        <th style="padding: 15px; text-align: right; width: 220px;">Ações</th>
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
                                        <?php $thumbSrc = $this->storage ? $this->storage->getPublicUrl((string)$c['thumbnail']) : $baseUrl . '/' . ltrim((string)$c['thumbnail'], '/'); ?>
                                        <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1);">
                                            <img src="<?= htmlspecialchars($thumbSrc) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
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
                                    <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; vertical-align: middle;">
                                        <a href="<?= $baseUrl ?>/admin/academy/edit/<?= $c['id'] ?>" class="btn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px; height: 32px !important; padding: 0 14px !important; box-sizing: border-box !important; line-height: 1 !important; font-size: 11px !important; font-weight: 600; text-decoration: none; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 !important; vertical-align: middle !important;" title="Editar Curso">
                                            <i class="fas fa-edit" style="font-size: 11px;"></i>
                                            <span>Editar</span>
                                        </a>
                                        <form method="POST" action="<?= $baseUrl ?>/admin/academy/delete/<?= $c['id'] ?>" style="display: inline-flex; margin: 0; padding: 0; vertical-align: middle; align-items: center;" onsubmit="return confirm('Tem certeza que deseja excluir o curso <?= htmlspecialchars(addslashes((string)$c['title'])) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" class="btn btn-deactivate" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px; height: 32px !important; padding: 0 14px !important; box-sizing: border-box !important; line-height: 1 !important; font-size: 11px !important; font-weight: 600; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 !important; vertical-align: middle !important;" title="Excluir Curso">
                                                <i class="fas fa-trash-alt" style="font-size: 11px;"></i>
                                                <span>Excluir</span>
                                            </button>
                                        </form>
                                    </div>
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
                                <option value="published" selected>Publicado</option>
                                <option value="soon">Em Breve</option>
                                <option value="draft">Rascunho</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:6px; font-weight:500;">Link de Destino</label>
                            <input type="text" name="link" placeholder="https://... ou /pagina" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="margin-bottom:20px; padding:15px; background:rgba(0,0,0,0.25); border:1px solid #4a545a; border-radius:6px; box-sizing:border-box; width:100%; max-width:100%; overflow:hidden;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Imagem de Capa (Card)</label>
                        <input type="file" id="course_new_thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="width:100%; max-width:100%; box-sizing:border-box; color:#8c8f94;">
                        
                        <div id="course_new_thumb_preview" style="display:none; margin-top:12px; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div style="width:140px; aspect-ratio:16/9; border-radius:4px; overflow:hidden; background:#000; border:1px solid var(--accent-green, #00d284); flex-shrink:0;">
                                <img id="course_new_thumb_preview_img" src="" alt="Prévia" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <span style="font-size:12px; color:var(--accent-green, #00d284);">Nova imagem selecionada pronta para envio.</span>
                        </div>

                        <script>
                            document.getElementById('course_new_thumbnail')?.addEventListener('change', function(e) {
                                const file = e.target.files && e.target.files[0];
                                const previewBox = document.getElementById('course_new_thumb_preview');
                                const previewImg = document.getElementById('course_new_thumb_preview_img');
                                if (file && previewBox && previewImg) {
                                    const reader = new FileReader();
                                    reader.onload = function(ev) {
                                        previewImg.src = ev.target.result;
                                        previewBox.style.display = 'flex';
                                    };
                                    reader.readAsDataURL(file);
                                }
                            });
                        </script>

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

        <div style="background:var(--bg-panel, #1d2327); padding:30px; border-radius:8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); max-width: 700px; width:100%; box-sizing:border-box; color:var(--text-main, #fff);">
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

                <div style="margin-bottom:18px; display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Status</label>
                        <select name="status" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                            <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
                            <option value="soon" <?= $course['status'] === 'soon' ? 'selected' : '' ?>>Em Breve</option>
                            <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        </select>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; margin-bottom:6px; font-weight:500;">Link de Destino</label>
                        <input type="text" name="link" value="<?= htmlspecialchars((string)($course['link'] ?? '')) ?>" placeholder="https://... ou /pagina" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#14191c; color:#fff; box-sizing:border-box;">
                    </div>
                </div>

                <div style="margin-bottom:24px; padding:18px; background:rgba(0,0,0,0.25); border:1px solid #4a545a; border-radius:6px; box-sizing:border-box; width:100%; max-width:100%; overflow:hidden;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Imagem de Capa (Card)</label>
                    
                    <?php if (!empty($course['thumbnail'])): ?>
                        <?php $thumbSrc = $this->storage ? $this->storage->getPublicUrl((string)$course['thumbnail']) : $baseUrl . '/' . ltrim((string)$course['thumbnail'], '/'); ?>
                        <div style="margin-bottom: 12px; display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                            <div style="width: 140px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.15); flex-shrink:0;">
                                <img src="<?= htmlspecialchars($thumbSrc) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <span style="font-size:12px; color:#8c8f94;">Capa atual configurada.<br>Envie um novo arquivo abaixo para substituir.</span>
                        </div>
                    <?php endif; ?>

                    <input type="file" id="course_edit_thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="width:100%; max-width:100%; box-sizing:border-box; color:#8c8f94;">
                    
                    <div id="course_edit_thumb_preview" style="display:none; margin-top:12px; align-items:center; gap:12px; flex-wrap:wrap;">
                        <div style="width: 140px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid var(--accent-green, #00d284); flex-shrink:0;">
                            <img id="course_edit_thumb_preview_img" src="" alt="Prévia" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <span style="font-size:12px; color:var(--accent-green, #00d284);">Nova imagem selecionada pronta para envio.</span>
                    </div>

                    <script>
                        document.getElementById('course_edit_thumbnail')?.addEventListener('change', function(e) {
                            const file = e.target.files && e.target.files[0];
                            const previewBox = document.getElementById('course_edit_thumb_preview');
                            const previewImg = document.getElementById('course_edit_thumb_preview_img');
                            if (file && previewBox && previewImg) {
                                const reader = new FileReader();
                                reader.onload = function(ev) {
                                    previewImg.src = ev.target.result;
                                    previewBox.style.display = 'flex';
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    </script>

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
            $availablePages = $this->resolvePublicThemePages();

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

            // Monta estrutura inicial para o JS preservando IDs (H-04)
            $initialStructure = [];
            foreach ($modules as $m) {
                $mId = (int)$m['id'];
                $initialStructure[] = [
                    'id'       => $mId,
                    'title'    => (string)$m['title'],
                    'position' => (int)$m['position'],
                    'lessons'  => array_map(function($les) {
                        return [
                            'id'          => (int)$les['id'],
                            'title'       => (string)$les['title'],
                            'slug'        => (string)$les['slug'],
                            'source_type' => (string)$les['source_type'],
                            'source'      => (string)($les['source'] ?? ''),
                            'content'     => (string)($les['content'] ?? ''),
                            'status'      => (string)($les['status'] ?? 'published'),
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
                            'id'          => (int)$les['id'],
                            'title'       => (string)$les['title'],
                            'slug'        => (string)$les['slug'],
                            'source_type' => (string)$les['source_type'],
                            'source'      => (string)($les['source'] ?? ''),
                            'content'     => (string)($les['content'] ?? ''),
                            'status'      => (string)($les['status'] ?? 'published'),
                        ];
                    }, $standaloneLessons),
                ];
            }
            ?>
            <div style="margin-top:35px; background:var(--bg-panel, #1d2327); padding:30px; border-radius:8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); max-width: 960px; color:var(--text-main, #fff);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="margin:0; font-size:1.3rem; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-sitemap" style="color:var(--accent-blue, #45f3ff);"></i> Estrutura do Curso (Módulos &amp; Aulas)
                        </h2>
                        <p style="margin:5px 0 0 0; color:var(--text-muted, #8c8f94); font-size:13px;">
                            Crie aulas com o <strong>⚡ Editor em Blocos</strong> (vídeos 16:9, códigos e alertas), vincule <strong>📄 Arquivos do Tema</strong> ou importe uma <strong>📂 Pasta Inteira</strong> com 1 clique.
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

            <!-- MODAL IMPORTAR PASTA DO TEMA -->
            <div id="modalImportFolder" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.82); z-index:9999; justify-content:center; align-items:center;">
                <div style="background:var(--bg-panel, #1d2327); padding:28px; border-radius:12px; width:520px; max-width:92%; color:var(--text-main, #fff); border: 1px solid rgba(69,243,255,0.3); box-shadow:0 15px 40px rgba(0,0,0,0.6);">
                    <h3 style="margin-top:0; color:#fff; display:flex; align-items:center; gap:10px;">
                        📂 Importar Pasta de Aulas do Tema
                    </h3>
                    <p style="font-size:13px; color:#8c8f94; line-height:1.5; margin-bottom:18px;">
                        Selecione uma pasta do tema ativo. Todos os arquivos <code>.php</code> dentro dela serão adicionados automaticamente como aulas ordenadas no módulo selecionado.
                    </p>
                    <input type="hidden" id="import_target_mod_idx" value="0">
                    <div style="margin-bottom:18px;">
                        <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600;">Pasta de Origem:</label>
                        <select id="import_folder_select" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#0b0c10; color:#45f3ff; font-family:monospace; font-size:13px;"></select>
                    </div>
                    <div id="import_folder_preview" style="max-height:180px; overflow-y:auto; background:#0b0c10; border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:10px; font-family:monospace; font-size:12px; color:#c5c6c7; margin-bottom:20px;"></div>
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" onclick="closeImportFolderModal()" class="btn btn-outline" style="padding:8px 16px; border:1px solid #4a545a; background:transparent; color:#8c8f94; border-radius:6px; cursor:pointer;">Cancelar</button>
                        <button type="button" onclick="confirmImportFolder()" class="btn btn-activate" style="padding:8px 18px; font-weight:600; cursor:pointer;">📂 Importar Aulas</button>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                var baseUrl = <?= json_encode($baseUrl) ?>;
                var structure = <?= json_encode($initialStructure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
                var availablePages = <?= json_encode($availablePages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];

                // Agrupa páginas por pasta para o recurso de Auto-Importação de Pasta
                var foldersMap = {};
                availablePages.forEach(function(pagePath) {
                    var lastSlash = pagePath.lastIndexOf('/');
                    var dir = lastSlash > 0 ? pagePath.substring(0, lastSlash) : 'pages';
                    if (!foldersMap[dir]) {
                        foldersMap[dir] = [];
                    }
                    foldersMap[dir].push(pagePath);
                });

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
                        modHeader.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px; flex-wrap:wrap;';
                        modHeader.innerHTML = `
                            <div style="flex:1; min-width:240px; display:flex; align-items:center; gap:10px;">
                                <span style="font-size:12px; font-weight:bold; color:var(--accent-blue, #45f3ff); text-transform:uppercase;">Módulo ` + (modIdx + 1) + `</span>
                                <input type="text" value="` + escapeHtml(mod.title || '') + `" placeholder="Título do Módulo" onchange="updateModTitle(` + modIdx + `, this.value)" style="flex:1; padding:8px 12px; border-radius:4px; border:1px solid #4a545a; background:#14191c; color:#fff; font-weight:600;">
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" onclick="openImportFolderModal(` + modIdx + `)" style="padding:6px 12px; border:1px solid rgba(69,243,255,0.35); background:rgba(69,243,255,0.08); color:#45f3ff; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600;" title="Importar todas as aulas de uma pasta do tema">
                                    📂 Importar Pasta do Tema
                                </button>
                                <button type="button" onclick="removeModule(` + modIdx + `)" style="background:transparent; border:none; color:#e06c75; cursor:pointer; font-size:14px; padding:4px 8px;" title="Excluir Módulo">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        modCard.appendChild(modHeader);

                        // Lista de Aulas
                        var lessonsList = document.createElement('div');
                        lessonsList.style.cssText = 'display:flex; flex-direction:column; gap:10px; margin-left:15px; border-left:2px solid rgba(69,243,255,0.2); padding-left:15px;';

                        (mod.lessons || []).forEach(function(lesson, lesIdx) {
                            var lesRow = document.createElement('div');
                            lesRow.style.cssText = 'background:#14191c; border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:12px; display:flex; flex-direction:column; gap:10px;';

                            var srcType = lesson.source_type || (lesson.source ? 'theme_file' : 'content');
                            var statusVal = lesson.status || 'published';

                            // Opções de páginas do tema para o select
                            var optionsHtml = '<option value="">-- Selecione arquivo de tema (.php) --</option>';
                            availablePages.forEach(function(p) {
                                var selected = (lesson.source === p) ? 'selected' : '';
                                optionsHtml += '<option value="' + escapeHtml(p) + '" ' + selected + '>' + escapeHtml(p) + '</option>';
                            });

                            var editorActionHtml = '';
                            if (lesson.id && parseInt(lesson.id) > 0) {
                                editorActionHtml = `<a href="` + baseUrl + `/admin/academy/lessons/` + parseInt(lesson.id) + `/editor" style="padding:6px 12px; border-radius:4px; background:rgba(0,210,132,0.12); border:1px solid rgba(0,210,132,0.4); color:#00d284; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; display:inline-flex; align-items:center; gap:5px;" title="Abrir Editor Visual em Blocos desta aula">⚡ Editor de Blocos</a>`;
                            } else {
                                editorActionHtml = `<span style="font-size:11px; color:#8c8f94; padding:4px 8px; background:rgba(255,255,255,0.04); border-radius:4px;" title="Salve a estrutura abaixo para habilitar o Editor de Blocos desta nova aula">💾 Salve p/ Editar Blocos</span>`;
                            }

                            var themeSelectDisplay = (srcType === 'theme_file') ? 'flex' : 'none';

                            lesRow.innerHTML = `
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                                    <div style="flex:1; min-width:260px; display:flex; gap:8px;">
                                        <input type="text" value="` + escapeHtml(lesson.title || '') + `" placeholder="Título da Aula" onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'title', this.value)" style="flex:2; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:13px;">
                                        <input type="text" value="` + escapeHtml(lesson.slug || '') + `" placeholder="slug-da-aula" onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'slug', this.value)" style="flex:1; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#8c8f94; font-family:monospace; font-size:12px;">
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <select onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'status', this.value)" style="padding:6px 8px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#c5c6c7; font-size:12px;">
                                            <option value="published" ` + (statusVal === 'published' ? 'selected' : '') + `>🟢 Publicada</option>
                                            <option value="draft" ` + (statusVal === 'draft' ? 'selected' : '') + `>⚪ Rascunho</option>
                                        </select>
                                        ` + editorActionHtml + `
                                        <button type="button" onclick="removeLesson(` + modIdx + `, ` + lesIdx + `)" style="background:transparent; border:none; color:#e06c75; cursor:pointer; padding:4px 6px;" title="Excluir Aula">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                    <span style="font-size:12px; color:#8c8f94;">Modo:</span>
                                    <select onchange="updateLessonSourceType(` + modIdx + `, ` + lesIdx + `, this.value)" style="padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:12px;">
                                        <option value="content" ` + (srcType === 'content' ? 'selected' : '') + `>⚡ Editor em Blocos (Conteúdo)</option>
                                        <option value="theme_file" ` + (srcType === 'theme_file' ? 'selected' : '') + `>📄 Arquivo do Tema (.php)</option>
                                    </select>
                                    <div style="flex:1; min-width:220px; display:` + themeSelectDisplay + `; align-items:center; gap:8px;">
                                        <span style="font-size:12px; color:#8c8f94;">Arquivo:</span>
                                        <select onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'source', this.value)" style="flex:1; padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-size:12px; font-family:monospace;">
                                            ` + optionsHtml + `
                                        </select>
                                    </div>
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

                function formatFilenameToTitle(filePath) {
                    var fileName = filePath.split('/').pop().replace(/\.(php|html)$/i, '');
                    var match = fileName.match(/^(\d+)[-_]+(.*)$/);
                    if (match) {
                        var num = match[1];
                        var rest = match[2].replace(/[-_]+/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                        return num + ' - ' + rest;
                    }
                    return fileName.replace(/[-_]+/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                }

                function formatFilenameToSlug(filePath) {
                    var fileName = filePath.split('/').pop().replace(/\.(php|html)$/i, '');
                    return fileName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                }

                window.openImportFolderModal = function(modIdx) {
                    document.getElementById('import_target_mod_idx').value = modIdx;
                    var select = document.getElementById('import_folder_select');
                    select.innerHTML = '';

                    var folderKeys = Object.keys(foldersMap);
                    if (folderKeys.length === 0) {
                        select.innerHTML = '<option value="">Nenhuma pasta encontrada em pages/</option>';
                        document.getElementById('import_folder_preview').innerHTML = 'Nenhum arquivo .php encontrado na pasta pages/ do tema.';
                    } else {
                        folderKeys.forEach(function(folder) {
                            var opt = document.createElement('option');
                            opt.value = folder;
                            opt.textContent = folder + '/ (' + foldersMap[folder].length + ' arquivos)';
                            select.appendChild(opt);
                        });
                        updateImportFolderPreview();
                    }

                    select.onchange = updateImportFolderPreview;
                    document.getElementById('modalImportFolder').style.display = 'flex';
                };

                function updateImportFolderPreview() {
                    var folder = document.getElementById('import_folder_select').value;
                    var preview = document.getElementById('import_folder_preview');
                    var files = foldersMap[folder] || [];
                    if (files.length === 0) {
                        preview.innerHTML = 'Nenhum arquivo nesta pasta.';
                        return;
                    }
                    preview.innerHTML = files.map(function(f) {
                        return '<div style="padding:3px 0; border-bottom:1px solid rgba(255,255,255,0.04);">📄 ' + escapeHtml(f) + ' &rarr; <strong style="color:#45f3ff;">' + escapeHtml(formatFilenameToTitle(f)) + '</strong></div>';
                    }).join('');
                }

                window.closeImportFolderModal = function() {
                    document.getElementById('modalImportFolder').style.display = 'none';
                };

                window.confirmImportFolder = function() {
                    var modIdx = parseInt(document.getElementById('import_target_mod_idx').value, 10);
                    var folder = document.getElementById('import_folder_select').value;
                    var files = foldersMap[folder] || [];

                    if (!structure[modIdx] || files.length === 0) {
                        closeImportFolderModal();
                        return;
                    }

                    if (!Array.isArray(structure[modIdx].lessons)) {
                        structure[modIdx].lessons = [];
                    }

                    var existingSources = {};
                    structure[modIdx].lessons.forEach(function(l) {
                        if (l.source) existingSources[l.source] = true;
                    });

                    files.forEach(function(filePath) {
                        if (!existingSources[filePath]) {
                            structure[modIdx].lessons.push({
                                title: formatFilenameToTitle(filePath),
                                slug: formatFilenameToSlug(filePath),
                                source_type: 'theme_file',
                                source: filePath,
                                content: '',
                                status: 'published'
                            });
                        }
                    });

                    closeImportFolderModal();
                    renderModules();
                };

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
                            source_type: 'content',
                            source: '',
                            content: '',
                            status: 'published'
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
                        if (field === 'source' && val !== '') {
                            structure[modIdx].lessons[lesIdx].source_type = 'theme_file';
                        }
                    }
                };

                window.updateLessonSourceType = function(modIdx, lesIdx, val) {
                    if (structure[modIdx] && structure[modIdx].lessons && structure[modIdx].lessons[lesIdx]) {
                        structure[modIdx].lessons[lesIdx].source_type = val;
                        if (val === 'content') {
                            structure[modIdx].lessons[lesIdx].source = '';
                        }
                        renderModules();
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

    private function verifyCsrfToken(Request $request): bool
    {
        $token = (string)($request->input('csrf_token') ?? '');
        $expected = (string)$this->session->getCsrfToken();

        if ($token === '' || $expected === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public function updateStructure(Request $request, $id): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $courseId = (int)$id;

        if (!$this->verifyCsrfToken($request)) {
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
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $file = $request->file('thumbnail');
            if ($file && (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_NO_FILE)) {
                $file = null;
            } elseif ($file && ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                    throw new \InvalidArgumentException("O arquivo de imagem enviado excedeu o tamanho máximo permitido pelo servidor.");
                }
                throw new \InvalidArgumentException("Erro no envio do arquivo de imagem (Código: " . ($file['error'] ?? 'desconhecido') . ").");
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
        if (!$this->verifyCsrfToken($request)) {
            $this->session->setFlash('error', "Token de segurança CSRF inválido.");
            return Response::redirect($baseUrl . '/admin/academy');
        }

        try {
            $file = $request->file('thumbnail');
            if ($file && (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_NO_FILE)) {
                $file = null;
            } elseif ($file && ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                    throw new \InvalidArgumentException("O arquivo de imagem enviado excedeu o tamanho máximo permitido pelo servidor.");
                }
                throw new \InvalidArgumentException("Erro no envio do arquivo de imagem (Código: " . ($file['error'] ?? 'desconhecido') . ").");
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
        if (!$this->verifyCsrfToken($request)) {
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

    /**
     * Resolve todas as páginas (.php / .html) disponíveis no tema público ativo para vinculação e importação em lote.
     *
     * @return array<int, string>
     */
    private function resolvePublicThemePages(): array
    {
        $pages = [];

        if ($this->themeManager !== null) {
            $pages = $this->themeManager->getAvailablePages();
        }

        if (empty($pages)) {
            $baseRoot = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);
            $siteState = new \DomainSystem\Core\Theme\EnvSiteState();
            $activePublicTheme = $siteState->getActiveTheme();

            if ($this->themeManager !== null && $activePublicTheme !== '') {
                $pages = $this->themeManager->getAvailablePages($activePublicTheme);
            }

            if (empty($pages)) {
                $resolver = new \DomainSystem\Core\Theme\SafeThemeResolver([
                    $baseRoot . '/themes',
                    $baseRoot . '/src/Plugins/flextheme/themes',
                ]);
                $candidateThemes = array_unique(array_filter([$activePublicTheme, 'rachidd', 'default']));
                foreach ($candidateThemes as $themeName) {
                    if ($resolver->themeExists($themeName)) {
                        $pagesDir = $resolver->resolveThemePath($themeName) . '/pages';
                        if (is_dir($pagesDir)) {
                            $iterator = new \RecursiveIteratorIterator(
                                new \RecursiveDirectoryIterator($pagesDir, \FilesystemIterator::SKIP_DOTS)
                            );
                            foreach ($iterator as $file) {
                                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php', 'html'], true)) {
                                    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($pagesDir) + 1));
                                    $pages[] = 'pages/' . ltrim($rel, '/');
                                }
                            }
                            if (!empty($pages)) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $pages = array_values(array_unique($pages));
        natsort($pages);
        return array_values($pages);
    }
}
