<div class="wrap">
    <h1>🧠 Configurações do Cérebro I.A. (AI Hub)</h1>
    <p>Configure as chaves de API e selecione o modelo de Inteligência Artificial que irá gerenciar o ecossistema através dos Contratos (Interfaces).</p>

    <?php if (isset($_GET['saved'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb; border-radius: 4px;">
            Configurações salvas com sucesso!
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/ai-hub/save" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 800px;">
        
        <div style="margin-bottom: 20px;">
            <label style="display:block; font-weight: 600; margin-bottom: 5px;">Modelo Ativo (Motor de Raciocínio):</label>
            <select name="active_model" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;">
                <option value="gemini" <?= ($config['active_model'] === 'gemini') ? 'selected' : '' ?>>Google Gemini (Recomendado)</option>
                <option value="openai" <?= ($config['active_model'] === 'openai') ? 'selected' : '' ?>>OpenAI ChatGPT</option>
                <option value="deepseek" <?= ($config['active_model'] === 'deepseek') ? 'selected' : '' ?>>DeepSeek</option>
                <option value="claude" <?= ($config['active_model'] === 'claude') ? 'selected' : '' ?>>Anthropic Claude</option>
            </select>
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Todas as I.A.s respeitarão as mesmas Interfaces e não terão acesso direto ao banco de dados ou Kernel.</p>
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Chaves de API (API Keys)</h3>

        <div style="margin-bottom: 15px;">
            <label style="display:block; font-weight: 600; margin-bottom: 5px;">Google Gemini API Key:</label>
            <input type="password" name="api_keys[gemini]" value="<?= htmlspecialchars($config['api_keys']['gemini'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; font-weight: 600; margin-bottom: 5px;">OpenAI API Key:</label>
            <input type="password" name="api_keys[openai]" value="<?= htmlspecialchars($config['api_keys']['openai'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; font-weight: 600; margin-bottom: 5px;">DeepSeek API Key:</label>
            <input type="password" name="api_keys[deepseek]" value="<?= htmlspecialchars($config['api_keys']['deepseek'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display:block; font-weight: 600; margin-bottom: 5px;">Anthropic Claude API Key:</label>
            <input type="password" name="api_keys[claude]" value="<?= htmlspecialchars($config['api_keys']['claude'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;">
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">🔌 Tomadas Ativas (Interfaces Expostas)</h3>
        <p style="font-size: 13px; color: #555;">Estes são os conectores do Kernel que a I.A. selecionada acima poderá "plugar" para ler ou alterar dados com segurança total.</p>
        
        <ul style="list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <li style="padding: 10px; background: #f6f7f7; border-left: 4px solid #00a32a;">✅ PatientRepositoryInterface</li>
            <li style="padding: 10px; background: #f6f7f7; border-left: 4px solid #00a32a;">✅ AppointmentRepositoryInterface</li>
            <li style="padding: 10px; background: #f6f7f7; border-left: 4px solid #00a32a;">✅ ErrorLogReaderInterface</li>
            <li style="padding: 10px; background: #f6f7f7; border-left: 4px solid #d63638; color: #888;">❌ DatabaseConnection (Bloqueado p/ IA)</li>
        </ul>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <button type="submit" class="btn" style="background: #2271b1; color: #fff; border-color: #2271b1; padding: 6px 16px; font-size: 14px;">Salvar Conexões Neurais</button>
        <button type="button" class="btn" id="btnTest" style="background: #00a32a; color: #fff; border-color: #00a32a; padding: 6px 16px; font-size: 14px; margin-left: 10px;">Testar Motor Ativo</button>
        <div id="testResult" style="margin-top: 15px; font-weight: bold; font-size: 14px; padding: 10px; display: none; border-radius: 4px;"></div>
    </form>
</div>

<script>
document.getElementById('btnTest').addEventListener('click', function() {
    var resultDiv = document.getElementById('testResult');
    resultDiv.style.display = 'block';
    resultDiv.style.background = '#f6f7f7';
    resultDiv.style.color = '#555';
    resultDiv.style.border = '1px solid #ccc';
    resultDiv.innerText = 'Testando conexão com a I.A... (aguarde)';
    
    fetch('<?= BASE_URL ?>/admin/ai-hub/test', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resultDiv.style.background = '#d4edda';
            resultDiv.style.color = '#155724';
            resultDiv.style.border = '1px solid #c3e6cb';
            resultDiv.innerText = '✅ Sucesso! A I.A. respondeu: "' + data.message + '"';
        } else {
            resultDiv.style.background = '#f8d7da';
            resultDiv.style.color = '#721c24';
            resultDiv.style.border = '1px solid #f5c6cb';
            resultDiv.innerText = '❌ Falha: ' + data.message;
        }
    })
    .catch(e => {
        resultDiv.style.background = '#f8d7da';
        resultDiv.style.color = '#721c24';
        resultDiv.style.border = '1px solid #f5c6cb';
        resultDiv.innerText = '❌ Erro de rede ao testar.';
    });
});
</script>
