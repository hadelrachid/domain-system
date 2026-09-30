<?php
require __DIR__ . '/../bootstrap.php';

$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Usaremos a constante BASE_URL no link para que funcione perfeitamente
$content = <<<HTML
<div style="text-align: center; margin-bottom: 50px;">
    <h2 style="font-size: 2.5rem; color: #fff; margin-bottom: 15px;">Central de <span class="text-primary">Conhecimento</span></h2>
    <p style="color: var(--text-muted); font-size: 1.2rem;">Escolha uma trilha abaixo para acessar conteúdos, vídeos, apostilas e tutoriais exclusivos.</p>
</div>

<div class="grid-responsive" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
    
    <!-- Pasta: Domain-System -->
    <a href="[base_url]/docs/index" style="text-decoration: none;">
        <div class="glass-panel" style="padding: 30px; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(69,243,255,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="font-size: 3rem; margin-bottom: 15px;">⚙️</div>
            <h3 style="color: #fff; margin-bottom: 10px;">Domain-System OS</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Como instalar, criar plugins, usar o Router e dominar a arquitetura.</p>
        </div>
    </a>

    <!-- Pasta: PHP -->
    <div class="glass-panel" style="padding: 30px; text-align: center; opacity: 0.5; position: relative;">
        <div style="position: absolute; top: 15px; right: 15px; background: rgba(255, 255, 255, 0.1); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; letter-spacing: 1px;">EM BREVE</div>
        <div style="font-size: 3rem; margin-bottom: 15px; filter: grayscale(100%);">🐘</div>
        <h3 style="color: #fff; margin-bottom: 10px;">Curso de PHP</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Do básico ao avançado. Orientação a Objetos, Design Patterns e SOLID.</p>
    </div>

    <!-- Pasta: JavaScript -->
    <div class="glass-panel" style="padding: 30px; text-align: center; opacity: 0.5; position: relative;">
        <div style="position: absolute; top: 15px; right: 15px; background: rgba(255, 255, 255, 0.1); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; letter-spacing: 1px;">EM BREVE</div>
        <div style="font-size: 3rem; margin-bottom: 15px; filter: grayscale(100%);">⚡</div>
        <h3 style="color: #fff; margin-bottom: 10px;">Curso de JavaScript</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Domine o frontend e o backend com JS, manipulação de DOM e APIs.</p>
    </div>

    <!-- Pasta: C/C++ -->
    <div class="glass-panel" style="padding: 30px; text-align: center; opacity: 0.5; position: relative;">
        <div style="position: absolute; top: 15px; right: 15px; background: rgba(255, 255, 255, 0.1); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; letter-spacing: 1px;">EM BREVE</div>
        <div style="font-size: 3rem; margin-bottom: 15px; filter: grayscale(100%);">🛠️</div>
        <h3 style="color: #fff; margin-bottom: 10px;">Curso de C/C++</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Fundamentos de memória, ponteiros, e alta performance em C++.</p>
    </div>

</div>
HTML;

$stmt = $pdo->prepare("UPDATE pages SET content = :content WHERE slug = 'tutoriais'");
$stmt->execute([
    'content' => $content
]);

echo "Card de Tutoriais atualizado com o link para a documentação!\n";
