# Guia do Desenvolvedor: Como criar Plugins e Temas

O **Domain System** é um Framework Universal (CMS) onde tudo é extensível. O sistema de clínica médica incluído por padrão é apenas um exemplo do que a plataforma pode rodar. 

Ele foi desenhado para ser infinitamente extensível, mantendo uma clara separação entre **Lógica (Plugins)** e **Apresentação (Temas)**.

Este guia prático ensinará você a construir aplicações robustas, de qualquer nicho de mercado, sem nunca precisar tocar no Kernel (Core) do sistema.

---

## 🧩 Como criar um Plugin

Um Plugin é onde toda a sua Regra de Negócios e acesso ao Banco de Dados devem morar.

### 1. Estrutura de Pastas
Para criar um novo plugin, crie uma pasta dentro de `src/Plugins/` com o nome do seu plugin (ex: `patients`).

```text
src/Plugins/patients/
├── plugin.json       # Metadados essenciais
├── Plugin.php        # A classe principal de Inicialização
└── Controllers/      # (Opcional) Seus controladores
```

### 2. O Arquivo `plugin.json`
Este arquivo diz ao sistema como carregar o seu plugin. Ele é obrigatório.

```json
{
    "name": "patients",
    "version": "1.0.0",
    "description": "Módulo de gestão de pacientes",
    "dependencies": ["auth", "database", "system-admin"],
    "namespace": "DomainSystem\\Plugins\\Patients\\"
}
```

### 3. A Classe `Plugin.php`
Todo plugin deve ter uma classe `Plugin` que estende `AbstractPlugin`. Ela deve implementar o método `register()`.

```php
<?php
namespace DomainSystem\Plugins\Patients;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // 1. Escute o evento de roteamento para adicionar suas URLs
        $this->dispatcher->addListener('router.register', function(Router $router) {
            
            // Defina uma rota e aponte para um Controller que você irá criar
            $router->get('/admin/patients', [PatientController::class, 'index']);
            
        });

        // 2. Se precisar de Banco de Dados, crie suas tabelas aqui:
        $db = $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL
            )
        ");
    }
}
```

### 4. Boas Práticas para Plugins
- **Nunca gere HTML (echo, \<html>) dentro do Plugin.** Use o `ThemeManager` para repassar os dados para o tema ativo.
- **Injeção de Dependências:** Peça dependências no construtor dos seus Controllers, o `Container` do Kernel irá injetá-las magicamente para você.

---

## 🎨 Como criar Telas (Temas)

Temas vivem na pasta `themes/`. O tema padrão atual é o `default`. 
Os temas apenas recebem os dados vindos dos Plugins e exibem na tela.

### 1. Chamando uma View pelo Plugin
Dentro do seu `PatientController`, você fará o seguinte:

```php
<?php
// ... namespace e uses ...
use DomainSystem\Core\Theme\ThemeManager;

class PatientController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function index()
    {
        // Pega dados do banco usando o Plugin Database
        $patients = [...]; 
        
        // Renderiza a view 'admin/patients/index' enviando os dados
        return $this->theme->render('admin/patients/index', ['pacientes' => $patients]);
    }
}
```

### 2. Criando o Arquivo HTML no Tema
Como você chamou `admin/patients/index`, o `ThemeManager` procurará o arquivo físico em:
`themes/default/admin/patients/index.php`

```php
<!-- themes/default/admin/patients/index.php -->
<?= $this->getHeader() ?> <!-- Traz o topo do site (navbar, css) -->

<div class="container">
    <h1>Lista de Pacientes</h1>
    
    <ul>
        <?php foreach ($pacientes as $paciente): ?>
            <li><?= htmlspecialchars($paciente['name']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?= $this->getFooter() ?> <!-- Traz o rodapé (scripts de fechamento) -->
```

---

## 🛡️ Tratamento Amigável de Erros

O Kernel possui proteção Anti-Crash no carregamento (`boot`).
Se o seu `Plugin.php` quebrar por algum motivo (um erro de sintaxe ou erro no Banco), o Kernel irá:
1. Capturar o Erro Fatal (Throw).
2. Desativar o plugin defeituoso automaticamente.
3. Gravar um Log amigável no Servidor Apache (`error.log`).
4. Continuar carregando o resto do sistema normalmente (O site não sai do ar!).

**Regra de Ouro:** Lance Exceções (`throw new Exception("Mensagem")`) sempre que o seu Plugin encontrar um cenário impossível de continuar. O Kernel cuida do resto!
