<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Services\CourseService;
use Throwable;

class CourseAdminController
{
    private CourseRepositoryInterface $repository;
    private CourseService $service;
    private SessionManagerInterface $session;

    public function __construct(
        CourseRepositoryInterface $repository,
        CourseService $service,
        SessionManagerInterface $session
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->session = $session;
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
        <?php
        $html = ob_get_clean();
        return new ViewResponse($html);
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
