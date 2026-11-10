<?php $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<footer style="background: #06090e; border-top: 1px solid rgba(212, 175, 55, 0.2); padding: 60px 0 30px; margin-top: 80px; color: #94a3b8; font-size: 13px;">
    <div class="container">
        <!-- Footer Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 40px; margin-bottom: 50px;">
            
            <!-- Col 1: Brand & Slogan -->
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    <img src="<?= $baseUrl ?>/assets/img/site-home/logo-lampada.svg" alt="RachidD" style="height: 42px; width: auto; filter: drop-shadow(0 0 10px rgba(212,175,55,0.4));">
                    <span style="font-size: 1.4rem; font-weight: 800; color: #fff;">Rachid<span style="color: #F0D060;">D.com</span></span>
                </div>
                <p style="line-height: 1.6; margin-bottom: 20px;">
                    O seu Gênio das Ofertas Internacionais. Conectamos você diretamente às fábricas e tendências mundiais com curadoria rigorosa, envio rastreado e suporte dedicado.
                </p>
                <div style="color: #D4AF37; font-weight: 700;">
                    ✦ Atendimento: contato@rachidd.com
                </div>
            </div>

            <!-- Col 2: Navegação Rápida -->
            <div>
                <h4 style="color: #fff; font-size: 15px; font-weight: 700; margin: 0 0 16px 0; text-transform: uppercase; letter-spacing: 0.5px;">Atendimento ao Cliente</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <li><a href="<?= $baseUrl ?>/loja" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Rastrear Meu Pedido</a></li>
                    <li><a href="<?= $baseUrl ?>/termos" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Termos de Uso & Compra</a></li>
                    <li><a href="<?= $baseUrl ?>/privacidade" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Política de Privacidade</a></li>
                    <li><a href="<?= $baseUrl ?>/loja" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Política de Envio & Prazos</a></li>
                    <li><a href="<?= $baseUrl ?>/sobre" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Sobre a RachidD.com</a></li>
                </ul>
            </div>

            <!-- Col 3: Categorias em Alta -->
            <div>
                <h4 style="color: #fff; font-size: 15px; font-weight: 700; margin: 0 0 16px 0; text-transform: uppercase; letter-spacing: 0.5px;">Tendências Globais</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <li><a href="<?= $baseUrl ?>/loja?cat=Smartwatches" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Smartwatches & Pulseiras</a></li>
                    <li><a href="<?= $baseUrl ?>/loja?cat=Audio" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Fones Bluetooth TWS</a></li>
                    <li><a href="<?= $baseUrl ?>/loja?cat=Casa" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Casa & Organização Inteligente</a></li>
                    <li><a href="<?= $baseUrl ?>/loja?cat=Tecnologia" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F0D060'" onmouseout="this.style.color='#94a3b8'">Acessórios para Celular</a></li>
                    <li><a href="<?= $baseUrl ?>/loja" style="color: #F0D060; text-decoration: none; font-weight: 600;">Ver Catálogo Completo ➔</a></li>
                </ul>
            </div>

            <!-- Col 4: Selos de Segurança e Formas de Pagamento -->
            <div>
                <h4 style="color: #fff; font-size: 15px; font-weight: 700; margin: 0 0 16px 0; text-transform: uppercase; letter-spacing: 0.5px;">Pagamento Seguro & Blindado</h4>
                <p style="font-size: 12px; margin-bottom: 14px; line-height: 1.5;">
                    Ambiente criptografado com certificados de segurança de nível bancário:
                </p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;">
                    <span style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #5cd67d;">PIX Instantâneo</span>
                    <span style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #60a5fa;">Mercado Pago</span>
                    <span style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #38bdf8;">PayPal Global</span>
                    <span style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #fff;">Cartões de Crédito (12x)</span>
                </div>
                <div style="font-size: 11px; color: #D4AF37;">
                    🛡️ Compra 100% Protegida ou Seu Dinheiro de Volta
                </div>
            </div>
        </div>

        <!-- Bottom Line -->
        <div style="padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.08); text-align: center; font-size: 12px; color: #64748b;">
            &copy; <?= date('Y') ?> RachidD.com. Todos os direitos reservados. Operado com ultraperformance pelo Domain System OS.
        </div>
    </div>
</footer>
