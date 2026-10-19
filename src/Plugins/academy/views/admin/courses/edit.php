<?php
/**
 * View Administrativa: Edição de Curso + Estrutura de Módulos, Aulas e Fábrica de Arquivos PHP
 *
 * @var array<string, mixed> $course
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 * @var \DomainSystem\Core\Contracts\PublicStorageInterface|null $storage
 * @var bool $hasStructureRepo
 * @var array<int, array<string, mixed>> $initialStructure
 * @var array<int, string> $availablePages
 */
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-edit" style="color:var(--accent-blue, #45f3ff);"></i> Editar Curso #<?= (int)$course['id'] ?>
    </h1>
    <a href="<?= $baseUrl ?>/admin/academy" class="btn btn-outline" style="padding: 8px 16px; border:1px solid #4a545a; border-radius:6px; color:#fff; text-decoration:none;">
        &larr; Voltar para Lista
    </a>
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

<div style="background:var(--bg-panel, #1d2327); padding:30px; border-radius:8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); max-width: 700px; width:100%; box-sizing:border-box; color:var(--text-main, #fff);">
    <form method="POST" action="<?= $baseUrl ?>/admin/academy/update/<?= (int)$course['id'] ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

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
                <?php $thumbSrc = isset($storage) && $storage ? $storage->getPublicUrl((string)$course['thumbnail']) : $baseUrl . '/' . ltrim((string)$course['thumbnail'], '/'); ?>
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

<?php if (!empty($hasStructureRepo)): ?>
    <div style="margin-top:35px; background:var(--bg-panel, #1d2327); padding:30px; border-radius:8px; border: 1px solid var(--border, rgba(255,255,255,0.1)); max-width: 980px; color:var(--text-main, #fff);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 style="margin:0; font-size:1.3rem; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-sitemap" style="color:var(--accent-blue, #45f3ff);"></i> Estrutura do Curso (Módulos &amp; Aulas)
                </h2>
                <p style="margin:5px 0 0 0; color:var(--text-muted, #8c8f94); font-size:13px;">
                    Crie aulas com o <strong>⚡ Editor em Blocos</strong>, <strong>🏭 Fabrique automaticamente pastas e arquivos <code>.php</code></strong> (ex: <em>"Estrutura de Dados"</em> &rarr; <code>pages/estrutura-de-dados.php</code>) ou importe uma <strong>📂 Pasta Inteira</strong>.
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <button type="button" onclick="openCreateFolderModal()" class="btn btn-outline" style="padding:8px 14px; border:1px solid rgba(0,210,132,0.45); background:rgba(0,210,132,0.08); color:#00d284; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;" title="Criar pasta física em pages/ e módulo vinculado">
                    📁 Criar Pasta (ou Módulo)
                </button>
                <a href="<?= $baseUrl ?>/cursos/<?= urlencode((string)$course['slug']) ?>" target="_blank" class="btn btn-outline" style="padding:8px 14px; border:1px solid rgba(69,243,255,0.3); color:var(--accent-blue, #45f3ff); border-radius:6px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-external-link-alt"></i> Ver no Site
                </a>
            </div>
        </div>

        <form method="POST" action="<?= $baseUrl ?>/admin/academy/structure/<?= (int)$course['id'] ?>" id="formCourseStructure">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="structure_json" id="structure_json_input" value="">

            <div style="margin-bottom:18px; padding:12px 16px; border-radius:8px; background:rgba(0,210,132,0.06); border:1px solid rgba(0,210,132,0.25); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; color:#e6edf3;">
                    <input type="checkbox" name="auto_fabricate_php" id="auto_fabricate_php_checkbox" value="1" checked style="width:16px; height:16px; accent-color:#00d284;">
                    <span>
                        <strong>🏭 Fábrica Automática de Arquivos PHP:</strong> Ao salvar, fabricar automaticamente arquivos <code>pages/{slug}.php</code> pré-configurados (com CSS/JS Mobile &amp; Desktop e Blocos) para aulas sem arquivo existente.
                    </span>
                </label>
            </div>

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

    <!-- MODAL CRIAR PASTA FÍSICA / MÓDULO NO TEMA -->
    <div id="modalCreateFolder" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.82); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:var(--bg-panel, #1d2327); padding:28px; border-radius:12px; width:520px; max-width:92%; color:var(--text-main, #fff); border: 1px solid rgba(0,210,132,0.4); box-shadow:0 15px 40px rgba(0,0,0,0.6);">
            <h3 style="margin-top:0; color:#fff; display:flex; align-items:center; gap:10px;">
                📁 Criar Pasta (ou Módulo) no Tema Ativo
            </h3>
            <p style="font-size:13px; color:#8c8f94; line-height:1.5; margin-bottom:18px;">
                Cria fisicamente um diretório dentro de <code>pages/</code> no tema ativo (via <code>LessonFileScaffolderInterface</code>) e adiciona o módulo correspondente na estrutura do curso.
            </p>
            <form method="POST" action="<?= $baseUrl ?>/admin/academy/scaffold/folder/<?= (int)$course['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div style="margin-bottom:14px;">
                    <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:600;">Título do Módulo / Pasta *</label>
                    <input type="text" name="folder_name" id="new_folder_name_input" required placeholder="Ex: Módulo 01 - Estrutura de Dados" oninput="updateNewFolderSlugPreview(this.value)" style="width:100%; padding:10px; border-radius:6px; border:1px solid #4a545a; background:#0b0c10; color:#fff; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:18px; padding:10px 12px; border-radius:6px; background:#0b0c10; border:1px solid rgba(0,210,132,0.25); font-family:monospace; font-size:12px; color:#00d284;">
                    📂 Caminho físico que será criado: <strong id="new_folder_slug_preview">pages/modulo-01-estrutura-de-dados</strong>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeCreateFolderModal()" class="btn btn-outline" style="padding:8px 16px; border:1px solid #4a545a; background:transparent; color:#8c8f94; border-radius:6px; cursor:pointer;">Cancelar</button>
                    <button type="submit" class="btn btn-activate" style="padding:8px 18px; font-weight:600; cursor:pointer;">📁 Criar Pasta &amp; Módulo</button>
                </div>
            </form>
        </div>
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
        var courseSlug = <?= json_encode((string)$course['slug']) ?>;
        var structure = <?= json_encode($initialStructure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
        var availablePages = <?= json_encode($availablePages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];

        var foldersMap = {};
        availablePages.forEach(function(pagePath) {
            var lastSlash = pagePath.lastIndexOf('/');
            var dir = lastSlash > 0 ? pagePath.substring(0, lastSlash) : 'pages';
            if (!foldersMap[dir]) {
                foldersMap[dir] = [];
            }
            foldersMap[dir].push(pagePath);
        });

        function slugifyText(raw) {
            var map = {'á':'a','à':'a','ã':'a','â':'a','ä':'a','é':'e','è':'e','ê':'e','ë':'e','í':'i','ì':'i','î':'i','ï':'i','ó':'o','ò':'o','õ':'o','ô':'o','ö':'o','ú':'u','ù':'u','û':'u','ü':'u','ç':'c','ñ':'n'};
            var str = String(raw || '').toLowerCase().replace(/[áàãâäéèêëíìîïóòõôöúùûüçñ]/g, function(c) { return map[c] || c; });
            return str.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'aula';
        }

        window.openCreateFolderModal = function() {
            document.getElementById('modalCreateFolder').style.display = 'flex';
        };
        window.closeCreateFolderModal = function() {
            document.getElementById('modalCreateFolder').style.display = 'none';
        };
        window.updateNewFolderSlugPreview = function(val) {
            var s = slugifyText(val);
            document.getElementById('new_folder_slug_preview').textContent = 'pages/' + s;
        };

        function renderModules() {
            var container = document.getElementById('modulesContainer');
            container.innerHTML = '';

            if (structure.length === 0) {
                container.innerHTML = '<div style="padding:24px; text-align:center; background:rgba(0,0,0,0.2); border-radius:6px; color:#8c8f94; border:1px dashed rgba(255,255,255,0.1);">Nenhum módulo criado ainda. Clique em "Adicionar Módulo" ou "📁 Criar Pasta (ou Módulo)" para começar.</div>';
                return;
            }

            structure.forEach(function(mod, modIdx) {
                var modCard = document.createElement('div');
                modCard.style.cssText = 'background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:18px;';

                var modFolderSlug = mod.folder_slug || '';
                var modHeader = document.createElement('div');
                modHeader.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px; flex-wrap:wrap;';
                modHeader.innerHTML = `
                    <div style="flex:1; min-width:260px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span style="font-size:12px; font-weight:bold; color:var(--accent-blue, #45f3ff); text-transform:uppercase;">Módulo ` + (modIdx + 1) + `</span>
                        <input type="text" value="` + escapeHtml(mod.title || '') + `" placeholder="Título do Módulo" onchange="updateModTitle(` + modIdx + `, this.value)" style="flex:2; min-width:180px; padding:8px 12px; border-radius:4px; border:1px solid #4a545a; background:#14191c; color:#fff; font-weight:600;">
                        <input type="text" value="` + escapeHtml(modFolderSlug) + `" placeholder="Pasta opcional (ex: modulo-1)" onchange="updateModFolderSlug(` + modIdx + `, this.value)" style="flex:1; min-width:140px; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#00d284; font-family:monospace; font-size:12px;" title="Subpasta dentro de pages/ onde novos arquivos .php deste módulo serão fabricados">
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

                var lessonsList = document.createElement('div');
                lessonsList.style.cssText = 'display:flex; flex-direction:column; gap:10px; margin-left:15px; border-left:2px solid rgba(69,243,255,0.2); padding-left:15px;';

                (mod.lessons || []).forEach(function(lesson, lesIdx) {
                    var lesRow = document.createElement('div');
                    lesRow.style.cssText = 'background:#14191c; border:1px solid rgba(255,255,255,0.08); border-radius:6px; padding:12px; display:flex; flex-direction:column; gap:10px;';

                    var srcType = lesson.source_type || (lesson.source ? 'theme_file' : 'content');
                    var statusVal = lesson.status || 'published';
                    var mobProfile = lesson.mobile_profile || 'drawer';
                    var computedSlug = lesson.slug || slugifyText(lesson.title || ('aula-' + (lesIdx + 1)));
                    var targetFile = lesson.source || ('pages/' + (modFolderSlug ? (modFolderSlug + '/') : '') + computedSlug + '.php');

                    var optionsHtml = '<option value="">-- Auto-Fabricar (' + escapeHtml(targetFile) + ') ou Selecione (.php) --</option>';
                    availablePages.forEach(function(p) {
                        var selected = (lesson.source === p) ? 'selected' : '';
                        optionsHtml += '<option value="' + escapeHtml(p) + '" ' + selected + '>' + escapeHtml(p) + '</option>';
                    });
                    if (lesson.source && availablePages.indexOf(lesson.source) === -1) {
                        optionsHtml += '<option value="' + escapeHtml(lesson.source) + '" selected>' + escapeHtml(lesson.source) + ' (Fabricado)</option>';
                    }

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
                                <input type="text" value="` + escapeHtml(lesson.title || '') + `" placeholder="Título da Aula (ex: Estrutura de Dados)" onchange="updateLessonTitleAndAutoSlug(` + modIdx + `, ` + lesIdx + `, this.value)" style="flex:2; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#fff; font-size:13px;">
                                <input type="text" value="` + escapeHtml(lesson.slug || '') + `" placeholder="slug-da-aula" onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'slug', this.value)" style="flex:1; padding:7px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#8c8f94; font-family:monospace; font-size:12px;">
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <select onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'mobile_profile', this.value)" style="padding:6px 8px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-size:12px;" title="Perfil de Layout Mobile desta aula">
                                    <option value="drawer" ` + (mobProfile === 'drawer' ? 'selected' : '') + `>📱 Mobile: Gaveta</option>
                                    <option value="bottom_bar" ` + (mobProfile === 'bottom_bar' ? 'selected' : '') + `>📲 Mobile: Barra Fixa</option>
                                    <option value="cinema" ` + (mobProfile === 'cinema' ? 'selected' : '') + `>🎬 Mobile: Cinema 16:9</option>
                                </select>
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
                                <option value="content" ` + (srcType === 'content' ? 'selected' : '') + `>⚡ Editor em Blocos (Conteúdo + PHP)</option>
                                <option value="theme_file" ` + (srcType === 'theme_file' ? 'selected' : '') + `>📄 Arquivo do Tema (.php)</option>
                            </select>
                            <div style="flex:1; min-width:220px; display:` + themeSelectDisplay + `; align-items:center; gap:8px;">
                                <span style="font-size:12px; color:#8c8f94;">Arquivo:</span>
                                <select onchange="updateLessonField(` + modIdx + `, ` + lesIdx + `, 'source', this.value)" style="flex:1; padding:6px 10px; border-radius:4px; border:1px solid #3a444a; background:#0b0c10; color:#45f3ff; font-size:12px; font-family:monospace;">
                                    ` + optionsHtml + `
                                </select>
                            </div>
                            <span style="font-size:11px; color:#64748b; font-family:monospace;">
                                🔗 /cursos/` + escapeHtml(computedSlug) + ` &bull; 📄 ` + escapeHtml(targetFile) + `
                            </span>
                        </div>
                    `;
                    lessonsList.appendChild(lesRow);
                });

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
                        mobile_profile: 'drawer',
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
                folder_slug: '',
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

        window.updateModFolderSlug = function(modIdx, val) {
            if (structure[modIdx]) {
                structure[modIdx].folder_slug = val ? slugifyText(val) : '';
                renderModules();
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
                    mobile_profile: 'drawer',
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

        window.updateLessonTitleAndAutoSlug = function(modIdx, lesIdx, val) {
            if (structure[modIdx] && structure[modIdx].lessons && structure[modIdx].lessons[lesIdx]) {
                var les = structure[modIdx].lessons[lesIdx];
                les.title = val;
                var newSlug = slugifyText(val);
                if (!les.slug || /^aula-\d+$/.test(les.slug)) {
                    les.slug = newSlug;
                }
                renderModules();
            }
        };

        window.updateLessonField = function(modIdx, lesIdx, field, val) {
            if (structure[modIdx] && structure[modIdx].lessons && structure[modIdx].lessons[lesIdx]) {
                structure[modIdx].lessons[lesIdx][field] = val;
                if (field === 'source' && val !== '') {
                    structure[modIdx].lessons[lesIdx].source_type = 'theme_file';
                }
                renderModules();
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
