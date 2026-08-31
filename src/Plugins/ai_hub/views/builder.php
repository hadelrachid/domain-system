<div class="wrap">
    <h1>🏗️ Plugin Builder & IA Editor</h1>
    <p>Peça para a Inteligência Artificial gerar, editar ou testar lógicas para um Plugin. O foco desta ferramenta é gerar <strong>Formulários</strong> e lógicas isoladas que usam <strong>Injeção de Dependência</strong> e respeitam 100% as Interfaces.</p>

    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Lado Esquerdo: Editor / Prompt -->
        <div>
            <div style="background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px; padding: 20px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">1. O que o plugin deve fazer?</h3>
                <textarea id="ai-prompt" style="width: 100%; height: 100px; padding: 10px; font-family: inherit; border: 1px solid #8c8f94; border-radius: 4px;" placeholder="Ex: Crie um plugin que exiba um formulário de cadastro rápido de paciente. Ao salvar, chame a classe injetada 'PatientRepositoryInterface' para gravar no banco. Mostre mensagem de sucesso."></textarea>
                
                <div style="margin-top: 15px; text-align: right;">
                    <button id="btn-generate" class="btn" style="background: #2271b1; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">🧠 Gerar com I.A.</button>
                </div>
            </div>

            <div style="background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px;">
                <div style="background: #f6f7f7; padding: 10px 15px; border-bottom: 1px solid #c3c4c7; font-weight: 600; display: flex; justify-content: space-between;">
                    <span>Editor de Código (Gerado)</span>
                    <span style="font-size: 12px; font-weight: normal; color: #666;">Arquivo: Plugin.php</span>
                </div>
                <div style="padding: 0;">
                    <textarea id="ai-code-editor" style="width: 100%; height: 400px; padding: 15px; font-family: Consolas, monospace; border: none; font-size: 14px; background: #1e1e1e; color: #d4d4d4; resize: vertical;">
// O código gerado pela I.A. aparecerá aqui para você aprovar
// Todas as injeções usarão as Interfaces!
                    </textarea>
                </div>
                <div style="padding: 15px; background: #f6f7f7; border-top: 1px solid #c3c4c7; text-align: right;">
                    <button class="btn" style="background: #00a32a; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">✅ Implantar Plugin</button>
                </div>
            </div>

        </div>

        <!-- Lado Direito: Histórico / Contexto -->
        <div>
            <div style="background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; font-size: 14px;">Contratos Disponíveis (Injetáveis)</h3>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px; font-family: Consolas, monospace; color: #2271b1;">
                    <li style="margin-bottom: 5px;">PatientRepositoryInterface</li>
                    <li style="margin-bottom: 5px;">AppointmentRepositoryInterface</li>
                    <li style="margin-bottom: 5px;">EventDispatcher</li>
                    <li style="margin-bottom: 5px;">ThemeManager</li>
                    <li style="margin-bottom: 5px;">Router</li>
                </ul>
            </div>
            
            <div style="background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-radius: 4px; padding: 15px;">
                <h3 style="margin-top: 0; font-size: 14px;">Meus Plugins Gerados</h3>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                    <li style="color: #666; font-style: italic;">Nenhum plugin gerado ainda.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-generate').addEventListener('click', async function() {
    const promptText = document.getElementById('ai-prompt').value;
    const btn = this;
    const editor = document.getElementById('ai-code-editor');
    
    if (!promptText.trim()) {
        alert('Digite algo no prompt!');
        return;
    }

    btn.innerText = 'Gerando... (Aguarde)';
    btn.disabled = true;
    editor.value = '// I.A. está escrevendo o código...';

    try {
        const formData = new URLSearchParams();
        formData.append('prompt', promptText);

        const response = await fetch('<?= BASE_URL ?>/admin/ai-hub/builder/generate', {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });

        const data = await response.json();
        
        if (data.error) {
            editor.value = '// Erro: ' + data.error;
        } else {
            // Limpar markdown de blocos de código se a API devolver com ```php
            let code = data.code;
            if (code.startsWith('```php')) {
                code = code.replace(/^```php\n?/, '').replace(/\n?```$/, '');
            } else if (code.startsWith('```')) {
                code = code.replace(/^```\n?/, '').replace(/\n?```$/, '');
            }
            editor.value = code;
        }
    } catch (e) {
        editor.value = '// Falha na requisição: ' + e.message;
    } finally {
        btn.innerText = '🧠 Gerar com I.A.';
        btn.disabled = false;
    }
});
</script>
