<section style="padding: 100px 0; text-align: center; position: relative;">
    <div style="display: inline-block; background: rgba(69, 243, 255, 0.1); color: var(--primary); padding: 0.5rem 1.2rem; border-radius: 50px; font-size: 0.8rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid rgba(69, 243, 255, 0.3); margin-bottom: 2rem; box-shadow: 0 0 15px rgba(69, 243, 255, 0.2);">
        Arquiteto de Software
    </div>
    
    <h1 style="color: #fff; font-size: 4rem; font-weight: 800; margin-bottom: 1.5rem; letter-spacing: -2px; line-height: 1.1; text-shadow: 0 0 30px rgba(255, 255, 255, 0.1);">
        Construindo a web com<br>
        <span class="text-primary" style="text-shadow: 0 0 40px rgba(69, 243, 255, 0.4);">Alta Performance</span>
    </h1>
    
    <p style="font-size: 1.25rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 3rem; line-height: 1.8;">
        Escrevo sobre PHP, Arquitetura SOLID e otimização extrema. Sou o criador do Domain-System OS, uma infraestrutura web modular pronta para o futuro.
    </p>
    
    <div style="display: flex; gap: 20px; justify-content: center;">
        <a href="#livro" class="btn btn-primary" data-track="livro_solid_hotmart" style="text-transform: uppercase; letter-spacing: 1px;">
            Meu Livro
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/p/tutoriais" class="btn btn-outline" data-track="livro_solid_amazon" style="text-transform: uppercase; letter-spacing: 1px;">
            Ler Tutoriais
        </a>
    </div>
</section>

<!-- Seção do Livro -->
<section id="livro" style="padding: 80px 0;">
    <div class="glass-panel grid-responsive" style="padding: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
        <div>
            <!-- Imagem Gerada Pela IA Entrará Aqui -->
            <div style="width: 100%; aspect-ratio: 3/4; background: rgba(0,0,0,0.5); border: 1px dashed rgba(69, 243, 255, 0.3); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/site-home/livro-solid.png" alt="Capa do Livro Arquitetura SOLID" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span style="display: none; color: var(--primary); font-family: 'Fira Code', monospace;">[Mockup do Livro Aqui]</span>
            </div>
        </div>
        <div>
            <h2 style="font-size: 2.5rem; color: #fff; font-weight: 800; margin-bottom: 20px; letter-spacing: -1px;">
                Entendendo<br>Arquitetura <span class="text-primary">SOLID</span>
            </h2>
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 30px; line-height: 1.8;">
                Domine os princípios que separam os amadores dos engenheiros de software. Aprenda a criar sistemas desacoplados, testáveis e altamente coesos utilizando PHP moderno.
            </p>
            <ul style="list-style: none; margin-bottom: 30px; display: flex; flex-direction: column; gap: 15px;">
                <li style="display: flex; align-items: center; gap: 15px;"><span class="text-primary">✓</span> Elimine o código espaguete</li>
                <li style="display: flex; align-items: center; gap: 15px;"><span class="text-primary">✓</span> Entenda a Inversão de Dependências</li>
                <li style="display: flex; align-items: center; gap: 15px;"><span class="text-primary">✓</span> 188 páginas de conhecimento prático</li>
            </ul>
            <div class="livro-buttons" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="https://www.amazon.com.br/dp/B0H74L5GFL" target="_blank" class="btn btn-outline" data-track="livro_solid_amazon">
                    Comprar na Amazon ➔
                </a>
                <a href="https://pay.hotmart.com/A107783240G?bid=1790643531521" target="_blank" class="btn btn-primary" data-track="livro_solid_hotmart">
                    Comprar na Hotmart ➔
                </a>
            </div>
        </div>
    </div>
</section>



