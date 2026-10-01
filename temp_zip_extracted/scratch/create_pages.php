<?php
$app = require __DIR__ . '/../bootstrap.php';
$app->boot(); // Inicializa os módulos, incluindo o Database
$container = $app->getContainer();
$db = $container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();

$pages = [
    [
        'title' => 'Sobre',
        'slug' => 'sobre',
        'content' => '<h1>Sobre o RachidD</h1><p>Sou Rachid Daher, Desenvolvedor Backend Especialista em PHP, Arquiteto de Software e apaixonado por criar sistemas resilientes baseados no SOLID e em Padrões de Projeto (Design Patterns).</p><p>Minha missão é elevar a qualidade do ecossistema PHP, ensinando a construir aplicações seguras, modulares e preparadas para a era da Inteligência Artificial.</p>'
    ],
    [
        'title' => 'Termos de Uso',
        'slug' => 'termos',
        'content' => '<h1>Termos de Uso</h1><p>Bem-vindo ao RachidD.com.</p><p>Ao acessar e utilizar este site, você concorda com os presentes Termos de Uso. Todo o conteúdo publicado aqui (artigos, documentação e códigos) possui caráter educacional e informativo.</p><p>O uso indevido de qualquer material ou sistema disponibilizado neste site é de inteira responsabilidade do usuário.</p>'
    ],
    [
        'title' => 'Política de Privacidade',
        'slug' => 'privacidade',
        'content' => '<h1>Política de Privacidade</h1><p>Sua privacidade é levada a sério no RachidD.com.</p><p><strong>1. Coleta de Dados:</strong> Não coletamos dados pessoais sensíveis. Armazenamos apenas os dados essenciais fornecidos voluntariamente via formulários de contato ou newsletter.</p><p><strong>2. Cookies:</strong> Utilizamos apenas os cookies estritamente necessários para o funcionamento correto do site (como preferências de idioma e controle de sessão), sem ferramentas agressivas de rastreamento de terceiros.</p><p><strong>3. Compartilhamento:</strong> Seus dados jamais serão vendidos ou compartilhados com terceiros.</p>'
    ]
];

$stmt = $db->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, ?)");

foreach ($pages as $p) {
    // Check if exists
    $check = $db->prepare("SELECT id FROM pages WHERE slug = ?");
    $check->execute([$p['slug']]);
    if (!$check->fetch()) {
        $stmt->execute([
            $p['title'],
            $p['slug'],
            $p['content']
        ]);
        echo "Página '{$p['title']}' inserida com sucesso!\n";
    } else {
        echo "Página '{$p['title']}' já existe.\n";
    }
}
