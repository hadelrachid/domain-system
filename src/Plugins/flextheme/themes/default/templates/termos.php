<div style="max-width: 900px; margin: 40px auto; padding: 40px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 12px; color: #cbd5e1; font-family: 'Inter', system-ui, sans-serif; line-height: 1.8; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <header style="text-align: center; border-bottom: 2px solid rgba(69, 243, 255, 0.3); padding-bottom: 20px; margin-bottom: 30px;">
        <h1 style="color: #45f3ff; font-size: 2.5rem; margin: 0; text-transform: uppercase; letter-spacing: 2px; text-shadow: 0 0 10px rgba(69, 243, 255, 0.3);">Termos de Uso</h1>
        <p style="color: #94a3b8; margin-top: 10px; font-size: 1.1rem;">Última atualização: <?= date('d/m/Y') ?></p>
    </header>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">1. Aceitação dos Termos</h2>
        <p>Ao acessar e utilizar os serviços do <strong><?= htmlspecialchars((defined('DOMAIN_SYSTEM_SITE_NAME') ? DOMAIN_SYSTEM_SITE_NAME : 'Domain System')) ?></strong>, você concorda em cumprir e sujeitar-se a estes Termos de Uso. Se você não concorda com qualquer parte destes termos, não deve utilizar nosso sistema.</p>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">2. Licença e Uso do Sistema</h2>
        <p>O Domain System concede a você uma licença limitada, não exclusiva e intransferível para utilizar os nossos serviços conforme as restrições detalhadas a seguir:</p>
        <ul style="list-style-type: square; padding-left: 40px; color: #94a3b8;">
            <li>Você não pode utilizar o sistema para fins ilícitos, fraudulentos ou maliciosos.</li>
            <li>Você não pode tentar realizar engenharia reversa, descompilar ou sobrecarregar a infraestrutura do Kernel (Ataques DDoS).</li>
            <li>O uso de bots ou automações sem autorização explícita via API (Webhooks) é estritamente proibido.</li>
        </ul>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">3. Responsabilidades do Usuário</h2>
        <p>Você é integralmente responsável por manter a confidencialidade das suas credenciais (inclusive tokens 2FA). Qualquer atividade realizada sob a sua conta é de sua inteira responsabilidade.</p>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">4. Isenção de Garantias</h2>
        <p>Apesar do nosso rigoroso padrão de resiliência e uso do <em>No-Break Shield</em>, o sistema é fornecido "no estado em que se encontra" (as is). Não garantimos que o serviço será ininterrupto, livre de erros ou totalmente imune a novas vulnerabilidades da web.</p>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">5. Modificações dos Termos</h2>
        <p>O Domain System reserva-se o direito de modificar estes Termos de Uso a qualquer momento. Notificaremos os usuários sobre mudanças significativas via e-mail ou aviso no painel de administração.</p>
    </section>
</div>
