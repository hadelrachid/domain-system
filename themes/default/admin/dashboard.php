<?php ob_start(); ?>

<h1>Bem-vindo ao Domain-System</h1>
<p>Este é o seu painel de controle central. A partir daqui você pode gerenciar a arquitetura do seu SaaS.</p>

<div style="display: flex; gap: 20px; margin-top: 30px;">
    <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; flex: 1;">
        <h3>Plugins Ativos</h3>
        <p>Acesse o gerenciador de plugins para expandir as funcionalidades do sistema instalando pacotes ZIP.</p>
        <a href="admin/plugins" class="btn btn-activate">Gerenciar Plugins</a>
    </div>
    
    <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; flex: 1;">
        <h3>Temas</h3>
        <p>Em breve: Gerenciamento visual da sua casca de apresentação.</p>
        <button class="btn btn-core" disabled>Em breve</button>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
