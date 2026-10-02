<div class="wrap" style="padding: 20px;">
    <h1 style="display: flex; align-items: center; gap: 10px; color: #fff; margin-bottom: 20px;">
        <i class="fas fa-sitemap text-primary"></i> Gerenciador de Menus
    </h1>
    


    

    

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
        <!-- COLUNA ESQUERDA: PÁGINAS E LINKS -->
        <div>
            <div class="glass-panel" style="padding: 20px; margin-bottom: 20px;">
                <h3 style="margin-top:0; color:#fff; border-bottom:1px solid var(--border); padding-bottom:10px;">Adicionar Páginas</h3>
                <?php if(empty($pages)): ?>
                    <p style="color:var(--text-muted); font-size:13px;">Nenhuma página dinâmica encontrada.</p>
                <?php else: ?>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border); border-radius: 4px; padding: 10px; margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:12px; cursor:pointer; font-weight:bold; border-bottom:1px solid var(--border); padding-bottom:8px;">
                            <input type="checkbox" onchange="document.querySelectorAll('.page-checkbox').forEach(cb => cb.checked = this.checked)"> 
                            Selecionar todos os itens
                        </label>
                        <?php foreach($pages as $p): ?>
                            <label style="display:block; margin-bottom:8px; cursor:pointer;">
                                <input type="checkbox" class="page-checkbox" data-id="<?= $p['id'] ?>" data-title="<?= htmlspecialchars($p['title']) ?>" data-slug="<?= htmlspecialchars($p['slug']) ?>"> 
                                <?= htmlspecialchars($p['title']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline" style="width: 100%; text-align: center; padding: 8px;" onclick="addSelectedPages()">Adicionar ao Menu</button>
                <?php endif; ?>
            </div>

            <div class="glass-panel" style="padding: 20px;">
                <h3 style="margin-top:0; color:#fff; border-bottom:1px solid var(--border); padding-bottom:10px;">Link Personalizado</h3>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom:5px; color:var(--text-muted);">URL</label>
                    <input type="text" id="custom-link-url" placeholder="https://..." style="width: 100%;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; color:var(--text-muted);">Texto do Link</label>
                    <input type="text" id="custom-link-text" placeholder="Ex: Contato" style="width: 100%;">
                </div>
                <button type="button" class="btn btn-outline" style="width: 100%; text-align: center; padding: 8px;" onclick="addCustomLink()">Adicionar ao Menu</button>
            </div>
        </div>

        <!-- COLUNA DIREITA: ESTRUTURA DO MENU -->
        <div class="glass-panel" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
                <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
                    <span style="color:var(--text-muted);">Selecione um menu para editar:</span>
                    <select name="menu_id" onchange="this.form.submit()" style="min-width: 200px;">
                        <?php if (empty($menus)): ?>
                            <option value="">Nenhum menu criado</option>
                        <?php else: ?>
                            <?php foreach($menus as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $activeMenuId == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['location']) ?>)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    
                </form>
            </div>

            <form id="menu-settings-form" onsubmit="saveMenuSettings(event, '<?= BASE_URL ?>/admin/themes/menus/store')">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="id" value="<?= $activeMenu['id'] ?? '' ?>">
