<style>
    @keyframes pulse-red {
        0% { box-shadow: 0 0 0 0 rgba(220, 50, 50, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(220, 50, 50, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 50, 50, 0); }
    }
    
    .btn-danger-pulse {
        background: #dc3232;
        color: #fff;
        text-decoration: none;
        padding: 12px 30px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        min-width: 200px;
        animation: pulse-red 2s infinite;
        transition: background 0.3s;
    }
    .btn-danger-pulse:hover {
        background: #b32d2d;
        color: #fff;
        animation: none;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Configurações Globais</h1>
</div>

<?php if(isset($_GET['success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #c3e6cb;">
        Configurações salvas com sucesso!
    </div>
<?php endif; ?>

<div class="sys-tabs">
    <button class="sys-tab active" onclick="openSysTab(event, 'tab-geral')"><i class="fas fa-cog"></i> Geral</button>
    <button class="sys-tab" onclick="openSysTab(event, 'tab-avancado')"><i class="fas fa-tools"></i> Avançado</button>
</div>

<!-- ABA 1: GERAL -->
<div id="tab-geral" class="sys-content active">
    <form method="POST" action="<?= BASE_URL ?>/admin/settings" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Nome do Sistema / Negócio</label>
            <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">CNPJ (Opcional)</label>
            <input type="text" name="site_cnpj" value="<?= htmlspecialchars($settings['site_cnpj'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;" placeholder="00.000.000/0001-00">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Slogan / Subtítulo</label>
            <input type="text" name="site_slogan" value="<?= htmlspecialchars($settings['site_slogan'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
            <small style="color: #666;">Aparece no cabeçalho de documentos PDF.</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Endereço Completo</label>
            <input type="text" name="site_address" value="<?= htmlspecialchars($settings['site_address'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Telefone Fixo</label>
            <input type="text" name="site_phone" value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">WhatsApp Oficial</label>
            <input type="text" name="site_whatsapp" value="<?= htmlspecialchars($settings['site_whatsapp'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px; padding-top: 15px; border-top: 1px solid #eee;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Logo do Sistema (PNG, máx 512x512)</label>
            <?php if(!empty($settings['site_logo'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="<?= $settings['site_logo'] ?>" alt="Logo" style="max-height: 80px; border: 1px solid #ccc; padding: 5px; background: #fafafa;">
                </div>
            <?php endif; ?>
            <input type="file" name="site_logo" accept="image/png" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" class="btn btn-activate" style="padding: 10px 20px; font-size: 14px;">Salvar Configurações</button>
    </form>
</div>

<!-- ABA 2: AVANÇADO -->
<div id="tab-avancado" class="sys-content">
    <div style="padding-top: 10px;">
        <h3 style="color: #dc3232; margin-top: 0; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-exclamation-triangle"></i> Zona de Perigo
        </h3>
        <p style="color: #646970; font-size: 14px; margin-bottom: 30px;">
            Esta área contém ações irreversíveis e estruturais do sistema. Proceda com cautela extrema.
        </p>
        

        <div style="border: 1px solid #4a545a; border-radius: 8px; padding: 25px; background: #2c3338; margin-bottom: 30px;">
            <h4 style="margin-top: 0; font-size: 18px; color: #fff; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-hammer"></i> Modo de Manutenção
            </h4>
            <p style="margin: 10px 0 20px; font-size: 14px; color: #8c8f94; line-height: 1.6;">
                Ativar esta opção bloqueia o acesso ao Frontend para visitantes comuns, exibindo uma tela isolada de Manutenção.
                Sua sessão de administrador continuará funcionando normalmente.
            </p>
            <form method="POST" action="<?= BASE_URL ?>/admin/settings/maintenance">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <?php $isMaint = file_exists(dirname(__DIR__, 4) . '/.maintenance'); ?>
                <?php if($isMaint): ?>
                    <input type="hidden" name="action" value="off">
                    <button type="submit" class="btn btn-activate" style="background: #28a745; border-color: #28a745;"><i class="fas fa-play"></i> Desativar e Publicar Site</button>
                <?php else: ?>
                    <input type="hidden" name="action" value="on">
                    <button type="submit" class="btn btn-activate" style="background: #f0ad4e; border-color: #f0ad4e; color: #000;"><i class="fas fa-pause"></i> Ativar Modo Manutenção</button>
                <?php endif; ?>
            </form>
        </div>

        <div style="border: 2px solid #ffb0b0; border-radius: 8px; padding: 25px; background: #fff5f5; text-align: center;">
            
            <h4 style="margin-top: 0; font-size: 18px; color: #b32d2d;">Restaurar Padrões de Fábrica (Wipe)</h4>
            <p style="margin: 15px 0 30px; font-size: 14px; color: #646970; line-height: 1.6;">
                Atenção: Ao executar esta ação, <strong>TODO</strong> o banco de dados será limpo.<br>
                Usuários, tenants, dados estruturais, configurações e agendamentos serão destruídos instantaneamente.<br>
                O sistema voltará ao seu estado de pré-instalação (Modo Assistente).
            </p>

            <div style="display: flex; justify-content: center;">
                <a href="<?= BASE_URL ?>/admin/settings/factory-reset" class="btn-danger-pulse">
                    <i class="fas fa-bomb"></i> Iniciar Wipe Total do Sistema
                </a>
            </div>
            
        </div>
    </div>
</div>

<script>
function openSysTab(evt, tabId) {
    var i, tabcontent, tablinks;
    
    tabcontent = document.getElementsByClassName("sys-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    
    tablinks = document.getElementsByClassName("sys-tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>

