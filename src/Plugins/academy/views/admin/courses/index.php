<?php
/**
 * View Administrativa: Lista de Cursos Academy
 *
 * @var array<int, array<string, mixed>> $courses
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 * @var \DomainSystem\Core\Contracts\PublicStorageInterface|null $storage
 */
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
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars((string)$flashMsg) ?>
    </div>
<?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
    <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars((string)$flashMsg) ?>
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
                        <td style="padding: 15px; color: var(--text-muted, #8c8f94);">#<?= (int)$c['id'] ?></td>
                        <td style="padding: 15px;">
                            <?php if (!empty($c['thumbnail'])): ?>
                                <?php $thumbSrc = isset($storage) && $storage ? $storage->getPublicUrl((string)$c['thumbnail']) : $baseUrl . '/' . ltrim((string)$c['thumbnail'], '/'); ?>
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
                                <a href="<?= $baseUrl ?>/admin/academy/edit/<?= (int)$c['id'] ?>" class="btn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px; height: 32px !important; padding: 0 14px !important; box-sizing: border-box !important; line-height: 1 !important; font-size: 11px !important; font-weight: 600; text-decoration: none; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 !important; vertical-align: middle !important;" title="Editar Curso">
                                    <i class="fas fa-edit" style="font-size: 11px;"></i>
                                    <span>Editar</span>
                                </a>
                                <form method="POST" action="<?= $baseUrl ?>/admin/academy/delete/<?= (int)$c['id'] ?>" class="ds-confirm-delete-form" data-confirm-title="<?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?>" style="display: inline-flex; margin: 0; padding: 0; vertical-align: middle; align-items: center;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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

<script>
document.querySelectorAll('.ds-confirm-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var title = form.getAttribute('data-confirm-title') || '';
        if (!confirm('Tem certeza que deseja excluir o curso "' + title + '"?')) {
            e.preventDefault();
        }
    });
});
</script>

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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
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
