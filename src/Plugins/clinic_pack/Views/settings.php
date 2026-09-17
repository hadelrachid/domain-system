<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0; font-size: 22px; color: var(--text-main);">Configurações da Clínica</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="padding: 12px; margin-bottom: 20px; border-left: 4px solid var(--accent-green); background: rgba(0,210,132,0.1);">
        <strong style="color: var(--accent-green);">Configurações salvas com sucesso!</strong>
    </div>
<?php endif; ?>

<?php $activeTab = $_GET['tab'] ?? 'gerais'; ?>
<!-- TABS HEADER -->
<div class="sys-tabs">
    <button type="button" class="sys-tab <?= $activeTab === 'gerais' ? 'active' : '' ?>" onclick="openClinicTab('tab-gerais', this)">Gerais & API</button>
    <button type="button" class="sys-tab <?= $activeTab === 'convenios' ? 'active' : '' ?>" onclick="openClinicTab('tab-convenios', this)">Convênios</button>
</div>

<!-- TABS CONTENT -->
<div class="sys-content <?= $activeTab === 'gerais' ? 'active' : '' ?>" id="tab-gerais">
    <div class="card" style="padding: 25px;">
        <h3 style="margin-top:0; color: var(--text-main);">Configurações Gerais</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/clinic/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="display:block; font-weight:600; margin-bottom:8px; color: var(--text-main);">Nome da Clínica</label>
                <input type="text" name="clinic_name" value="<?= htmlspecialchars($settings['clinic_name'] ?? 'Daher Clínica') ?>" style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-deep); color: var(--text-main);">
            </div>

            <h3 style="margin-top: 35px; border-bottom: 1px dashed var(--border); padding-bottom: 10px; color: var(--text-main);">Integração WhatsApp API</h3>
            
            <div style="margin-bottom: 20px;">
                <label style="display:block; font-weight:600; margin-bottom:8px; color: var(--text-main);">URL da API (Evolution/Z-API)</label>
                <input type="url" name="whatsapp_api_url" value="<?= htmlspecialchars($settings['whatsapp_api_url'] ?? '') ?>" style="width: 100%; max-width: 600px; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-deep); color: var(--text-main);">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display:block; font-weight:600; margin-bottom:8px; color: var(--text-main);">Token de Acesso</label>
                <input type="password" name="whatsapp_api_token" value="<?= htmlspecialchars($settings['whatsapp_api_token'] ?? '') ?>" style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-deep); color: var(--text-main);">
            </div>

            <button type="submit" class="btn button-primary">Salvar Configurações Gerais</button>
        </form>
    </div>
</div>

<div class="sys-content <?= $activeTab === 'convenios' ? 'active' : '' ?>" id="tab-convenios">
    <div class="card" style="padding: 25px; margin-bottom: 25px;">
        <h3 style="margin-top:0; color: var(--text-main);">Adicionar Novo Convênio</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/clinic/settings/insurance/add" style="display:flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div style="flex:1; max-width: 350px;">
                <label style="display:block; font-weight:600; margin-bottom:8px; color: var(--text-main);">Nome do Convênio</label>
                <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; background: var(--bg-deep); color: var(--text-main);">
            </div>
            <button type="submit" class="btn btn-activate" style="margin-bottom: 2px;">Adicionar Convênio</button>
        </form>
    </div>
    
    <table class="plugin-table" style="width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
        <thead>
            <tr>
                <th style="padding: 15px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.2); font-weight: 600; text-align: left; color: var(--text-main);">Nome do Convênio</th>
                <th style="padding: 15px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.2); font-weight: 600; text-align: left; width: 150px; color: var(--text-main);">Status</th>
                <th style="padding: 15px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.2); font-weight: 600; text-align: left; width: 100px; color: var(--text-main);">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($insurances as $ins): ?>
            <tr>
                <td style="padding: 15px; border-bottom: 1px solid var(--border); color: var(--text-main);"><?= htmlspecialchars($ins['name']) ?></td>
                <td style="padding: 15px; border-bottom: 1px solid var(--border);">
                    <?= $ins['active'] ? '<span class="badge" style="background: rgba(0,210,132,0.1); border: 1px solid var(--accent-green); color: var(--accent-green);">Ativo</span>' : '<span class="badge" style="background: rgba(245,110,40,0.1); border: 1px solid var(--accent-orange); color: var(--accent-orange);">Inativo</span>' ?>
                </td>
                <td style="padding: 15px; border-bottom: 1px solid var(--border);">
                    <form method="POST" action="<?= BASE_URL ?>/admin/clinic/settings/insurance/delete" onsubmit="return confirm('Excluir este convênio?')" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="id" value="<?= $ins['id'] ?>">
                        <button type="submit" class="btn btn-deactivate" style="padding: 4px 8px !important; font-size: 11px !important;">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function openClinicTab(tabId, btn) {
    // Esconder todos os conteúdos
    document.querySelectorAll('.sys-content').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    
    // Remover classe active de todos os botões
    document.querySelectorAll('.sys-tab').forEach(el => el.classList.remove('active'));
    // Adicionar classe active ao botão clicado
    btn.classList.add('active');
}
</script>
