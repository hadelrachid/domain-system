<?php

namespace DomainSystem\Plugins\academy\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Core\Http\Responses\RedirectResponse;

class CourseAdminController
{
    private \PDO $db;
    private SessionManagerInterface $session;
    private \DomainSystem\SystemApps\Database\Schema\SchemaBuilder $schema;

    public function __construct(
        \DomainSystem\SystemApps\Database\Connection $connection,
        \DomainSystem\SystemApps\Database\Schema\SchemaBuilder $schema
    ) {
        $this->db = $connection->getPdo();
        $this->session = $session;
        $this->schema = $schema;
    }

    public function index(Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        // Auto-migração silenciosa para Hostinger (MySQL) / Local (SQLite)
        try {
            $this->schema->create('academy_courses', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('thumbnail')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('draft');
                $table->string('link')->nullable();
                $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
            });
        } catch (\Exception $e) {
            // Ignora se a tabela já existir no DB
        }

        $stmt = $this->db->query("SELECT * FROM academy_courses ORDER BY id DESC");
        $courses = $stmt->fetchAll();

        ob_start();
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; display:flex; align-items:center; gap:10px;"><i class="fas fa-graduation-cap" style="color:var(--accent-blue);"></i> Academy (Cursos)</h1>
            <button onclick="document.getElementById('modalNewCourse').style.display='flex'" class="btn btn-activate" style="padding: 10px 20px;">
                <i class="fas fa-plus"></i> Novo Curso
            </button>
        </div>

        <div style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; color: var(--text-main);">
                <thead>
                    <tr style="background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--border);">
                        <th style="padding: 15px; text-align: left;">ID</th>
                        <th style="padding: 15px; text-align: left;">Título</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($courses)): ?>
                        <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">Nenhum curso cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($courses as $c): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px; color: var(--text-muted);">#<?= $c['id'] ?></td>
                                <td style="padding: 15px; font-weight: 500;"><?= htmlspecialchars($c['title']) ?></td>
                                <td style="padding: 15px;">
                                    <?php if($c['status'] == 'published'): ?>
                                        <span style="background: rgba(40,167,69,0.1); color:#28a745; padding: 4px 8px; border-radius: 4px; font-size: 12px; border: 1px solid rgba(40,167,69,0.3);">Publicado</span>
                                    <?php elseif($c['status'] == 'soon'): ?>
                                        <span style="background: rgba(240,173,78,0.1); color:#f0ad4e; padding: 4px 8px; border-radius: 4px; font-size: 12px; border: 1px solid rgba(240,173,78,0.3);">Em Breve</span>
                                    <?php else: ?>
                                        <span style="background: rgba(255,255,255,0.05); color:#8c8f94; padding: 4px 8px; border-radius: 4px; font-size: 12px; border: 1px solid rgba(255,255,255,0.1);">Rascunho</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: right;">
                                    <form method="POST" action="<?= BASE_URL ?>/admin/academy/delete/<?= $c['id'] ?>" style="display:inline;" onsubmit="event.preventDefault(); const f = this; OS.confirm('Excluir curso?', () => f.submit());">
                                        <input type="hidden" name="csrf_token" value="<?= $this->session->getCsrfToken() ?>">
                                        <button type="submit" style="background:transparent; border:none; color:#dc3232; cursor:pointer;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: rgba(88,166,255,0.05); border: 1px solid rgba(88,166,255,0.2); border-radius: 8px; color: #8c8f94; display:flex; align-items:flex-start; gap: 15px;">
            <i class="fas fa-info-circle" style="color: var(--accent-blue); font-size: 20px; margin-top: 2px;"></i>
            <div>
                <strong style="color: var(--text-main); display:block; margin-bottom: 5px;">Como exibir a vitrine no site?</strong>
                Basta utilizar o shortcode <code style="background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; color:#fff;">[hub_de_cursos]</code> dentro de qualquer página (como a página de Tutoriais). O Kernel irá renderizar a grade automaticamente com o design do FlexTheme.
            </div>
        </div>

        <!-- MODAL -->
        <div id="modalNewCourse" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
            <div style="background:var(--bg-panel); padding:30px; border-radius:12px; width:500px; color:var(--text-main); border: 1px solid var(--border);">
                <h3 style="margin-top:0; color:#fff;">Cadastrar Curso</h3>
                
                <form method="POST" action="<?= BASE_URL ?>/admin/academy/store">
                    <input type="hidden" name="csrf_token" value="<?= $this->session->getCsrfToken() ?>">
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px;">Título do Curso</label>
                        <input type="text" name="title" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#1d2327; color:#fff; box-sizing:border-box;">
                    </div>
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px;">Descrição Curta</label>
                        <textarea name="description" rows="3" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#1d2327; color:#fff; box-sizing:border-box;"></textarea>
                    </div>

                    <div style="margin-bottom:15px; display:flex; gap:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px;">Status</label>
                            <select name="status" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#1d2327; color:#fff; box-sizing:border-box;">
                                <option value="soon">Em Breve</option>
                                <option value="published">Publicado</option>
                                <option value="draft">Rascunho</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px;">Link (Opcional)</label>
                            <input type="text" name="link" placeholder="https..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#1d2327; color:#fff; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                        <button type="button" onclick="document.getElementById('modalNewCourse').style.display='none'" class="btn btn-outline" style="padding:8px 15px; border-color:#4a545a; color:#8c8f94;">Cancelar</button>
                        <button type="submit" class="btn btn-activate" style="padding:8px 15px;">Salvar Curso</button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        $html = ob_get_clean();
        return new ViewResponse($html);
    }

    public function store(Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        // Validação CSRF
        $token = $request->input('csrf_token');
        if (empty($token) || $token !== $this->session->getCsrfToken()) {
            return Response::redirect(BASE_URL . '/admin/academy');
        }

        $title = $request->input('title');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $description = $request->input('description');
        $status = in_array($request->input('status'), ['published', 'draft', 'soon']) ? $request->input('status') : 'draft';
        $link = $request->input('link');

        $stmt = $this->db->prepare("INSERT INTO academy_courses (title, slug, description, status, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $description, $status, $link]);

        return Response::redirect(BASE_URL . '/admin/academy');
    }

    public function delete($id): \DomainSystem\Core\Contracts\ResponseInterface
    {
        // Validação CSRF
        $token = $request->input('csrf_token') ?? '';
        if (empty($token) || $token !== $this->session->getCsrfToken()) {
            return Response::redirect(BASE_URL . '/admin/academy');
        }

        $stmt = $this->db->prepare("DELETE FROM academy_courses WHERE id = ?");
        $stmt->execute([$id]);
        return Response::redirect(BASE_URL . '/admin/academy');
    }
}