<input type="hidden" name="location" value="<?= $activeMenu['location'] ?? '' ?>">
                
                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom:5px;">Nome do Menu</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($activeMenu['name'] ?? '') ?>" required style="width: 100%;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display:block; margin-bottom:5px;">Posição do Tema (Location)</label>
                        <select name="location" disabled style="width: 100%; background: var(--bg-deep); opacity: 0.7; cursor: not-allowed;">
                            <option value="header" <?= ($activeMenu['location'] ?? '') == 'header' ? 'selected' : '' ?>>Main Header (Cabeçalho)</option>
                            <option value="footer" <?= ($activeMenu['location'] ?? '') == 'footer' ? 'selected' : '' ?>>Footer (Rodapé)</option>
                        </select>
                        <p style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">A posição deste menu de sistema é fixa.</p>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <button type="submit" class="btn btn-primary">Salvar Configurações do Menu</button>
                    <?php if ($activeMenu): ?>
                        <button type="button" class="btn" style="background:#dc3545; border-color:#dc3545;" onclick="deleteMenuData('<?= BASE_URL ?>/admin/themes/menus/delete/<?= $activeMenu['id'] ?>')">Excluir Menu</button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($activeMenu): ?>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 30px 0;">

            <h3 style="margin-top:0; color:#fff; margin-bottom: 20px;">Estrutura do Menu</h3>
            <p style="color:var(--text-muted); font-size:13px;">Arraste os itens para reordená-los.</p>

            <form method="POST" action="<?= BASE_URL ?>/admin/themes/menus/items" id="items-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="menu_id" value="<?= $activeMenu['id'] ?>">
                <input type="hidden" name="items" id="items-json" value="">

                <ul id="menu-items-list" style="list-style: none; padding: 0; min-height: 100px; border: 1px dashed rgba(69, 243, 255, 0.3); border-radius: 8px; padding: 10px;">
                    <?php foreach($menuItems as $item): ?>
                        <li class="menu-item" data-title="<?= htmlspecialchars($item['title']) ?>" data-url="<?= htmlspecialchars($item['url'] ?? '') ?>" data-page-id="<?= $item['page_id'] ?>" style="background: var(--bg-deep); border: 1px solid var(--border); padding: 15px; margin-bottom: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; cursor: grab;">
                            <div>
                                <strong style="color: #fff;"><?= htmlspecialchars($item['title']) ?></strong>
                                <span style="color: var(--text-muted); font-size: 12px; margin-left: 10px;">
                                    <?= $item['page_id'] ? 'Página Dinâmica' : 'Link Personalizado (' . htmlspecialchars($item['url']) . ')' ?>
                                </span>
                            </div>
                            
    <div>
        <button type="button" style="background:transparent; border:none; color:var(--primary); cursor:pointer; margin-right: 10px;" onclick="editItemTitle(this)">Editar</button>
        <button type="button" style="background:transparent; border:none; color:#dc3545; cursor:pointer;" onclick="this.closest('.menu-item').remove()">Excluir</button>
    </div>

                        </li>
                    <?php endforeach; ?>
                </ul>

                <button type="button" class="btn btn-primary" onclick="saveMenuItems('<?= BASE_URL ?>/admin/themes/menus/items')" style="margin-top: 20px; width: 100%; text-align: center;">Salvar Estrutura</button>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php if ($activeMenu): ?>

<?php endif; ?>

