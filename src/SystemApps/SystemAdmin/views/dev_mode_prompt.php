<div class="wrap" style="max-width: 400px; margin: 40px auto;">
    <h1 style="text-align: center; color: var(--accent-orange);">⚠️ Modo Desenvolvedor</h1>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 20px;">
        Esta ação afeta a infraestrutura do sistema. Insira sua senha de administrador para continuar.
    </p>

    <?php if (!empty($error)): ?>
        <div style="padding: 12px; margin-bottom: 20px; border-left: 4px solid #d63638; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <strong style="color: #d63638;"><?= htmlspecialchars($error) ?></strong>
        </div>
    <?php endif; ?>

    <div style="background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <form method="POST" action="<?= BASE_URL ?>/admin/dev-mode/auth">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '') ?>">
            
            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Senha do Administrador</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required autofocus
                        style="width: 100%; padding: 10px; padding-right: 40px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    <button type="button" onclick="const p = document.getElementById('password'); if(p.type === 'password'){ p.type = 'text'; this.innerHTML = '<svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24\'></path><line x1=\'1\' y1=\'1\' x2=\'23\' y2=\'23\'></line></svg>'; } else { p.type = 'password'; this.innerHTML = '<svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z\'></path><circle cx=\'12\' cy=\'12\' r=\'3\'></circle></svg>'; }" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: transparent; border: none; color: #666; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            
            <div style="text-align: right;">
                <a href="<?= htmlspecialchars($redirect ?? BASE_URL . '/admin/plugins') ?>" class="btn" style="color: var(--text-muted); text-decoration: none; margin-right: 10px;">Cancelar</a>
                <button type="submit" class="btn btn-activate" style="background-color: var(--accent-orange); border-color: var(--accent-orange);">Autenticar</button>
            </div>
        </form>
    </div>
</div>
