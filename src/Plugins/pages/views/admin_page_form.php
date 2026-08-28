<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <a href="<?= BASE_URL ?>/admin/pages" style="text-decoration: none; font-size: 20px;">⬅️</a>
        <?= $page ? 'Editar Página' : 'Criar Nova Página' ?>
    </h1>

    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
        <form action="<?= BASE_URL ?>/admin/pages/store" method="POST">
            <?php if($page): ?>
                <input type="hidden" name="id" value="<?= $page['id'] ?>">
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Título da Página</label>
                <input type="text" name="title" value="<?= $page ? htmlspecialchars($page['title']) : '' ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 16px;" required>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">
                    Conteúdo (Editor Raw/Shortcodes)
                    <span style="font-weight: normal; color: #64748b; font-size: 12px; margin-left: 10px;">Aqui você pode colar HTML ou seus [shortcodes]</span>
                </label>
                <textarea name="content" rows="15" style="width: 100%; padding: 15px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: monospace; font-size: 14px; background: #f8fafc; line-height: 1.5; resize: vertical;"><?= $page ? htmlspecialchars($page['content']) : '' ?></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn" style="background: #2563eb; color: #fff; padding: 12px 24px; border: none; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer;">
                    <?= $page ? 'Atualizar Página' : 'Publicar Página' ?>
                </button>
            </div>
        </form>
    </div>
</div>
