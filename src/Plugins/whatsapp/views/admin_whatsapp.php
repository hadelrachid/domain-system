<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#25d366" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
        Integração WhatsApp (Z-API)
    </h1>
    <p>Configure as credenciais da sua instância Z-API para disparar mensagens automáticas.</p>

    <?php if (!empty($_SESSION['success_msg'])): ?>
        <div style="background: #dcfce3; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($_SESSION['success_msg']) ?>
            <?php unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_msg'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($_SESSION['error_msg']) ?>
            <?php unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; margin-top: 30px; flex-wrap: wrap;">
        
        <!-- Bloco de Configuração -->
        <div style="background: #fff; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; flex: 2; min-width: 300px;">
            <h2 style="margin-top: 0; font-size: 1.2rem; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Credenciais da API</h2>
            
            <form action="admin/whatsapp/save" method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #475569;">Instance ID</label>
                    <input type="text" name="zapi_instance" value="<?= htmlspecialchars($settings['instance'] ?? '') ?>" placeholder="Ex: 3B9A8C7F9D9F8E7E9F8E7E9F8" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #475569;">Token de Segurança</label>
                    <input type="password" name="zapi_token" value="<?= htmlspecialchars($settings['token'] ?? '') ?>" placeholder="Coloque seu token da Z-API aqui" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                </div>

                <button type="submit" class="btn" style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">Salvar Configurações</button>
            </form>
        </div>

        <!-- Bloco de Teste -->
        <div style="background: #fff; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; flex: 1; min-width: 300px;">
            <h2 style="margin-top: 0; font-size: 1.2rem; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Disparo de Teste</h2>
            <p style="color: #64748b; font-size: 0.9rem;">Envie uma mensagem de teste para verificar se a conexão está funcionando.</p>
            
            <form action="admin/whatsapp/test" method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #475569;">Telefone (com DDD)</label>
                    <input type="text" name="test_phone" placeholder="Ex: 5511999999999" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                </div>

                <button type="submit" class="btn" style="background: #25d366; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; justify-content: center; width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Enviar Mensagem de Teste
                </button>
            </form>
        </div>
        
    </div>
</div>
