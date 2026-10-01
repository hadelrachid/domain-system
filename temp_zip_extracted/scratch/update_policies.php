<?php
$app = require __DIR__ . '/../bootstrap.php';
$app->boot();
$container = $app->getContainer();
$db = $container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();

$privacy = <<<HTML
<div style="max-width: 800px; margin: 0 auto; line-height: 1.8; font-size: 1.05rem; padding-bottom: 50px;">
    <h1 style="color: var(--primary); font-size: 2.5rem; margin-bottom: 30px; letter-spacing: -1px;">Política de Privacidade</h1>
    
    <p>A sua privacidade é uma prioridade para o <strong>RachidD.com</strong>. Esta Política de Privacidade descreve como coletamos, usamos e protegemos as informações que você fornece ao utilizar nosso site, em total conformidade com a LGPD (Lei Geral de Proteção de Dados) e as Políticas e Diretrizes do Google.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">1. Coleta de Dados e Cookies</h2>
    <p>Nós utilizamos "cookies" (arquivos de texto armazenados no seu navegador) para melhorar a performance do site, registrar consentimentos (como a aceitação desta política) e analisar o tráfego.</p>
    <ul>
        <li><strong>Cookies Essenciais:</strong> Necessários para a navegação segura e o funcionamento da plataforma Domain-System OS.</li>
        <li><strong>Fornecedores de Terceiros (Google):</strong> Parceiros de publicidade e análise de tráfego, como o Google (Google Analytics e Google AdSense), usam cookies para veicular anúncios e métricas baseadas nas suas visitas anteriores ao nosso site ou a outros sites na internet.</li>
    </ul>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">2. Publicidade do Google (AdSense)</h2>
    <p>Para cumprir estritamente as Políticas do Google para Editores, informamos que:</p>
    <ul>
        <li>O uso de cookies de publicidade pelo Google permite que ele e seus parceiros veiculem anúncios para você com base na sua navegação pela web.</li>
        <li>Você pode desativar a publicidade personalizada acessando as <a href="https://www.google.com/settings/ads" target="_blank" style="color: var(--primary);">Configurações de Anúncios do Google</a>.</li>
        <li>Alternativamente, você pode desativar o uso de cookies de publicidade personalizada de terceiros acessando o <a href="https://www.aboutads.info/" target="_blank" style="color: var(--primary);">www.aboutads.info</a>.</li>
    </ul>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">3. Inteligência Artificial e Processamento</h2>
    <p>Parte do código, revisão e estruturação deste ecossistema pode ser assistida, gerada ou otimizada por sistemas de Inteligência Artificial Avançada (incluindo tecnologias desenvolvidas pelo Google / DeepMind). A AI atua estritamente como ferramenta de assistência arquitetural, e nenhum dado pessoal de visitantes é processado pela IA para fins de treinamento sem consentimento expresso.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">4. Compartilhamento de Dados</h2>
    <p>Não vendemos, alugamos ou transferimos seus dados pessoais fornecidos (como e-mail de newsletter) para terceiros comerciais. Os dados agregados de navegação só são compartilhados com as plataformas do Google (Analytics/Search Console) sob a finalidade de análise de desempenho da infraestrutura do site.</p>
    
    <p style="margin-top: 40px; font-size: 0.9rem; color: var(--text-muted);">Última atualização: 2026. Reservamo-nos o direito de atualizar esta política a qualquer momento.</p>
</div>
HTML;

$terms = <<<HTML
<div style="max-width: 800px; margin: 0 auto; line-height: 1.8; font-size: 1.05rem; padding-bottom: 50px;">
    <h1 style="color: var(--primary); font-size: 2.5rem; margin-bottom: 30px; letter-spacing: -1px;">Termos de Uso</h1>
    
    <p>Bem-vindo ao <strong>RachidD.com</strong>. Ao acessar e utilizar este site, você concorda expressamente com os presentes Termos e Condições de Uso. Caso não concorde com qualquer parte destes termos, solicitamos que não utilize nossa plataforma.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">1. Natureza Educacional</h2>
    <p>Todo o conteúdo publicado neste site, incluindo artigos técnicos, trechos de código, documentações do <em>Domain-System OS</em>, discussões sobre Arquitetura SOLID e Inteligência Artificial, possui caráter estritamente educacional e informativo.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">2. Propriedade Intelectual e Uso de IA</h2>
    <p>O conteúdo textual, a identidade visual e o código arquitetural apresentados pertencem ao autor, Rachid Daher. É reconhecido que o desenvolvimento, a otimização e a escrita técnica deste ecossistema utilizam, como co-piloto, sistemas avançados de Inteligência Artificial criados pelo Google. O uso, a reprodução e a distribuição do material open-source do <em>Domain-System</em> são regidos pelas respectivas licenças contidas no repositório GitHub.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">3. Limitação de Responsabilidade</h2>
    <p>O autor e seus provedores de hospedagem não se responsabilizam por quaisquer danos, perdas de dados ou vulnerabilidades em servidores de terceiros decorrentes da implementação, cópia ou adaptação dos códigos ensinados neste site. O uso de qualquer conhecimento técnico aqui adquirido é de sua inteira responsabilidade.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">4. Conformidade com Plataformas Externas</h2>
    <p>O usuário reconhece que o site se submete às Políticas do Google relacionadas à transparência na web, veiculação de anúncios seguros e responsabilidade na utilização de tecnologias de IA. É proibido o uso do conteúdo deste site para treinamento em massa de outras IAs ou extração de dados (scraping) comercial sem autorização.</p>

    <p style="margin-top: 40px; font-size: 0.9rem; color: var(--text-muted);">Última atualização: 2026.</p>
</div>
HTML;

$stmt = $db->prepare("UPDATE pages SET content = ? WHERE slug = 'privacidade'");
$stmt->execute([$privacy]);

$stmt2 = $db->prepare("UPDATE pages SET content = ? WHERE slug = 'termos'");
$stmt2->execute([$terms]);

echo "Políticas Google Compliance atualizadas com sucesso!\n";
