<div class="dashboard-container">
    <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Dashboard</h2>
        <button id="addWidgetBtn" class="button button-primary">✚ Adicionar Widget</button>
    </div>

    <div class="dashboard-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <?php if (empty($renderedWidgets)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #1d2327; color: #8c8f94; border-radius: 8px;">
                <p>O seu painel está vazio. Clique em "Adicionar Widget" para personalizá-lo.</p>
            </div>
        <?php else: ?>
            <?php foreach ($renderedWidgets as $html): ?>
                <div class="widget-wrapper" style="width: 100%;">
                    <?= $html ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Combobox -->
<div id="widgetModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#1d2327; padding:30px; border-radius:8px; width:500px; color:#fff; border: 1px solid #2c3338;">
        <h3>Adicionar Widget ao Painel</h3>
        <p style="color:#8c8f94;">Selecione os widgets fornecidos pelos plugins ativos:</p>
        
        <form method="POST" action="<?= BASE_URL ?>/admin/dashboard/save-layout" id="widgetForm">
            <?php 
                $sessionMgr = \DomainSystem\Core\Application::getInstance()->getContainer()->make(\DomainSystem\Core\Http\SessionManager::class);
                $token = $sessionMgr->getCsrfToken();
            ?>
            <input type="hidden" name="csrf_token" value="<?= $token ?>">
            <!-- Lista de Checkboxes (Múltipla Seleção) -->
            <div style="background:#2c3338; border:1px solid #4a545a; border-radius:4px; padding:15px; margin-bottom:20px; max-height:300px; overflow-y:auto;">
                <?php 
                // Extrai apenas os IDs dos widgets já ativos para pré-selecionar
                $activeWidgetIds = array_column($userWidgets ?? [], 'id');
                ?>
                <?php foreach ($catalog as $provider): ?>
                    <div style="margin-bottom: 15px;">
                        <strong style="color:#00d284; display:block; margin-bottom:8px; border-bottom:1px solid #4a545a; padding-bottom:5px;">
                            <?= htmlspecialchars($provider['provider_name']) ?>
                        </strong>
                        <?php foreach ($provider['widgets'] as $id => $meta): ?>
                            <?php 
                                $valJson = htmlspecialchars(json_encode(["provider" => $provider['provider_class'], "id" => $id]));
                                $isChecked = in_array($id, $activeWidgetIds) ? 'checked' : '';
                            ?>
                            <label style="display:flex; align-items:flex-start; gap:10px; margin-bottom:8px; cursor:pointer;">
                                <input type="checkbox" name="widgets[]" value="<?= $valJson ?>" <?= $isChecked ?> style="margin-top:4px;">
                                <div>
                                    <div style="font-weight:bold;"><?= htmlspecialchars($meta['title']) ?></div>
                                    <div style="font-size:12px; color:#8c8f94;"><?= htmlspecialchars($meta['description']) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('widgetModal').style.display='none'" class="button" style="margin-right:10px; background:transparent; color:#fff; border:1px solid #fff;">Cancelar</button>
                <button type="submit" class="button button-primary">Adicionar ao Painel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addWidgetBtn').addEventListener('click', function() {
    document.getElementById('widgetModal').style.display = 'flex';
});
</script>
