<div class="dashboard-container">
    <?php if (!empty($_SESSION['plugin_crashes'])): ?>
    <div style="background: #f87171; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 6px solid #b91c1c; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: white; display: flex; align-items: center; gap: 10px;">⚠️ No-Break Shield Alert</h3>
        <p style="margin-bottom: 10px;">O No-Break Shield desarmou os seguintes plugins devido a falhas fatais (Fatal Crash):</p>
        <ul style="margin-bottom: 0;">
            <?php foreach ($_SESSION['plugin_crashes'] as $crash): ?>
                <li><strong><?= htmlspecialchars($crash['plugin'] ?? 'Desconhecido') ?>:</strong> <?= htmlspecialchars($crash['error'] ?? 'Erro Desconhecido') ?></li>
            <?php endforeach; ?>
        </ul>
        <div style="margin-top: 15px;">
            <form action="<?= BASE_URL ?>/admin/clear-crashes" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="submit" style="background: #b91c1c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">Ciente (Limpar Avisos)</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Dashboard</h2>
        <button id="addWidgetBtn" class="button button-primary">➕ Adicionar Widget</button>
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
        
        <form id="addWidgetForm">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:10px;">1. Selecione o Módulo Provedor:</label>
                <select id="providerSelect" style="width:100%; padding:10px; background:#2c3338; color:#fff; border:1px solid #8c8f94; border-radius:4px;">
                    <option value="">-- Escolha --</option>
                    <?php foreach ($catalog as $provider): ?>
                        <option value="<?= htmlspecialchars($provider['provider_class']) ?>">
                            <?= htmlspecialchars($provider['provider_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:10px;">2. Selecione o Widget:</label>
                <select id="widgetSelect" style="width:100%; padding:10px; background:#2c3338; color:#fff; border:1px solid #8c8f94; border-radius:4px;" disabled>
                    <option value="">-- Selecione o Módulo Primeiro --</option>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" id="closeModalBtn" class="button" style="background:#8c8f94; color:#fff;">Cancelar</button>
                <button type="submit" class="button button-primary">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Mantém o JSON do catálogo no JS para popular o segundo combobox dinamicamente
const catalog = <?= json_encode($catalog) ?>;
const currentUserWidgets = <?= json_encode($userWidgets) ?>;

const addWidgetBtn = document.getElementById('addWidgetBtn');
const widgetModal = document.getElementById('widgetModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const providerSelect = document.getElementById('providerSelect');
const widgetSelect = document.getElementById('widgetSelect');
const addWidgetForm = document.getElementById('addWidgetForm');

addWidgetBtn.addEventListener('click', () => {
    widgetModal.style.display = 'flex';
});

closeModalBtn.addEventListener('click', () => {
    widgetModal.style.display = 'none';
});

// Popula os widgets quando o provedor muda
providerSelect.addEventListener('change', function() {
    const providerClass = this.value;
    widgetSelect.innerHTML = '<option value="">-- Escolha --</option>';
    
    if (!providerClass) {
        widgetSelect.disabled = true;
        return;
    }

    const providerData = catalog.find(p => p.provider_class === providerClass);
    if (providerData && providerData.widgets) {
        Object.entries(providerData.widgets).forEach(([id, meta]) => {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = meta.title + ' (' + meta.description + ')';
            widgetSelect.appendChild(opt);
        });
        widgetSelect.disabled = false;
    }
});

// Ao submeter, envia a nova array de widgets via form dinâmico
addWidgetForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const pClass = providerSelect.value;
    const wId = widgetSelect.value;
    
    if(!pClass || !wId) {
        alert("Selecione um módulo e um widget!");
        return;
    }

    // Adiciona na array
    currentUserWidgets.push({provider: pClass, id: wId});
    
    // Cria form invisível e submete para save-layout
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= BASE_URL ?>/admin/dashboard/save-layout';
    
    // INJETANDO O CSRF TOKEN TAMBÉM NO FORM INVISÍVEL PARA NÃO DAR ERRO DE CSRF DEPOIS!
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    form.appendChild(csrfInput);

    currentUserWidgets.forEach(w => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'widgets[]';
        input.value = JSON.stringify(w);
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
});
</script>
