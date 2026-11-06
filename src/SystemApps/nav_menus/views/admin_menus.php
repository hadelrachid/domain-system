<div class="wrap" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <h1 style="display: flex; align-items: center; gap: 10px; color: #fff; margin: 0;">
            <i class="fas fa-sitemap text-primary"></i> Gerenciador de Menus &amp; Submenus
        </h1>
        <div style="font-size: 12px; color: var(--text-muted); background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); padding: 6px 12px; border-radius: 6px;">
            💡 <strong>Estilo WordPress (wpNavMenu):</strong> Arraste os itens para reordenar ou <strong>arraste para a direita (➡️)</strong> para criar <strong>Submenus</strong>!
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start;">
        <!-- COLUNA ESQUERDA: PÁGINAS E LINKS PERSONALIZADOS -->
        <div>
            <div class="glass-panel" style="padding: 20px; margin-bottom: 20px;">
                <h3 style="margin-top:0; color:#fff; border-bottom:1px solid var(--border); padding-bottom:10px; font-size: 15px;">📄 Adicionar Páginas</h3>
                <?php if (empty($pages)): ?>
                    <p style="color:var(--text-muted); font-size:13px;">Nenhuma página dinâmica encontrada.</p>
                <?php else: ?>
                    <div style="max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 6px; padding: 10px; margin-bottom: 15px; background: rgba(0,0,0,0.2);">
                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:12px; cursor:pointer; font-weight:bold; border-bottom:1px solid var(--border); padding-bottom:8px; color:#fff; font-size:13px;">
                            <input type="checkbox" id="select-all-pages-cb" onchange="document.querySelectorAll('.page-checkbox').forEach(cb => cb.checked = this.checked)"> 
                            Selecionar todos os itens
                        </label>
                        <?php foreach ($pages as $p): ?>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; cursor:pointer; font-size:13px; color:#cbd5e1;">
                                <input type="checkbox" class="page-checkbox" data-id="<?= htmlspecialchars((string)($p['id'] ?? '')) ?>" data-title="<?= htmlspecialchars((string)$p['title']) ?>" data-slug="<?= htmlspecialchars((string)$p['slug']) ?>"> 
                                <span><?= htmlspecialchars((string)$p['title']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline" style="width: 100%; text-align: center; padding: 9px; font-weight: 600;" onclick="addSelectedPages()">+ Adicionar ao Menu</button>
                <?php endif; ?>
            </div>

            <div class="glass-panel" style="padding: 20px;">
                <h3 style="margin-top:0; color:#fff; border-bottom:1px solid var(--border); padding-bottom:10px; font-size: 15px;">🔗 Link Personalizado / Submenu</h3>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; margin-bottom:5px; color:var(--text-muted); font-size: 12px;">URL (use <code>#</code> para título de Submenu)</label>
                    <input type="text" id="custom-link-url" placeholder="https://... ou #" value="#" style="width: 100%;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display:block; margin-bottom:5px; color:var(--text-muted); font-size: 12px;">Texto do Link (Rótulo)</label>
                    <input type="text" id="custom-link-text" placeholder="Ex: Especialidades" style="width: 100%;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; color:var(--text-muted); font-size: 12px;">Adicionar como:</label>
                    <select id="custom-link-as-sub" style="width: 100%;">
                        <option value="0">Item Principal (Nível 0)</option>
                        <option value="1">↳ Submenu do Último Item (Nível 1)</option>
                        <option value="2">↳↳ Sub-submenu (Nível 2)</option>
                    </select>
                </div>
                <button type="button" class="btn btn-outline" style="width: 100%; text-align: center; padding: 9px; font-weight: 600;" onclick="addCustomLink()">+ Adicionar ao Menu</button>
            </div>
        </div>

        <!-- COLUNA DIREITA: ESTRUTURA HIERÁRQUICA DO MENU (WP NAV MENU) -->
        <div class="glass-panel" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 10px;">
                <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
                    <span style="color:var(--text-muted); font-size: 13px;">Selecione um menu para editar:</span>
                    <select name="menu_id" onchange="this.form.submit()" style="min-width: 220px;">
                        <?php if (empty($menus)): ?>
                            <option value="">Nenhum menu criado</option>
                        <?php else: ?>
                            <?php foreach ($menus as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= $activeMenuId == $m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$m['name']) ?> (<?= htmlspecialchars((string)$m['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>

            <form id="menu-settings-form" onsubmit="saveMenuSettings(event, '<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/themes/menus/store')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($activeMenu['id'] ?? '')) ?>">
                <input type="hidden" name="location" value="<?= htmlspecialchars((string)($activeMenu['location'] ?? '')) ?>">

                <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display:block; margin-bottom:5px; font-size:13px;">Nome do Menu</label>
                        <input type="text" name="name" value="<?= htmlspecialchars((string)($activeMenu['name'] ?? '')) ?>" required style="width: 100%;">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display:block; margin-bottom:5px; font-size:13px;">Posição do Tema (Location)</label>
                        <select name="location_display" disabled style="width: 100%; background: var(--bg-deep); opacity: 0.7; cursor: not-allowed;">
                            <option value="header" <?= ($activeMenu['location'] ?? '') === 'header' ? 'selected' : '' ?>>Main Header (Cabeçalho)</option>
                            <option value="footer" <?= ($activeMenu['location'] ?? '') === 'footer' ? 'selected' : '' ?>>Footer (Rodapé)</option>
                        </select>
                        <p style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">Vinculado automaticamente aos blocos de Navegação do Flex Builder.</p>
                    </div>
                </div>

                <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Salvar Configurações do Menu</button>
                    <?php if ($activeMenu): ?>
                        <button type="button" class="btn" style="background:#dc3545; border-color:#dc3545; color:#fff;" onclick="deleteMenuData(event, '<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/themes/menus/delete/<?= (int)$activeMenu['id'] ?>')">Excluir Menu</button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($activeMenu): ?>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h3 style="margin: 0 0 4px 0; color:#fff;">Estrutura Hierárquica do Menu (Menus &amp; Submenus)</h3>
                    <p style="color:var(--text-muted); font-size:12px; margin:0;">
                        Arraste verticalmente para ordenar. <strong>Arraste para a direita/esquerda</strong> (ou use as setas <code>⬅️</code> <code>➡️</code>) para transformar em <strong>Submenu</strong>.
                    </p>
                </div>
            </div>

            <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/themes/menus/items" id="items-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
                <input type="hidden" name="menu_id" value="<?= (int)$activeMenu['id'] ?>">
                <input type="hidden" name="items" id="items-json" value="">

                <ul id="menu-items-list" class="wp-nav-menu-list" style="list-style: none; min-height: 120px; border: 1px dashed rgba(69, 243, 255, 0.3); border-radius: 8px; padding: 14px; margin: 0;">
                    <?php
                    // Calcula depth se vier apenas parent_id do banco
                    $depthMap = [];
                    foreach ($menuItems as $idx => $item):
                        $itemId = (int)($item['id'] ?? ($idx + 1));
                        $parentId = !empty($item['parent_id']) ? (int)$item['parent_id'] : 0;
                        $depth = isset($item['depth']) ? (int)$item['depth'] : 0;
                        if ($depth === 0 && $parentId > 0 && isset($depthMap[$parentId])) {
                            $depth = min(5, $depthMap[$parentId] + 1);
                        }
                        $depthMap[$itemId] = $depth;
                        $target = (string)($item['target'] ?? '_self');
                        $cssClasses = (string)($item['css_classes'] ?? '');
                        $typeLabel = !empty($item['page_id']) ? 'Página Dinâmica' : 'Link Personalizado';
                    ?>
                        <li class="menu-item menu-item-depth-<?= $depth ?>"
                            data-item-id="item_<?= $itemId ?>"
                            data-parent-id="<?= $parentId ?>"
                            data-depth="<?= $depth ?>"
                            data-title="<?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?>"
                            data-url="<?= htmlspecialchars((string)($item['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-page-id="<?= htmlspecialchars((string)($item['page_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-target="<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>"
                            data-css-classes="<?= htmlspecialchars($cssClasses, ENT_QUOTES, 'UTF-8') ?>"
                            style="margin-left: <?= $depth * 32 ?>px;">
                            
                            <div class="menu-item-bar">
                                <div class="menu-item-handle">
                                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                        <span class="drag-grip" title="Arraste para ordenar ou arraste para a direita para criar Submenu">⠿</span>
                                        <strong class="item-title-display" style="color: #fff; font-size: 14px;"><?= htmlspecialchars((string)$item['title']) ?></strong>
                                        <span class="is-submenu-badge" style="<?= $depth > 0 ? '' : 'display:none;' ?>">
                                            ↳ Submenu <?= $depth > 1 ? '(Nível ' . $depth . ')' : '' ?>
                                        </span>
                                        <span class="item-parent-hint" style="font-size: 11px; color: var(--text-muted);"></span>
                                    </div>
                                    <div class="menu-item-actions">
                                        <span class="item-type-label"><?= $typeLabel ?></span>
                                        <button type="button" class="indent-btn" onclick="shiftItemDepth(this, -1)" title="Recuar para a esquerda (Promover nível)">⬅️</button>
                                        <button type="button" class="indent-btn" onclick="shiftItemDepth(this, 1)" title="Avançar para a direita (Tornar Submenu do item acima)">➡️</button>
                                        <button type="button" class="edit-toggle-btn" onclick="toggleItemSettings(this)">▼ Editar</button>
                                        <button type="button" class="delete-item-btn" onclick="removeMenuItem(this)" title="Remover item">✕</button>
                                    </div>
                                </div>
                            </div>

                            <!-- GAVETA DE CONFIGURAÇÕES DO ITEM (ESTILO WORDPRESS WALKER_NAV_MENU_EDIT) -->
                            <div class="menu-item-settings" style="display: none;">
                                <div class="settings-grid">
                                    <div>
                                        <label>Rótulo de Navegação (Título)</label>
                                        <input type="text" class="edit-menu-item-title" value="<?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?>" oninput="syncItemField(this, 'title')">
                                    </div>
                                    <div>
                                        <label>URL / Link</label>
                                        <input type="text" class="edit-menu-item-url" value="<?= htmlspecialchars((string)($item['url'] ?? '/'), ENT_QUOTES, 'UTF-8') ?>" oninput="syncItemField(this, 'url')" <?= !empty($item['page_id']) ? 'placeholder="Automático pela Página"' : '' ?>>
                                    </div>
                                    <div>
                                        <label>Item Pai (Hierarquia)</label>
                                        <select class="edit-menu-item-parent" onchange="onParentDropdownChange(this)">
                                            <option value="">— Item Principal (Raiz) —</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Destino ao Clicar (Target)</label>
                                        <select class="edit-menu-item-target" onchange="syncItemField(this, 'target')">
                                            <option value="_self" <?= $target === '_self' ? 'selected' : '' ?>>Mesma Janela (_self)</option>
                                            <option value="_blank" <?= $target === '_blank' ? 'selected' : '' ?>>Nova Aba (_blank)</option>
                                        </select>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <label>Classes CSS Opcionais</label>
                                        <input type="text" class="edit-menu-item-classes" value="<?= htmlspecialchars($cssClasses, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: destaque-menu btn-nav" oninput="syncItemField(this, 'cssClasses')">
                                    </div>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.06); font-size:11px; color:var(--text-muted);">
                                    <span>Dica: Você também pode usar os botões ⬅️ e ➡️ ou arrastar o bloco para a direita para aninhar.</span>
                                    <button type="button" style="background:transparent; border:none; color:#ef4444; cursor:pointer; font-weight:600;" onclick="removeMenuItem(this)">🗑️ Remover Este Item</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <button type="button" class="btn btn-primary" onclick="saveMenuItems('<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/themes/menus/items')" style="margin-top: 20px; width: 100%; text-align: center; padding: 12px; font-weight: 700;">
                    💾 Salvar Estrutura de Menus e Submenus
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    const MENU_DEPTH_PX = 32;
    const MAX_MENU_DEPTH = 5;
    let dragStartX = 0;
    let dragStartDepth = 0;
    let draggedChildren = [];
    let itemCounter = 1000;

    document.addEventListener('DOMContentLoaded', function() {
        const list = document.getElementById('menu-items-list');
        if (!list) return;

        refreshHierarchyMetadata();

        new Sortable(list, {
            animation: 150,
            handle: '.menu-item-bar',
            ghostClass: 'sortable-ghost',
            onStart: function(evt) {
                const item = evt.item;
                dragStartX = (evt.originalEvent && evt.originalEvent.clientX) ? evt.originalEvent.clientX : 0;
                dragStartDepth = getItemDepth(item);
                // Coleta os sub-itens filhos para movê-los junto com o pai (igual ao childMenuItems do wpNavMenu)
                draggedChildren = getChildMenuItems(item);
                draggedChildren.forEach(ch => ch.classList.add('dragging-child- companion'));
            },
            onEnd: function(evt) {
                const item = evt.item;
                const endX = (evt.originalEvent && evt.originalEvent.clientX) ? evt.originalEvent.clientX : dragStartX;
                const deltaX = endX - dragStartX;
                const depthDelta = Math.round(deltaX / MENU_DEPTH_PX);

                // Reposiciona os filhos imediatamente abaixo do item pai na nova posição
                if (draggedChildren.length > 0) {
                    let refNode = item;
                    draggedChildren.forEach(ch => {
                        ch.classList.remove('dragging-child-companion');
                        refNode.after(ch);
                        refNode = ch;
                    });
                }

                if (depthDelta !== 0) {
                    setItemAndChildrenDepth(item, dragStartDepth + depthDelta);
                } else {
                    refreshHierarchyMetadata();
                }

                draggedChildren = [];
            }
        });
    });

    function getItemDepth(li) {
        return Math.max(0, parseInt(li.dataset.depth || '0', 10) || 0);
    }

    function setItemDepth(li, newDepth) {
        const clamped = Math.max(0, Math.min(MAX_MENU_DEPTH, newDepth));
        const prev = getItemDepth(li);
        li.classList.remove('menu-item-depth-' + prev);
        li.classList.add('menu-item-depth-' + clamped);
        li.dataset.depth = String(clamped);
        li.style.marginLeft = (clamped * MENU_DEPTH_PX) + 'px';
    }

    function getChildMenuItems(parentLi) {
        const children = [];
        const baseDepth = getItemDepth(parentLi);
        let next = parentLi.nextElementSibling;
        while (next && next.classList.contains('menu-item')) {
            if (getItemDepth(next) > baseDepth) {
                children.push(next);
                next = next.nextElementSibling;
            } else {
                break;
            }
        }
        return children;
    }

    function setItemAndChildrenDepth(li, targetDepth) {
        const curDepth = getItemDepth(li);
        const children = getChildMenuItems(li);
        const maxAllowed = getMaxAllowedDepthForItem(li);
        const finalDepth = Math.max(0, Math.min(maxAllowed, targetDepth));
        const diff = finalDepth - curDepth;

        setItemDepth(li, finalDepth);
        children.forEach(ch => {
            setItemDepth(ch, getItemDepth(ch) + diff);
        });

        refreshHierarchyMetadata();
    }

    function getMaxAllowedDepthForItem(li) {
        const prev = li.previousElementSibling;
        if (!prev || !prev.classList.contains('menu-item')) {
            return 0; // O primeiro item da lista sempre deve ser nível 0 (Raiz)
        }
        return Math.min(MAX_MENU_DEPTH, getItemDepth(prev) + 1);
    }

    function shiftItemDepth(btn, delta) {
        const li = btn.closest('.menu-item');
        if (!li) return;
        const cur = getItemDepth(li);
        setItemAndChildrenDepth(li, cur + delta);
    }

    function refreshHierarchyMetadata() {
        const items = Array.from(document.querySelectorAll('#menu-items-list .menu-item'));
        const lastItemByDepth = {};

        items.forEach((li, index) => {
            if (!li.dataset.itemId) {
                li.dataset.itemId = 'item_new_' + (++itemCounter);
            }

            let depth = getItemDepth(li);
            if (index === 0) {
                depth = 0;
            } else {
                const prevDepth = getItemDepth(items[index - 1]);
                if (depth > prevDepth + 1) {
                    depth = prevDepth + 1;
                }
            }
            setItemDepth(li, depth);

            const badge = li.querySelector('.is-submenu-badge');
            const hint = li.querySelector('.item-parent-hint');

            if (depth > 0 && lastItemByDepth[depth - 1]) {
                const parentLi = lastItemByDepth[depth - 1];
                li.dataset.parentId = parentLi.dataset.itemId;
                if (badge) {
                    badge.style.display = 'inline-block';
                    badge.textContent = '↳ Submenu' + (depth > 1 ? ' (Nível ' + depth + ')' : '');
                }
                if (hint) {
                    hint.textContent = 'de "' + (parentLi.dataset.title || 'Item') + '"';
                }
            } else {
                li.dataset.parentId = '0';
                if (badge) badge.style.display = 'none';
                if (hint) hint.textContent = '';
            }

            lastItemByDepth[depth] = li;
        });

        updateParentDropdowns(items);
    }

    function updateParentDropdowns(items) {
        items.forEach((li, idx) => {
            const select = li.querySelector('.edit-menu-item-parent');
            if (!select) return;
            const myId = li.dataset.itemId;
            const myParentId = li.dataset.parentId || '0';
            const myChildrenIds = new Set(getChildMenuItems(li).map(c => c.dataset.itemId));

            select.innerHTML = '<option value="0">— Item Principal (Sem Pai) —</option>';
            items.forEach(candidate => {
                const cId = candidate.dataset.itemId;
                if (cId === myId || myChildrenIds.has(cId)) return;
                const cDepth = getItemDepth(candidate);
                const prefix = '— '.repeat(cDepth);
                const opt = document.createElement('option');
                opt.value = cId;
                opt.textContent = prefix + (candidate.dataset.title || 'Item');
                if (cId === myParentId) opt.selected = true;
                select.appendChild(opt);
            });
        });
    }

    function onParentDropdownChange(selectEl) {
        const li = selectEl.closest('.menu-item');
        if (!li) return;
        const targetParentId = selectEl.value;
        const list = document.getElementById('menu-items-list');
        const children = getChildMenuItems(li);

        if (!targetParentId || targetParentId === '0') {
            setItemAndChildrenDepth(li, 0);
            return;
        }

        const parentLi = list.querySelector(`.menu-item[data-item-id="${targetParentId}"]`);
        if (!parentLi) return;

        // Move o item (e seus filhos) para logo abaixo da subárvore do novo pai
        const parentSubtree = getChildMenuItems(parentLi);
        const insertAfterNode = parentSubtree.length > 0 ? parentSubtree[parentSubtree.length - 1] : parentLi;
        insertAfterNode.after(li);
        let ref = li;
        children.forEach(ch => {
            ref.after(ch);
            ref = ch;
        });

        setItemAndChildrenDepth(li, getItemDepth(parentLi) + 1);
    }

    function toggleItemSettings(btn) {
        const li = btn.closest('.menu-item');
        if (!li) return;
        const panel = li.querySelector('.menu-item-settings');
        if (!panel) return;
        const isOpen = panel.style.display !== 'none';
        panel.style.display = isOpen ? 'none' : 'block';
        btn.textContent = isOpen ? '▼ Editar' : '▲ Fechar';
    }

    function syncItemField(inputEl, fieldKey) {
        const li = inputEl.closest('.menu-item');
        if (!li) return;
        const val = inputEl.value;
        li.dataset[fieldKey] = val;
        if (fieldKey === 'title') {
            const titleStrong = li.querySelector('.item-title-display');
            if (titleStrong) titleStrong.textContent = val || 'Sem título';
            refreshHierarchyMetadata();
        }
    }

    function removeMenuItem(btn) {
        const li = btn.closest('.menu-item');
        if (!li) return;
        // Promove filhos diretos um nível acima antes de remover o pai (comportamento WordPress)
        const children = getChildMenuItems(li);
        children.forEach(ch => {
            setItemDepth(ch, Math.max(0, getItemDepth(ch) - 1));
        });
        li.remove();
        refreshHierarchyMetadata();
    }

    function buildMenuItemElement(data) {
        const id = 'item_new_' + (++itemCounter);
        const depth = Math.max(0, parseInt(data.depth || 0, 10));
        const title = data.title || 'Novo Item';
        const url = data.url || '/';
        const pageId = data.pageId || '';
        const target = data.target || '_self';
        const cssClasses = data.cssClasses || '';
        const typeLabel = pageId ? 'Página Dinâmica' : 'Link Personalizado';

        const li = document.createElement('li');
        li.className = 'menu-item menu-item-depth-' + depth;
        li.dataset.itemId = id;
        li.dataset.parentId = '0';
        li.dataset.depth = String(depth);
        li.dataset.title = title;
        li.dataset.url = url;
        li.dataset.pageId = pageId;
        li.dataset.target = target;
        li.dataset.cssClasses = cssClasses;
        li.style.marginLeft = (depth * MENU_DEPTH_PX) + 'px';

        li.innerHTML = `
            <div class="menu-item-bar">
                <div class="menu-item-handle">
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="drag-grip" title="Arraste para ordenar ou arraste para a direita para criar Submenu">⠿</span>
                        <strong class="item-title-display" style="color: #fff; font-size: 14px;"></strong>
                        <span class="is-submenu-badge" style="display:none;">↳ Submenu</span>
                        <span class="item-parent-hint" style="font-size: 11px; color: var(--text-muted);"></span>
                    </div>
                    <div class="menu-item-actions">
                        <span class="item-type-label">${typeLabel}</span>
                        <button type="button" class="indent-btn" onclick="shiftItemDepth(this, -1)" title="Recuar para a esquerda (Promover nível)">⬅️</button>
                        <button type="button" class="indent-btn" onclick="shiftItemDepth(this, 1)" title="Avançar para a direita (Tornar Submenu do item acima)">➡️</button>
                        <button type="button" class="edit-toggle-btn" onclick="toggleItemSettings(this)">▼ Editar</button>
                        <button type="button" class="delete-item-btn" onclick="removeMenuItem(this)" title="Remover item">✕</button>
                    </div>
                </div>
            </div>
            <div class="menu-item-settings" style="display: none;">
                <div class="settings-grid">
                    <div>
                        <label>Rótulo de Navegação (Título)</label>
                        <input type="text" class="edit-menu-item-title" oninput="syncItemField(this, 'title')">
                    </div>
                    <div>
                        <label>URL / Link</label>
                        <input type="text" class="edit-menu-item-url" oninput="syncItemField(this, 'url')">
                    </div>
                    <div>
                        <label>Item Pai (Hierarquia)</label>
                        <select class="edit-menu-item-parent" onchange="onParentDropdownChange(this)">
                            <option value="0">— Item Principal (Raiz) —</option>
                        </select>
                    </div>
                    <div>
                        <label>Destino ao Clicar (Target)</label>
                        <select class="edit-menu-item-target" onchange="syncItemField(this, 'target')">
                            <option value="_self">Mesma Janela (_self)</option>
                            <option value="_blank">Nova Aba (_blank)</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label>Classes CSS Opcionais</label>
                        <input type="text" class="edit-menu-item-classes" placeholder="Ex: destaque-menu btn-nav" oninput="syncItemField(this, 'cssClasses')">
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.06); font-size:11px; color:var(--text-muted);">
                    <span>Dica: Use ⬅️ e ➡️ ou arraste o bloco para a direita para criar submenus.</span>
                    <button type="button" style="background:transparent; border:none; color:#ef4444; cursor:pointer; font-weight:600;" onclick="removeMenuItem(this)">🗑️ Remover Este Item</button>
                </div>
            </div>
        `;

        li.querySelector('.item-title-display').textContent = title;
        li.querySelector('.edit-menu-item-title').value = title;
        li.querySelector('.edit-menu-item-url').value = url;
        return li;
    }

    function addSelectedPages() {
        const checkboxes = document.querySelectorAll('.page-checkbox:checked');
        const list = document.getElementById('menu-items-list');
        if (!list) {
            OS.notify('Você precisa criar ou selecionar um menu antes de adicionar itens.', 'error');
            return;
        }

        checkboxes.forEach(cb => {
            const title = cb.getAttribute('data-title') || 'Página';
            const pageId = cb.getAttribute('data-id') || '';
            const slug = cb.getAttribute('data-slug') || '/';
            const li = buildMenuItemElement({
                title: title,
                pageId: pageId,
                url: pageId ? ('/' + slug.replace(/^\/+/, '')) : slug,
                depth: 0
            });
            list.appendChild(li);
            cb.checked = false;
        });

        const selectAll = document.getElementById('select-all-pages-cb');
        if (selectAll) selectAll.checked = false;
        refreshHierarchyMetadata();
    }

    function addCustomLink() {
        const urlInput = document.getElementById('custom-link-url');
        const textInput = document.getElementById('custom-link-text');
        const depthSelect = document.getElementById('custom-link-as-sub');

        const url = urlInput.value.trim();
        const text = textInput.value.trim();
        const requestedDepth = depthSelect ? parseInt(depthSelect.value || '0', 10) : 0;

        if (!url || !text) {
            OS.notify('Preencha a URL e o texto do link.', 'error');
            return;
        }

        const list = document.getElementById('menu-items-list');
        if (!list) {
            OS.notify('Você precisa criar ou selecionar um menu antes de adicionar itens.', 'error');
            return;
        }

        const li = buildMenuItemElement({
            title: text,
            pageId: '',
            url: url,
            depth: requestedDepth
        });
        list.appendChild(li);
        refreshHierarchyMetadata();

        urlInput.value = '#';
        textInput.value = '';
        if (depthSelect) depthSelect.value = '0';
    }

    async function saveMenuSettings(e, url) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify(data)
            });
            const resData = await response.json();
            if (resData.success) {
                OS.notify(resData.message, 'success');
                if (!data.id) {
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
        refreshHierarchyMetadata();
        const listItems = document.querySelectorAll('#menu-items-list .menu-item');
        const items = [];
        listItems.forEach(li => {
            items.push({
                id: li.dataset.itemId,
                parent_id: li.dataset.parentId && li.dataset.parentId !== '0' ? li.dataset.parentId : null,
                depth: getItemDepth(li),
                title: li.dataset.title,
                url: li.dataset.url,
                page_id: li.dataset.pageId,
                target: li.dataset.target || '_self',
                css_classes: li.dataset.cssClasses || ''
            });
        });

        const menuId = document.querySelector('input[name="menu_id"]').value;
        const csrf = document.querySelector('input[name="csrf_token"]').value;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
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

    async function deleteMenuData(event, url) {
        const btn = event.currentTarget;
        if (!btn.dataset.confirmed) {
            OS.confirm('Tem certeza absoluta que deseja excluir este menu?', () => {
                btn.dataset.confirmed = 'true';
                btn.click();
            });
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
                }
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
</script>
<style>
    .wp-nav-menu-list .menu-item {
        margin-bottom: 10px;
        transition: margin-left 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .wp-nav-menu-list .menu-item-bar {
        background: var(--bg-deep, #0b0e14);
        border: 1px solid var(--border, rgba(255,255,255,0.1));
        border-radius: 8px;
        padding: 12px 16px;
        cursor: grab;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .wp-nav-menu-list .menu-item-bar:hover {
        border-color: rgba(56, 189, 248, 0.45);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    }
    .wp-nav-menu-list .menu-item-depth-1 .menu-item-bar,
    .wp-nav-menu-list .menu-item-depth-2 .menu-item-bar,
    .wp-nav-menu-list .menu-item-depth-3 .menu-item-bar {
        border-left: 3px solid var(--primary, #38bdf8);
        background: rgba(15, 23, 42, 0.85);
    }
    .wp-nav-menu-list .menu-item-handle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .wp-nav-menu-list .drag-grip {
        color: var(--text-muted);
        font-size: 16px;
        cursor: grab;
        user-select: none;
    }
    .wp-nav-menu-list .is-submenu-badge {
        font-size: 11px;
        font-weight: 700;
        color: var(--primary, #38bdf8);
        background: rgba(56, 189, 248, 0.12);
        border: 1px solid rgba(56, 189, 248, 0.3);
        padding: 2px 8px;
        border-radius: 999px;
    }
    .wp-nav-menu-list .menu-item-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .wp-nav-menu-list .item-type-label {
        font-size: 11px;
        color: var(--text-muted);
        margin-right: 6px;
    }
    .wp-nav-menu-list .indent-btn,
    .wp-nav-menu-list .edit-toggle-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        border-radius: 5px;
        padding: 4px 8px;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .wp-nav-menu-list .indent-btn:hover,
    .wp-nav-menu-list .edit-toggle-btn:hover {
        background: rgba(56, 189, 248, 0.15);
        border-color: var(--primary, #38bdf8);
        color: #fff;
    }
    .wp-nav-menu-list .delete-item-btn {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #f87171;
        border-radius: 5px;
        padding: 4px 8px;
        font-size: 11px;
        cursor: pointer;
    }
    .wp-nav-menu-list .menu-item-settings {
        background: rgba(10, 14, 22, 0.95);
        border: 1px solid var(--border, rgba(255,255,255,0.1));
        border-top: none;
        border-radius: 0 0 8px 8px;
        padding: 14px 16px;
        margin-top: -4px;
    }
    .wp-nav-menu-list .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .wp-nav-menu-list .settings-grid label {
        display: block;
        font-size: 11px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }
    .wp-nav-menu-list .settings-grid input,
    .wp-nav-menu-list .settings-grid select {
        width: 100%;
        font-size: 13px;
    }
    .sortable-ghost {
        opacity: 0.45;
        background: rgba(69, 243, 255, 0.1) !important;
        border: 1px dashed var(--primary) !important;
    }
    .dragging-child-companion {
        opacity: 0.35;
    }
</style>
