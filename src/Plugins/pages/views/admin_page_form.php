<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <a href="<?= BASE_URL ?>/admin/pages" style="text-decoration: none; font-size: 20px;">⬅️</a>
        <?= $page ? 'Editar Página' : 'Criar Nova Página' ?>
    </h1>

    <div style="display: flex; gap: 20px;">
        <!-- COLUNA PRINCIPAL -->
        <div style="flex: 1;">
            <form action="<?= BASE_URL ?>/admin/pages/store" method="POST" id="page_form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <?php if($page): ?>
                    <input type="hidden" name="id" value="<?= $page['id'] ?>">
                <?php endif; ?>
                
                <!-- TÍTULO -->
                <div style="background: var(--bg-panel, #fff); padding: 20px; border-radius: 8px; border: 1px solid var(--border, #e2e8f0); margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Título da Página</label>
                    <input type="text" name="title" id="title_input" value="<?= $page ? htmlspecialchars($page['title']) : '' ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 16px; background: var(--bg-input, #fff); color: var(--text-main, #1d2327);" required>
                    
                                        <!-- SLUG FIELD -->
                    <div style="margin-top: 8px; font-size: 13px; color: var(--text-muted, #64748b); display: flex; align-items: center; gap: 5px;">
                        <span>🔗 Permalink: </span>
                        <code style="background: var(--bg-surface, #f1f5f9); padding: 2px 8px; border-radius: 3px; display: flex; align-items: center; gap: 2px;">
                            <?= rtrim(BASE_URL, '/') ?>/
                            <input type="text" name="slug" id="slug_input" value="<?= $page ? htmlspecialchars($page['slug']) : '' ?>" placeholder="gerado-automaticamente" style="background: transparent; border: none; border-bottom: 1px dashed #cbd5e1; color: var(--text-main); font-family: monospace; outline: none; width: 150px;">
                        </code>
                        <?php if($page): ?>
                            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($page['slug']) ?>" target="_blank" style="margin-left: 8px; font-size: 12px;">↗ Visualizar</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TEMPLATE DO TEMA -->
                <div style="background: var(--bg-panel, #fff); padding: 20px; border-radius: 8px; border: 1px solid var(--border, #e2e8f0); margin-bottom: 15px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted, #64748b);">📄 Template do Tema</h3>
                    
                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px;">Tema</label>
                            <select name="theme" id="theme_select" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; background: var(--bg-input, #fff); color: var(--text-main, #1d2327);">
                                <option value="">-- Sem Template (Usar Editor) --</option>
                                <?php
                                if (isset($available_themes)) {
                                    foreach ($available_themes as $t) {
                                        $themeFolder = $t['folder'];
                                        $themeName = $t['name'];
                                        $sel = ($page && ($page['theme'] ?? '') === $themeFolder) ? 'selected' : '';
                                        echo "<option value=\"$themeFolder\" $sel>$themeName</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div style="flex: 2;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px;">Arquivo PHP (da pasta <code>templates/</code>)</label>
                            <select name="template_file" id="template_select" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; background: var(--bg-input, #fff); color: var(--text-main, #1d2327);">
                                <option value="">-- Selecione um Tema Primeiro --</option>
                            </select>
                        </div>
                    </div>

                    <div id="template_info" style="display: none; margin-top: 12px; padding: 10px 15px; background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.2); border-radius: 6px; font-size: 13px; color: var(--text-main, #1d2327);">
                        ✅ <strong>Modo Template:</strong> Esta página será renderizada pelo arquivo PHP selecionado. O editor de conteúdo abaixo será ignorado.
                    </div>
                    <div id="content_info" style="margin-top: 12px; padding: 10px 15px; background: rgba(88, 166, 255, 0.08); border: 1px solid rgba(88, 166, 255, 0.2); border-radius: 6px; font-size: 13px; color: var(--text-main, #1d2327);">
                        ✏️ <strong>Modo Editor:</strong> O conteúdo será renderizado diretamente pelo editor abaixo (suporta HTML e Shortcodes).
                    </div>
                </div>

                <!-- CONTEÚDO (EDITOR) -->
                <div style="background: var(--bg-panel, #fff); padding: 20px; border-radius: 8px; border: 1px solid var(--border, #e2e8f0); margin-bottom: 15px; transition: opacity 0.3s;" id="content_container">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">
                        Conteúdo (Editor Raw / Shortcodes)
                    </label>
                    <textarea name="content" rows="15" style="width: 100%; padding: 15px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: monospace; font-size: 14px; background: var(--bg-input, #f8fafc); color: var(--text-main, #1d2327); line-height: 1.5; resize: vertical;"><?= $page ? htmlspecialchars($page['content']) : '' ?></textarea>
                </div>
            </form>
        </div>

        <!-- SIDEBAR DIREITA (PUBLICAÇÃO) -->
        <div style="width: 280px; flex-shrink: 0;">
            <!-- BOX PUBLICAR -->
            <div style="background: var(--bg-panel, #fff); padding: 20px; border-radius: 8px; border: 1px solid var(--border, #e2e8f0); margin-bottom: 15px;">
                <h3 style="margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted, #64748b); border-bottom: 1px solid var(--border, #e2e8f0); padding-bottom: 10px;">🚀 Publicar</h3>
                
                <div style="margin-bottom: 12px; font-size: 13px; color: var(--text-muted, #64748b);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Status:</span>
                        <strong style="color: <?= $page ? '#00a32a' : '#d97706' ?>">
                            <?= $page ? '✅ Publicada' : '📝 Rascunho' ?>
                        </strong>
                    </div>
                    <?php if($page && !empty($page['created_at'])): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Criada em:</span>
                        <span><?= date('d/m/Y H:i', strtotime($page['created_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Slug:</span>
                        <code style="font-size: 12px;" id="slug_sidebar"><?= $page ? htmlspecialchars($page['slug']) : 'auto' ?></code>
                    </div>
                </div>

                <hr style="border: 0; height: 1px; background: var(--border, #e2e8f0); margin: 15px 0;">
                
                <button type="submit" form="page_form" id="publish_btn" style="width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; transition: background 0.2s;">
                    <?= $page ? '💾 Atualizar Página' : '🚀 Publicar Página' ?>
                </button>
                
                <?php if($page): ?>
                <div style="margin-top: 10px; text-align: center;">
                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($page['slug']) ?>" target="_blank" style="font-size: 13px; color: var(--accent-blue, #2563eb);">
                        ↗ Visualizar no Site
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- BOX RESUMO -->
            <div style="background: var(--bg-panel, #fff); padding: 20px; border-radius: 8px; border: 1px solid var(--border, #e2e8f0);">
                <h3 style="margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted, #64748b); border-bottom: 1px solid var(--border, #e2e8f0); padding-bottom: 10px;">ℹ️ Informações</h3>
                <div style="font-size: 13px; color: var(--text-muted, #64748b); line-height: 1.6;">
                    <p style="margin: 0 0 8px 0;"><strong>Template:</strong> Conecte um arquivo <code>.php</code> da pasta <code>templates/</code> do tema selecionado.</p>
                    <p style="margin: 0 0 8px 0;"><strong>Editor:</strong> Se não selecionar template, o conteúdo do editor será usado diretamente.</p>
                    <p style="margin: 0;"><strong>Menu:</strong> A gestão de menus será feita pelo módulo Builder-Flex.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.getElementById('theme_select');
    const templateSelect = document.getElementById('template_select');
    const contentContainer = document.getElementById('content_container');
    const templateInfo = document.getElementById('template_info');
    const contentInfo = document.getElementById('content_info');
    const titleInput = document.getElementById('title_input');
    const slugPreview = document.getElementById('slug_preview');
    const slugSidebar = document.getElementById('slug_sidebar');
    const publishBtn = document.getElementById('publish_btn');
    const savedTemplate = '<?= $page["template_file"] ?? "" ?>';
    const isEditing = <?= $page ? 'true' : 'false' ?>;

    // Gera slug a partir do título (apenas para páginas novas)
    function generateSlug(text) {
        return text.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    titleInput.addEventListener('input', function() {
        if (!isEditing) {
            const slug = generateSlug(this.value) || '...';
            slugPreview.textContent = slug;
            slugSidebar.textContent = slug;
        }
    });

    // Confirmação de publicação
    document.getElementById('page_form').addEventListener('submit', function(e) {
        const title = titleInput.value.trim();
        if (!title) {
            e.preventDefault();
            OS.notify('O título é obrigatório.', 'error');
            return;
        }
        
        const action = isEditing ? 'atualizar' : 'publicar';
        const usingTemplate = themeSelect.value && templateSelect.value;
        
        let msg = `Deseja ${action} a página "${title}"?\n\n`;
        msg += usingTemplate 
            ? `📄 Modo: Template PHP\n📁 Arquivo: ${templateSelect.value}` 
            : `✏️ Modo: Conteúdo do Editor`;
        msg += `\n🔗 Slug: /${slugPreview.textContent}`;
        
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });

    // Carrega templates via AJAX
    function loadTemplates() {
        const theme = themeSelect.value;
        templateSelect.innerHTML = '<option value="">Carregando...</option>';
        
        if (!theme) {
            templateSelect.innerHTML = '<option value="">-- Selecione um Tema Primeiro --</option>';
            updateMode();
            return;
        }

        fetch('<?= BASE_URL ?>/admin/pages/api/theme-files?theme=' + encodeURIComponent(theme), {
                credentials: 'same-origin'
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(files => {
                if (files.templates.length === 0 && files.pages.length === 0) {
                    templateSelect.innerHTML = '<option value="">Nenhum template ou página encontrado</option>';
                } else {
                    templateSelect.innerHTML = '<option value="">-- Escolha um arquivo --</option>';
                    
                    if (files.templates.length > 0) {
                        const optgroupTpl = document.createElement('optgroup');
                        optgroupTpl.label = 'Templates Dinâmicos (templates/)';
                        files.templates.forEach(file => {
                            const opt = document.createElement('option');
                            opt.value = 'templates/' + file; // add prefix back for logic
                            opt.textContent = '📄 ' + file;
                            if (('templates/' + file) === savedTemplate || file === savedTemplate) opt.selected = true;
                            optgroupTpl.appendChild(opt);
                        });
                        templateSelect.appendChild(optgroupTpl);
                    }

                    if (files.pages.length > 0) {
                        const optgroupPg = document.createElement('optgroup');
                        optgroupPg.label = 'Páginas Estáticas (pages/)';
                        files.pages.forEach(file => {
                            const opt = document.createElement('option');
                            opt.value = file; // already has pages/ prefix
                            opt.textContent = '🔗 ' + file.replace('pages/', '');
                            if (file === savedTemplate) opt.selected = true;
                            optgroupPg.appendChild(opt);
                        });
                        templateSelect.appendChild(optgroupPg);
                    }
                }
                updateMode();
            })
            .catch(err => {
                console.error('Erro ao carregar arquivos do tema:', err);
                templateSelect.innerHTML = '<option value="">❌ Erro ao carregar</option>';
            });
    }

    // Alterna visual entre Modo Template/Página e Modo Editor
    function updateMode() {
        const selectedValue = templateSelect.value;
        const usingTemplate = themeSelect.value && selectedValue !== '';
        
        if (usingTemplate) {
            contentContainer.style.opacity = '0.4';
            contentContainer.style.pointerEvents = 'none';
            contentInfo.style.display = 'none';
            templateInfo.style.display = 'block';
            
            if (selectedValue.startsWith('pages/')) {
                templateInfo.innerHTML = '✅ <strong>Modo Arquivo Raw:</strong> Esta rota apontará diretamente para o arquivo estático selecionado. O banco de dados não injetará conteúdo.';
                templateInfo.style.background = 'rgba(234, 88, 12, 0.08)'; // orange theme
                templateInfo.style.borderColor = 'rgba(234, 88, 12, 0.2)';
            } else {
                templateInfo.innerHTML = '✅ <strong>Modo Template:</strong> Esta página injetará o conteúdo do banco de dados dentro do template PHP selecionado.';
                templateInfo.style.background = 'rgba(0, 210, 132, 0.08)'; // green theme
                templateInfo.style.borderColor = 'rgba(0, 210, 132, 0.2)';
            }
        } else {
            contentContainer.style.opacity = '1';
            contentContainer.style.pointerEvents = 'auto';
            templateInfo.style.display = 'none';
            contentInfo.style.display = 'block';
        }
    }

    themeSelect.addEventListener('change', loadTemplates);
    templateSelect.addEventListener('change', updateMode);

    // Carrega templates se já existir um tema salvo
    if (themeSelect.value) {
        loadTemplates();
    }
});
</script>
