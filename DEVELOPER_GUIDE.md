# Guia do Desenvolvedor: Domain System (v2.7.0)

> **Bem-vindo à área de desenvolvimento do Domain System!**  
> Este guia foi escrito de forma simples, prática e direta ao ponto para você aprender a **criar seus próprios plugins**, **desenvolver seus próprios temas visuais** e nos ajudar a **auditar e fortalecer o sistema**.

---

## 🧭 Sumário Rápido

1. [🤝 Como nos ajudar e colaborar](#-como-nos-ajudar-e-colaborar)
2. [🧩 Tutorial 1: Criando seu Primeiro Plugin do Zero](#-tutorial-1-criando-seu-primeiro-plugin-do-zero)
3. [🎨 Tutorial 2: Criando seu Primeiro Tema Visual do Zero](#-tutorial-2-criando-seu-primeiro-tema-visual-do-zero)
4. [🧪 Como Rodar e Criar Testes com PHPUnit](#-como-rodar-e-criar-testes-com-phpunit)
5. [🛡️ Boas Práticas de Código e Segurança](#️-boas-práticas-de-código-e-segurança)

---

## 🤝 Como nos ajudar e colaborar

O Domain System foi construído por um desenvolvedor autodidata que queria uma alternativa simples e ultraleve (~30 MB em disco e ~5 MB de RAM) para e-commerce, dropshipping e cursos online. 

Como programo a maior parte do tempo sozinho, utilizo modelos de Inteligência Artificial como revisores de bancada diários para varrer o código linha por linha caçando falhas. Mas **I.A. tem limites e nada substitui a experiência e o bom senso de desenvolvedores humanos reais**.

Você pode ajudar muito de várias formas:
- **Auditoria de Código e Segurança:** Vasculhe o repositório, tente quebrar o checkout, procure falhas de concorrência ou injeção e abra uma *Issue* ou *Pull Request*.
- **Simplificação de Arquitetura:** Nos ajude a manter o código desacoplado e limpo, sem cair na armadilha de criar camadas vazias de abstração onde o simples funciona melhor.
- **Novos Plugins e Temas:** Crie extensões úteis (novos meios de pagamento, fretes, blocos visuais) seguindo os tutoriais abaixo!

---

## 🧩 Tutorial 1: Criando seu Primeiro Plugin do Zero

Um plugin no Domain System é apenas uma pasta dentro de `src/Plugins/{nome_do_plugin}/`. Ele não precisa mexer em nenhuma linha do núcleo do sistema.

### O Atalho via Terminal:
Você pode gerar o esqueleto do seu plugin com um único comando:
```bash
php os-cli.php make:plugin meu_plugin
```

Se preferir criar na mão, você só precisa de **dois arquivos**:

```text
src/Plugins/meu_plugin/
├── plugin.json       # A certidão de nascimento do plugin
└── Plugin.php        # A lógica onde você registra suas rotas e eventos
```

---

### Passo 1: O arquivo `plugin.json`
Crie `src/Plugins/meu_plugin/plugin.json`:

```json
{
    "name": "meu_plugin",
    "version": "1.0.0",
    "description": "Meu primeiro plugin incrível para o Domain System",
    "author": "Seu Nome",
    "core": false,
    "os_version": "2.7"
}
```

---

### Passo 2: O arquivo `Plugin.php`
Crie `src/Plugins/meu_plugin/Plugin.php`. O ciclo de vida do plugin tem 3 momentos simples:

1. **`osRegister` (Registro):** Você avisa ao sistema quais serviços do núcleo você precisa (banco de dados, rotas, sessão) e quais eventos deseja escutar.
2. **`osBoot` (Inicialização):** Você registra suas rotas (`$router->addRoute`), menus no painel admin ou integrações.
3. **`activate` (Instalação):** Roda apenas na primeira vez que o plugin for ativado pelo admin (ótimo para criar tabelas no banco de dados).

```php
<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\meu_plugin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // 1. O que meu plugin precisa usar?
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db.schema'); // Preciso do banco de dados
        $os->listenHook('router.register'); // Preciso criar rotas
        $os->listenHook('admin.menu');       // Preciso adicionar um item no menu do admin
    }

    // 2. O plugin iniciando no carregamento da página
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // Registrando rotas web públicas
        $runtime->onHook('router.register', function (Router $router) {
            $router->addRoute('GET', '/ola-mundo', function (Request $request) {
                return new Response("<h1>Olá Mundo! Meu plugin está funcionando perfeitamente.</h1>");
            });
        });

        // Adicionando um botão no menu lateral do painel admin
        $runtime->onHook('admin.menu', function ($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Meu Plugin',
                    'url'   => '/ola-mundo',
                    'icon'  => '🚀',
                ];
            }
            return $menus;
        });
    }

    // 3. Executado quando você clica em "Ativar" no painel
    public function activate(OsRuntimeInterface $runtime): void
    {
        // Cria uma tabela simples no banco (funciona em MySQL e SQLite automaticamente)
        $schema = $runtime->getLink('core.db.schema');
        $schema->create('minha_tabela_exemplo', function ($table) {
            $table->id();
            $table->string('titulo');
            $table->timestamps();
        });
    }
}
```

Pronto! Ao salvar, acesse `http://localhost:8000/ola-mundo` no seu navegador e você verá sua página funcionando. No painel em `/admin/plugins`, seu plugin aparecerá com botão de ativar e desativar.

---

## 🎨 Tutorial 2: Criando seu Primeiro Tema Visual do Zero

Criar temas no Domain System é extremamente simples porque usamos **PHP limpo com HTML puro**. Você não precisa aprender sintaxes complicadas de templates como Twig ou Blade.

Os temas ficam na pasta `themes/{nome_do_tema}/` (ou em `src/Plugins/flextheme/themes/{nome_do_tema}/`).

### Estrutura básica de um tema:
```text
themes/meu_tema/
├── theme.json         # Nome e configurações do tema
├── layouts/
│   └── main.php       # A "casca" HTML completa (head, tags de SEO, etc.)
├── partials/
│   ├── header.php     # O cabeçalho (logo, menu de navegação)
│   └── footer.php     # O rodapé (direitos autorais, links de redes)
├── templates/
│   └── home.php       # O conteúdo que aparece na página inicial (/)
└── assets/            # CSS, JavaScript e imagens
    ├── css/style.css
    └── img/logo.svg
```

---

### Passo 1: O arquivo `theme.json`
Crie `themes/meu_tema/theme.json`:
```json
{
    "name": "Meu Tema Moderno",
    "slug": "meu_tema",
    "version": "1.0.0",
    "description": "Um tema limpo, rápido e responsivo",
    "author": "Seu Nome",
    "flex_builder": true
}
```
> **Dica de Ouro:** Se o seu tema tiver um design autoral fixo que você **não quer** que seja alterado pelo editor visual do Flex Builder, basta mudar para `"flex_builder": false`. O sistema vai respeitar seu design 100% isolado!

---

### Passo 2: O Layout Principal (`layouts/main.php`)
Este arquivo é o esqueleto HTML que envolve todas as páginas do site:
```php
<?php
declare(strict_types=1);

$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Resolve SEO dinamicamente para o Google indexar a página certa
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalUrl = 'https://meusite.com' . ($requestPath === '/' ? '' : $requestPath);

$seo->setCanonical($canonicalUrl)
    ->addMeta('theme-color', '#0f172a');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->generateTags() ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/themes/meu_tema/assets/css/style.css">
</head>
<body>

    <!-- Cabeçalho -->
    <?php require $themeDir . '/partials/header.php'; ?>

    <!-- O conteúdo da página atual entra aqui -->
    <main class="site-main">
        <?= $content ?>
    </main>

    <!-- Rodapé -->
    <?php require $themeDir . '/partials/footer.php'; ?>

</body>
</html>
```

---

### Passo 3: Cabeçalho e Rodapé (`partials/header.php` e `partials/footer.php`)

No arquivo `partials/header.php`:
```php
<?php $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<header class="site-header">
    <div class="container">
        <a href="<?= $baseUrl ?>/" class="site-logo">MeuSite</a>
        <nav class="site-nav">
            <a href="<?= $baseUrl ?>/">Início</a>
            <a href="<?= $baseUrl ?>/loja">Loja</a>
            <a href="<?= $baseUrl ?>/cursos">Cursos</a>
            <a href="<?= $baseUrl ?>/minha-conta">Minha Conta</a>
        </nav>
    </div>
</header>
```

No arquivo `partials/footer.php`:
```php
<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> MeuSite — Feito com Domain System.</p>
    </div>
</footer>
```

---

### Passo 4: A Página Inicial (`templates/home.php`)
O arquivo `templates/home.php` gera o conteúdo que será injetado dentro da variável `$content` do layout:

```php
<?php
// Inicia a captura do conteúdo da página
ob_start();
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>

<section class="hero-banner">
    <h1>Bem-vindo ao Meu Site!</h1>
    <p>Construído em PHP ultraleve com carregamento instantâneo.</p>
    <a href="<?= $baseUrl ?>/loja" class="btn-primary">Ver Produtos</a>
</section>

<?php
// Finaliza a captura e carrega o layout principal
$content = (string)ob_get_clean();
require $themeDir . '/layouts/main.php';
?>
```

---

### Passo 5: Ativando seu Tema
1. Acesse o painel administrativo em: `http://localhost:8000/admin/themes`
2. Seu tema **"Meu Tema Moderno"** aparecerá na lista de temas disponíveis.
3. Clique em **"Ativar Tema"**.
4. Pronto! Abra a página inicial e seu novo tema estará no ar!

---

## 🧪 Como Rodar e Criar Testes com PHPUnit

O Domain System leva testes automatizados muito a sério para garantir que nenhuma alteração quebre recursos existentes.

Para rodar toda a suíte de testes (mais de 650 testes):
```bash
php phpunit.phar
```

Para rodar apenas um arquivo de teste específico:
```bash
php phpunit.phar tests/Unit/CheckoutSecurityTest.php
```

### Criando um teste simples para seu plugin:
Crie um arquivo em `tests/Unit/MeuPluginTest.php`:
```php
<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MeuPluginTest extends TestCase
{
    public function testPluginFazCalculoCorreto(): void
    {
        $resultado = 10 + 15;
        $this->assertSame(25, $resultado);
    }
}
```

---

## 🛡️ Boas Práticas de Código e Segurança

Ao desenvolver plugins para o Domain System, siga estas regrinhas de ouro:

1. **Nunca use superglobais diretamente em controllers:**  
   Em vez de `$_POST['email']` ou `$_GET['id']`, use o objeto `$request->input('email')` e `$request->query('id')`.
2. **Sempre use Prepared Statements com PDO:**  
   Nunca concatene variáveis em consultas SQL (`"SELECT * WHERE id = " . $id`). Use interrogações ou parâmetros nomeados (`$pdo->prepare("SELECT * WHERE id = ?")`).
3. **Não traga dependências pesadas:**  
   O grande diferencial do Domain System é rodar em ~30 MB e gastar pouca memória. Evite instalar bibliotecas gigantes pelo Composer se uma função nativa do PHP resolver o problema.
4. **Trate falhas com elegância:**  
   Se você estiver se comunicando com uma API externa (como Correios ou gateway de pagamento), sempre use `try/catch` e registre erros em log, para que uma falha externa não trave o site do seu cliente.

---

## 💬 Ficou com alguma dúvida?
Se travar em qualquer ponto ao criar seu plugin ou tema, abra uma discussão no nosso GitHub ou envie um e-mail para `hadelrachid@gmail.com`. Vamos adorar ajudar você a construir sua ideia!
