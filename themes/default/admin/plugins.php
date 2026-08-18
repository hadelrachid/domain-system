<?php
// Layout Header
$title = "Gerenciador de Plugins";
// We don't have getHeader() implemented in ThemeManager completely in index.php yet (the dummy one had it).
// Let's use simple HTML for now to not depend on default theme structure.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain-System Admin | Plugins</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f0f0f1; margin: 0; padding: 20px; color: #3c434a; }
        .wrap { max-width: 1000px; margin: 0 auto; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; }
        h1 { font-size: 23px; font-weight: 400; margin: 0 0 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #c3c4c7; }
        th { font-weight: 600; background: #f6f7f7; }
        .plugin-active { background: #f6fcf8; }
        .plugin-inactive { background: #fff; }
        .btn { padding: 4px 8px; border: 1px solid; border-radius: 3px; cursor: pointer; text-decoration: none; font-size: 13px; }
        .btn-activate { background: #f6f7f7; border-color: #2271b1; color: #2271b1; }
        .btn-deactivate { background: #f6f7f7; border-color: #d63638; color: #d63638; }
        .btn-core { background: #e0e0e0; border-color: #ccc; color: #666; cursor: not-allowed; }
        .badge { font-size: 11px; padding: 2px 6px; border-radius: 10px; background: #000; color: #fff; margin-left: 5px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Gerenciador de Plugins</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Descrição</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): ?>
                <tr class="<?= $plugin['is_active'] ? 'plugin-active' : 'plugin-inactive' ?>">
                    <td>
                        <strong><?= htmlspecialchars($plugin['name']) ?></strong> 
                        <span class="badge">v<?= htmlspecialchars($plugin['version']) ?></span>
                        <br>
                        <small style="color:#666;">/<?= htmlspecialchars($plugin['folder']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($plugin['description']) ?></td>
                    <td>
                        <form method="POST" action="/admin/plugins/toggle" style="margin:0;">
                            <input type="hidden" name="plugin_name" value="<?= htmlspecialchars($plugin['name']) ?>">
                            
                            <?php if ($plugin['is_core']): ?>
                                <button type="button" class="btn btn-core" disabled>Core (Bloqueado)</button>
                            <?php elseif ($plugin['is_active']): ?>
                                <input type="hidden" name="action" value="disable">
                                <button type="submit" class="btn btn-deactivate">Desativar</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="enable">
                                <button type="submit" class="btn btn-activate">Ativar</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
