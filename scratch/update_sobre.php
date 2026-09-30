<?php
$app = require __DIR__ . '/../bootstrap.php';
$app->boot();
$container = $app->getContainer();
$db = $container->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();

$content = <<<HTML
<div style="max-width: 800px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem;">
    <h1 style="color: var(--primary); font-size: 2.5rem; margin-bottom: 30px; letter-spacing: -1px;">Sobre Rachid Daher</h1>
    
    <p>Sou Rachid Daher, Desenvolvedor Backend Especialista em PHP, Arquiteto de Software e apaixonado por criar sistemas resilientes baseados no <strong>SOLID</strong> e em <strong>Padrões de Projeto</strong> (Design Patterns).</p>
    
    <p>Minha missão é elevar a qualidade do ecossistema PHP, ensinando a construir aplicações seguras, modulares e preparadas para a era da Inteligência Artificial. Acredito que o código não deve ser apenas funcional, mas também uma obra de engenharia limpa e sustentável a longo prazo.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">A Criação do Domain-System OS</h2>
    <p>A constante busca por um sistema que fosse leve, livre de "códigos espaguete" e protegido contra falhas em cascata me levou a criar o <strong>Domain-System OS</strong>.</p>
    <p>O Domain-System não é apenas um CMS; é um Kernel PHP Moderno, Modular e Orientado a Eventos. Ele introduz a arquitetura do <em>Ciclo de Confiança Verificável</em> e o mecanismo do <em>Circuit Breaker (No-Break Shield)</em>, onde o núcleo do sistema é blindado e os plugins atuam de forma isolada, impedindo que erros de terceiros derrubem a sua aplicação.</p>

    <h2 style="color: #fff; margin-top: 40px; border-bottom: 1px solid rgba(69, 243, 255, 0.2); padding-bottom: 10px;">O Livro</h2>
    <div style="display: flex; gap: 20px; align-items: flex-start; margin-top: 20px; background: rgba(31, 40, 51, 0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(69, 243, 255, 0.1);">
        <div style="flex: 1;">
            <h3 style="color: var(--primary); margin-top: 0;">Entendendo Arquitetura SOLID em PHP</h3>
            <p style="font-size: 1rem;">Decidi compilar anos de estudo, erros e acertos em um material definitivo. Meu livro foi escrito para o desenvolvedor que está cansado de refatorar código quebrado e deseja alcançar o próximo nível na engenharia de software.</p>
            <p style="font-size: 1rem;">Nele, desmonto os mitos do desenvolvimento PHP e ensino, na prática, como aplicar os 5 princípios do SOLID e os Padrões de Projeto para criar sistemas que não envelhecem.</p>
        </div>
    </div>
</div>
HTML;

$stmt = $db->prepare("UPDATE pages SET content = ? WHERE slug = 'sobre'");
$stmt->execute([$content]);

echo "Página 'Sobre' atualizada com sucesso!\n";
