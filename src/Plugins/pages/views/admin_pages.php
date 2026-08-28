<div class="wrap">
    <h1 style="display: flex; align-items: center; justify-content: space-between;">
        <span>📄 Gerenciador de Páginas</span>
        <a href="<?= BASE_URL ?>/admin/pages/create" class="btn" style="background: #2563eb; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px;">Nova Página</a>
    </h1>
    
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div style="background: #dcfce3; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($_SESSION['flash_message']['msg']) ?>
            <?php unset($_SESSION['flash_message']); ?>
        </div>
    <?php endif; ?>

    <table class="wp-list-table" style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left;">Título</th>
                <th style="padding: 12px; text-align: left;">URL (Slug)</th>
                <th style="padding: 12px; text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($pages)): ?>
                <tr><td colspan="3" style="text-align:center; padding: 30px;">Nenhuma página criada ainda.</td></tr>
            <?php else: foreach($pages as $p): ?>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px; font-weight: bold;"><?= htmlspecialchars($p['title']) ?></td>
                    <td style="padding: 12px; color: #64748b;">/p/<?= htmlspecialchars($p['slug']) ?></td>
                    <td style="padding: 12px; text-align: center;">
                        <a href="<?= BASE_URL ?>/admin/pages/edit/<?= $p['id'] ?>" style="color: #2563eb; text-decoration: none; font-weight: bold; margin-right: 15px;">Editar</a>
                        <a href="<?= BASE_URL ?>/p/<?= $p['slug'] ?>" target="_blank" style="color: #10b981; text-decoration: none; font-weight: bold;">Ver Página ↗</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
