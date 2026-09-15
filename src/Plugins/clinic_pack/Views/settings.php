<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0; font-size: 22px;">Configurações da Clínica</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="padding: 10px; margin-bottom: 20px; border-left: 4px solid #4caf50; background: #fff; color: #155724;">
        Configurações salvas com sucesso!
    </div>
<?php endif; ?>

<?php $activeTab = $_GET['tab'] ?? 'gerais'; ?>
<!-- TABS HEADER -->
<div style="border-bottom: 1px solid #c3c4c7; margin-bottom: 20px;">
    <button type="button" class="tab-btn <?= $activeTab === 'gerais' ? 'active' : '' ?>" onclick="openTab('tab-gerais', this)" style="padding: 10px 15px; border: <?= $activeTab === 'gerais' ? '1px solid #c3c4c7' : 'none' ?>; border-bottom: none; background: <?= $activeTab === 'gerais' ? '#fff' : 'transparent' ?>; cursor: pointer; border-radius: 4px 4px 0 0; font-weight: <?= $activeTab === 'gerais' ? 'bold' : 'normal' ?>;">Gerais & API</button>
    <button type="button" class="tab-btn <?= $activeTab === 'convenios' ? 'active' : '' ?>" onclick="openTab('tab-convenios', this)" style="padding: 10px 15px; border: <?= $activeTab === 'convenios' ? '1px solid #c3c4c7' : 'none' ?>; border-bottom: none; background: <?= $activeTab === 'convenios' ? '#fff' : 'transparent' ?>; cursor: pointer; border-radius: 4px 4px 0 0; font-weight: <?= $activeTab === 'convenios' ? 'bold' : 'normal' ?>;">Convênios</button>
</div>

<!-- TABS CONTENT -->
<div class="tab-content" id="tab-gerais" style="display: <?= $activeTab === 'gerais' ? 'block' : 'none' ?>;">
    <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
        <h3 style="margin-top:0;">Configurações Gerais</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/clinic/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Nome da Clínica</label>
                <input type="text" name="clinic_name" value="<?= htmlspecialchars($settings['clinic_name'] ?? 'Daher Clínica') ?>" style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #c3c4c7; border-radius: 4px;">
            </div>

            <h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Integração WhatsApp API</h3>
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">URL da API (Evolution/Z-API)</label>
                <input type="url" name="whatsapp_api_url" value="<?= htmlspecialchars($settings['whatsapp_api_url'] ?? '') ?>" style="width: 100%; max-width: 600px; padding: 8px; border: 1px solid #c3c4c7; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Token de Acesso</label>
                <input type="password" name="whatsapp_api_token" value="<?= htmlspecialchars($settings['whatsapp_api_token'] ?? '') ?>" style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #c3c4c7; border-radius: 4px;">
            </div>

            <button type="submit" class="btn" style="background: #2271b1; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Salvar Configurações Gerais</button>
        </form>
    </div>
</div>

<div class="tab-content" id="tab-convenios" style="display: <?= $activeTab === 'convenios' ? 'block' : 'none' ?>;">
    <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 20px;">
        <h3 style="margin-top:0;">Adicionar Novo Convênio</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/clinic/settings/insurance/add" style="display:flex; gap: 10px; align-items: flex-end;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div style="flex:1; max-width: 300px; margin-right: 10px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Nome do Convênio</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; border: 1px solid #c3c4c7; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn" style="background: #2271b1; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 1px;">Adicionar</button>
        </form>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nome do Convênio</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 80px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($insurances as $ins): ?>
            <tr>
                <td><?= htmlspecialchars($ins['name']) ?></td>
                <td><?= $ins['active'] ? '<span style="color:green">Ativo</span>' : '<span style="color:red">Inativo</span>' ?></td>
                <td>
                    <form method="POST" action="<?= BASE_URL ?>/admin/clinic/settings/insurance/delete" onsubmit="return confirm('Excluir este convênio?')">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="id" value="<?= $ins['id'] ?>">
                        <button type="submit" style="color: #d63638; background: none; border: none; cursor: pointer; text-decoration: underline;">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function openTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
    
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.style.border = 'none';
        el.style.background = 'transparent';
        el.style.fontWeight = 'normal';
    });
    
    btn.style.border = '1px solid #c3c4c7';
    btn.style.borderBottom = 'none';
    btn.style.background = '#fff';
    btn.style.fontWeight = 'bold';
    btn.style.borderRadius = '4px 4px 0 0';
}
</script>
