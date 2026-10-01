<?php
require __DIR__ . '/../public/index.php';

$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$content = <<<HTML
<h2>Guia Definitivo: Hospedagem na Hostinger</h2>
<p>Se você adquiriu o <strong>Domain-System OS</strong>, sabe que a arquitetura SOLID garante que ele pode rodar em praticamente qualquer servidor moderno. Neste tutorial, focaremos na Hostinger por seu custo-benefício.</p>

<h3>1. Download do GitHub</h3>
<p>Acesse o repositório privado no GitHub e baixe a última versão estável:</p>
<pre><code style="display: block; background: #1a1a1d; padding: 15px; border-radius: 8px; color: #45f3ff; margin: 20px 0; font-family: monospace;">git clone https://github.com/hadelrachid/domain-system.git</code></pre>

<h3>2. Descompactando na Hostinger</h3>
<p>No painel HPanel da Hostinger:</p>
<ul>
    <li style="margin-bottom: 10px;">Acesse o Gerenciador de Arquivos.</li>
    <li style="margin-bottom: 10px;">Navegue até a pasta <code style="background: rgba(69, 243, 255, 0.1); padding: 2px 6px; border-radius: 4px;">public_html</code> do seu domínio.</li>
    <li style="margin-bottom: 10px;">Suba o arquivo <code>.zip</code> e clique em <strong>Extrair</strong>.</li>
</ul>

<h3>3. Apontamento de Segurança (Root vs Public)</h3>
<p>O Domain-System OS possui um roteador frontal super seguro que intercepta todas as requisições na pasta <code>/public</code>. Portanto, na configuração de hospedagem, certifique-se de que o <strong>Document Root</strong> aponta para:</p>
<pre><code style="display: block; background: #1a1a1d; padding: 15px; border-radius: 8px; color: #45f3ff; margin: 20px 0; font-family: monospace;">/domains/seusite.com/public_html/public</code></pre>

<p>Pronto! Acesse seu site e verá o sistema rodando na velocidade da luz.</p>
HTML;

$stmt = $pdo->prepare("INSERT INTO pages (slug, title, content) VALUES (:slug, :title, :content)
ON DUPLICATE KEY UPDATE title = :title, content = :content");
$stmt->execute([
    'slug' => 'tutoriais',
    'title' => 'Como Instalar na Hostinger',
    'content' => $content
]);

echo "Tutorial salvo no banco com sucesso!\n";
