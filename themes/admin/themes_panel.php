<div class="wrap">
    <h1 class="wp-heading-inline">Painel de Temas</h1>
    <p>Aqui você pode ver as interfaces dinâmicas (CockPITs) instaladas no sistema. Cada tema isola a interface de um perfil de usuário.</p>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
        <?php foreach ($themes as $t): ?>
            <div style="background: #fff; border: 1px solid #c3c4c7; width: 300px; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
                <div style="width: 100%; height: 150px; background: #f0f0f1; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 1px solid #ddd;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
                <h3 style="margin: 0 0 10px; font-size: 16px; color: #1d2327;"><?= htmlspecialchars($t['name']) ?> Theme</h3>
                <p style="margin: 0 0 15px; font-size: 13px; color: #646970;">Diretório: <code>/themes/<?= htmlspecialchars($t['folder']) ?></code></p>
                <div style="border-top: 1px solid #f0f0f1; padding-top: 10px;">
                    <span style="font-size: 12px; font-weight: 600; color: #2271b1;">ATIVO E ISOLADO</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
