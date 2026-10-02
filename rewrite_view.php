<?php
$f = "src/Plugins/nav_menus/views/admin_menus.php";
$code = file_get_contents($f);

// 1. Remove old success/error blocks
$code = preg_replace("/<\?php if \(isset\(\\\$_SESSION\['admin_success'\]\)\): \?>.*?<\?php endif; \?>/s", "", $code);
$code = preg_replace("/<\?php if \(isset\(\\\$_SESSION\['admin_error'\]\)\): \?>.*?<\?php endif; \?>/s", "", $code);

// 2. Add Toast container right after the H1
$toastHtml = "
    <!-- Toast Container -->
    <div id=\"toast-container\" style=\"position: fixed; top: 20px; right: 20px; z-index: 9999;\"></div>
";
$code = preg_replace("/<\/h1>/", "</h1>" . $toastHtml, $code);

// 3. Forms to JS
$code = str_replace('<form method="POST" action="<?= BASE_URL ?>/admin/themes/menus/store">', '<form id="menu-settings-form" onsubmit="saveMenuSettings(event, \'<?= BASE_URL ?>/admin/themes/menus/store\')">', $code);

$code = str_replace('<button type="button" class="btn" style="background:#dc3545; border-color:#dc3545;" onclick="if(confirm(\'Tem certeza?\')) { document.getElementById(\'delete-menu-form\').submit(); }">Excluir Menu</button>', '<button type="button" class="btn" style="background:#dc3545; border-color:#dc3545;" onclick="deleteMenuData(\'<?= BASE_URL ?>/admin/themes/menus/delete/<?= $activeMenu[\'id\'] ?>\')">Excluir Menu</button>', $code);

$code = preg_replace('/<form id="delete-menu-form" method="POST" action=".*?<\/form>/s', '', $code);

// 4. Update the item HTML to have an edit button
$oldItemHtml = '<button type="button" style="background:transparent; border:none; color:#dc3545; cursor:pointer;" onclick="this.closest(\'.menu-item\').remove()">Excluir</button>';
$newItemHtml = '
    <div>
        <button type="button" style="background:transparent; border:none; color:var(--primary); cursor:pointer; margin-right: 10px;" onclick="editItemTitle(this)">Editar</button>
        <button type="button" style="background:transparent; border:none; color:#dc3545; cursor:pointer;" onclick="this.closest(\'.menu-item\').remove()">Excluir</button>
    </div>
';
$code = str_replace($oldItemHtml, $newItemHtml, $code);

// 5. Update Javascript
$newJs = <<<'JS'
<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const bg = type === 'success' ? '#28a745' : '#dc3545';
        toast.style = `background: ${bg}; color: white; padding: 15px 25px; border-radius: 4px; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); opacity: 0; transform: translateX(50px); transition: all 0.3s ease;`;
        toast.innerText = message;
        container.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
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
                showToast(resData.message, 'success');
                if (!data.id) { // Foi criado um novo
                    setTimeout(() => window.location.href = '?menu_id=' + resData.id, 1000);
                }
            } else {
                showToast(resData.message, 'error');
            }
        } catch (err) {
            showToast('Erro ao salvar as configurações.', 'error');
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
                showToast(resData.message, 'success');
            } else {
                showToast(resData.message, 'error');
            }
        } catch (err) {
            showToast('Erro ao salvar a estrutura do menu.', 'error');
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
                showToast(resData.message, 'success');
                setTimeout(() => window.location.href = '?deleted=1', 1000);
            } else {
                showToast(resData.message, 'error');
            }
        } catch (err) {
            showToast('Erro ao excluir.', 'error');
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
JS;

$code = preg_replace('/function saveMenuItems\(\).*?items-form.\)\.submit\(\);\s*}/s', $newJs, $code);

// Fix the onclick call in the html button
$code = str_replace('onclick="saveMenuItems()"', 'onclick="saveMenuItems(\'<?= BASE_URL ?>/admin/themes/menus/items\')"', $code);

// Fix the JS add items to include the edit button
$addPagesFix = str_replace("this.closest('.menu-item').remove()", "this.closest('.menu-item').remove()\">Excluir</button>", ""); // just clear out old, we'll regex the whole html chunk

$code = preg_replace('/li\.innerHTML = `.*?`;/s', 'li.innerHTML = `
                <div>
                    <strong style="color: #fff;">${title || text}</strong>
                    <span style="color: var(--text-muted); font-size: 12px; margin-left: 10px;">${pageId ? "Página Dinâmica" : "Link Personalizado (" + url + ")"}</span>
                </div>
                <div>
                    <button type="button" style="background:transparent; border:none; color:var(--primary); cursor:pointer; margin-right: 10px;" onclick="editItemTitle(this)">Editar</button>
                    <button type="button" style="background:transparent; border:none; color:#dc3545; cursor:pointer;" onclick="this.closest(\'.menu-item\').remove()">Excluir</button>
                </div>
            `;', $code);

// Remove old javascript function we just regexed out
$code = str_replace('function saveMenuItems() {', '', $code);

file_put_contents($f, $code);
echo "Done";