<!-- Script ultra leve para Drag and Drop e manipulação de DOM -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const list = document.getElementById('menu-items-list');
        if (list) {
            new Sortable(list, {
                animation: 150,
                ghostClass: 'sortable-ghost'
            });
        }
    });

    function addSelectedPages() {
        const checkboxes = document.querySelectorAll('.page-checkbox:checked');
        const list = document.getElementById('menu-items-list');
        if (!list) { alert('Você precisa criar ou selecionar um menu antes de adicionar itens.'); return; }
        
        checkboxes.forEach(cb => {
            const title = cb.getAttribute('data-title');
            const pageId = cb.getAttribute('data-id');
            const slug = cb.getAttribute('data-slug');
            
            const li = document.createElement('li');
            li.className = 'menu-item';
            li.dataset.title = title;
            li.dataset.pageId = pageId;
            li.dataset.url = pageId ? '' : slug;
            li.style = 'background: var(--bg-deep); border: 1px solid var(--border); padding: 15px; margin-bottom: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; cursor: grab;';
            const displayType = pageId ? "Página Dinâmica" : "Página Inicial (Home)";
            li.innerHTML = `
                <div>
                    <strong style="color: #fff;">${title}</strong>
                    <span style="color: var(--text-muted); font-size: 12px; margin-left: 10px;">${displayType}</span>
                </div>
                <div>
                    <button type="button" style="background:transparent; border:none; color:var(--primary); cursor:pointer; margin-right: 10px;" onclick="editItemTitle(this)">Editar</button>
                    <button type="button" style="background:transparent; border:none; color:#dc3545; cursor:pointer;" onclick="this.closest('.menu-item').remove()">Excluir</button>
                </div>
            `;
            list.appendChild(li);
            cb.checked = false; // reset
        });
        
        // Reset select-all se existir
        const selectAll = document.querySelector('input[onchange*="selectAll"]');
        if (selectAll) selectAll.checked = false;
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    }

    function addCustomLink() {
        const urlInput = document.getElementById('custom-link-url');
        const textInput = document.getElementById('custom-link-text');
        
        const url = urlInput.value.trim();
        const text = textInput.value.trim();
        
        if (!url || !text) {
            alert('Preencha a URL e o texto do link.');
            return;
        }

        const list = document.getElementById('menu-items-list');
        if (!list) { alert('Você precisa criar ou selecionar um menu antes de adicionar itens.'); return; }

        const li = document.createElement('li');
        li.className = 'menu-item';
        li.dataset.title = text;
        li.dataset.pageId = '';
        li.dataset.url = url;
        li.style = 'background: var(--bg-deep); border: 1px solid var(--border); padding: 15px; margin-bottom: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; cursor: grab;';
        li.innerHTML = `
                <div>
                    <strong style="color: #fff;">${title}</strong>
                    <span style="color: var(--text-muted); font-size: 12px; margin-left: 10px;">${displayType}</span>
                </div>
                <div>
                    <button type="button" style="background:transparent; border:none; color:var(--primary); cursor:pointer; margin-right: 10px;" onclick="editItemTitle(this)">Editar</button>
                    <button type="button" style="background:transparent; border:none; color:#dc3545; cursor:pointer;" onclick="this.closest('.menu-item').remove()">Excluir</button>
                </div>
            `;
        list.appendChild(li);
        
        urlInput.value = '';
        textInput.value = '';
    }

    async function saveMenuSettings(e, url) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const resData = await response.json();
            if (resData.success) {
                OS.notify(resData.message, 'success');
                if (!data.id) { // Foi criado um novo
                    setTimeout(() => window.location.href = '?menu_id=' + resData.id, 1000);
                }
            } else {
                OS.notify(resData.message, 'error');
            }
        } catch (err) {
            OS.notify('Erro ao salvar as configurações.', 'error');
        }
    }

    async function saveMenuItems(url) {
        const listItems = document.querySelectorAll('.menu-item');
        const items = [];
        listItems.forEach(li => {
            items.push({
                title: li.dataset.title,
                url: li.dataset.url,
                page_id: li.dataset.pageId
            });
        });

        const menuId = document.querySelector('input[name="menu_id"]').value;
        const csrf = document.querySelector('input[name="csrf_token"]').value;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ menu_id: menuId, items: items, csrf_token: csrf })
            });
            const resData = await response.json();
            if (resData.success) {
                OS.notify(resData.message, 'success');
            } else {
                OS.notify(resData.message, 'error');
            }
        } catch (err) {
            OS.notify('Erro ao salvar a estrutura do menu.', 'error');
        }
    }

    async function deleteMenuData(url) {
        if (!confirm('Tem certeza absoluta que deseja excluir este menu?')) return;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const resData = await response.json();
            if (resData.success) {
                OS.notify(resData.message, 'success');
                setTimeout(() => window.location.href = '?deleted=1', 1000);
            } else {
                OS.notify(resData.message, 'error');
            }
        } catch (err) {
            OS.notify('Erro ao excluir.', 'error');
        }
    }

    function editItemTitle(btn) {
        const li = btn.closest('.menu-item');
        const titleEl = li.querySelector('strong');
        const currentTitle = li.dataset.title;
        const newTitle = prompt('Digite o novo texto para o link:', currentTitle);
        if (newTitle && newTitle.trim() !== '') {
            li.dataset.title = newTitle.trim();
            titleEl.innerText = newTitle.trim();
        }
    }
</script>
<style>
    .sortable-ghost { opacity: 0.4; background: rgba(69, 243, 255, 0.1) !important; border: 1px dashed var(--primary) !important; }
</style>
