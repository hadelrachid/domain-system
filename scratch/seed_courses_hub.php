<?php
require __DIR__ . '/../public/index.php';

$pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$content = <<<HTML
<div style="text-align: center; margin-bottom: 50px;">
    <h2 style="font-size: 2.5rem; color: #fff; margin-bottom: 15px;">Central de <span class="text-primary">Conhecimento</span></h2>
    <p style="color: var(--text-muted); font-size: 1.2rem;">Escolha uma trilha abaixo para acessar conteúdos, vídeos, apostilas e tutoriais exclusivos.</p>
</div>

<div class="grid-responsive" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
    
    <!-- Pasta: Domain-System -->
    <a href="#" style="text-decoration: none;">
        <div class="glass-panel" style="padding: 30px; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(69,243,255,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="font-size: 3rem; margin-bottom: 15px;">⚙️</div>
            <h3 style="color: #fff; margin-bottom: 10px;">Domain-System OS</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Como instalar, criar plugins, usar o Router e dominar a arquitetura.</p>
        </div>
    </a>

    <!-- Pasta: PHP -->
    <a href="#" style="text-decoration: none;">
        <div class="glass-panel" style="padding: 30px; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(69,243,255,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="font-size: 3rem; margin-bottom: 15px;">🐘</div>
            <h3 style="color: #fff; margin-bottom: 10px;">Curso de PHP</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Do básico ao avançado. Orientação a Objetos, Design Patterns e SOLID.</p>
        </div>
    </a>

    <!-- Pasta: JavaScript -->
    <a href="#" style="text-decoration: none;">
        <div class="glass-panel" style="padding: 30px; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(69,243,255,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="font-size: 3rem; margin-bottom: 15px;">⚡</div>
            <h3 style="color: #fff; margin-bottom: 10px;">Curso de JavaScript</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Domine o frontend e o backend com JS, manipulação de DOM e APIs.</p>
        </div>
    </a>

    <!-- Pasta: C/C++ -->
    <a href="#" style="text-decoration: none;">
        <div class="glass-panel" style="padding: 30px; text-align: center; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(69,243,255,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="font-size: 3rem; margin-bottom: 15px;">🛠️</div>
            <h3 style="color: #fff; margin-bottom: 10px;">Curso de C/C++</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Fundamentos de memória, ponteiros, e alta performance em C++.</p>
        </div>
    </a>

</div>
HTML;

$stmt = $pdo->prepare("UPDATE pages SET title = :title, content = :content WHERE slug = 'tutoriais'");
$stmt->execute([
    'title' => 'Tutoriais & Cursos',
    'content' => $content
]);

echo "Página de Tutoriais atualizada com sucesso para um Menu de Cursos!\n";
