<div style="max-width: 900px; margin: 40px auto; padding: 40px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 12px; color: #cbd5e1; font-family: 'Inter', system-ui, sans-serif; line-height: 1.8; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <header style="text-align: center; border-bottom: 2px solid rgba(69, 243, 255, 0.3); padding-bottom: 20px; margin-bottom: 30px;">
        <h1 style="color: #45f3ff; font-size: 2.5rem; margin: 0; text-transform: uppercase; letter-spacing: 2px; text-shadow: 0 0 10px rgba(69, 243, 255, 0.3);">Política de Privacidade</h1>
        <p style="color: #94a3b8; margin-top: 10px; font-size: 1.1rem;">Última atualização: <?= date('d/m/Y') ?></p>
    </header>

    <div style="background: rgba(69, 243, 255, 0.05); padding: 15px; border-radius: 8px; border-left: 4px solid #45f3ff; margin-bottom: 30px; font-size: 0.95rem;">
        Esta política de privacidade foi elaborada em conformidade com as diretrizes do Google, a Lei Geral de Proteção de Dados (LGPD) e o Regulamento Geral sobre a Proteção de Dados (GDPR).
    </div>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">1. Coleta de Informações</h2>
        <p>Ao utilizar o <strong><?= htmlspecialchars((defined('DOMAIN_SYSTEM_SITE_NAME') ? DOMAIN_SYSTEM_SITE_NAME : 'Domain System')) ?></strong>, nós coletamos os seguintes tipos de informações:</p>
        <ul style="list-style-type: square; padding-left: 40px; color: #94a3b8;">
            <li><strong>Dados Pessoais:</strong> Nome, endereço de e-mail e informações de perfil fornecidas durante o cadastro.</li>
            <li><strong>Dados de Navegação e Telemetria:</strong> Endereços de IP, tipo de navegador, sistema operacional e logs de erro do <em>No-Break Shield</em>, utilizados estritamente para manter a estabilidade do sistema.</li>
            <li><strong>Cookies e Rastreamento:</strong> Utilizamos cookies funcionais essenciais para manter a sua sessão (Sessão Global e Tokens CSRF) e cookies analíticos (caso integrados a plataformas de terceiros, como Google Analytics).</li>
        </ul>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">2. Uso das Informações</h2>
        <p>As informações coletadas são utilizadas exclusivamente para:</p>
        <ul style="list-style-type: square; padding-left: 40px; color: #94a3b8;">
            <li>Fornecer, operar e manter os serviços do sistema web;</li>
            <li>Melhorar e personalizar a experiência do usuário (incluindo as preferências de Temas Cyberpunk);</li>
            <li>Analisar o uso da plataforma para otimização de performance e detecção de anomalias no Kernel;</li>
            <li>Garantir a segurança global do sistema (bloqueio de ataques e emissão de alertas 2FA).</li>
        </ul>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">3. Compartilhamento de Dados</h2>
        <p>Nós <strong>não vendemos, alugamos ou comercializamos</strong> seus dados pessoais para terceiros. O compartilhamento só ocorre nos seguintes casos:</p>
        <ul style="list-style-type: square; padding-left: 40px; color: #94a3b8;">
            <li>Com provedores de serviços confiáveis que auxiliam na operação da infraestrutura (ex: provedores de nuvem).</li>
            <li>Quando exigido por lei ou para responder a processos legais.</li>
            <li>Para proteger os direitos, a propriedade e a segurança do Domain System ou do público.</li>
        </ul>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">4. Seus Direitos (LGPD / GDPR)</h2>
        <p>Você tem total controle sobre os seus dados. É o seu direito:</p>
        <ul style="list-style-type: square; padding-left: 40px; color: #94a3b8;">
            <li>Solicitar o acesso a uma cópia completa dos seus dados armazenados em nossos bancos de dados.</li>
            <li>Solicitar a correção de dados incompletos ou imprecisos.</li>
            <li>Solicitar a exclusão ("Direito ao Esquecimento") dos seus dados pessoais.</li>
            <li>Revogar o seu consentimento a qualquer momento.</li>
        </ul>
        <p>Para exercer seus direitos, entre em contato através da tela de suporte do painel de administração.</p>
    </section>

    <section style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 1.5rem; border-left: 4px solid #45f3ff; padding-left: 15px;">5. Segurança dos Dados</h2>
        <p>Implementamos rigorosas medidas técnicas de nível Enterprise para proteger suas informações. Nosso Kernel utiliza <strong>Proteção Global CSRF</strong>, Criptografia robusta para senhas e rotinas de isolamento automático de erros para prevenir vazamento de memória e acessos indevidos.</p>
    </section>

</div>
