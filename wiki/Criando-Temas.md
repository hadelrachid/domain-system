# 🎨 Como Criar um Tema Visual no Domain System

> **Aprenda a construir um tema limpo, rápido e responsivo sem sintaxes complicadas.**  
> No Domain System usamos PHP puro com HTML: simples de manter e com carregamento instantâneo.

---

## Onde ficam os Temas?
Todos os temas visuais ficam dentro da pasta:
```text
themes/{nome_do_tema}/
```
*(ou em `src/Plugins/flextheme/themes/{nome_do_tema}/`)*

---

## 📁 Estrutura Obrigatória de um Tema

Um tema organizado precisa apenas destes arquivos e pastas:

```text
themes/meu_tema/
├── theme.json         # Manifesto com nome e autor do tema
├── layouts/
│   └── main.php       # Estrutura HTML completa (head, tags de SEO, etc.)
├── partials/
│   ├── header.php     # O topo (logo e menu de navegação)
│   └── footer.php     # O rodapé (links e direitos autorais)
├── templates/
│   └── home.php       # O conteúdo exibido na página inicial (/)
└── assets/            # Seus arquivos CSS, JavaScript e imagens
    ├── css/style.css
    └── img/logo.svg
```

---

## 🛠️ Passo a Passo Prático

### 1. Criando o `theme.json`
Crie o arquivo `themes/meu_tema/theme.json`:

```json
{
    "name": "Meu Tema Incrível",
    "slug": "meu_tema",
    "version": "1.0.0",
    "description": "Tema moderno, leve e 100% responsivo",
    "author": "Seu Nome",
    "flex_builder": true
}
```

> **Dica Importante:** Se você estiver criando um tema personalizado que **não deve** ter o cabeçalho e rodapé alterados pelo editor visual (como acontece com lojas personalizadas), basta definir `"flex_builder": false`. O sistema isola seu design automaticamente.

---

### 2. Criando o Layout Principal (`layouts/main.php`)
O arquivo `layouts/main.php` é a casca HTML que abraça todas as páginas do site:

```php
<?php
declare(strict_types=1);

$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Resolve a URL Canônica dinamicamente para o SEO do Google
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
    
    <!-- Gera automaticamente todas as meta tags de título, descrição e OpenGraph -->
    <?= $seo->generateTags() ?>

    <link rel="stylesheet" href="<?= $baseUrl ?>/themes/meu_tema/assets/css/style.css">
</head>
<body>

    <!-- Inclui o Cabeçalho -->
    <?php require $themeDir . '/partials/header.php'; ?>

    <!-- O conteúdo da página atual é injetado aqui -->
    <main class="site-main">
        <?= $content ?>
    </main>

    <!-- Inclui o Rodapé -->
    <?php require $themeDir . '/partials/footer.php'; ?>

</body>
</html>
```

---

### 3. Criando o Cabeçalho (`partials/header.php`) e Rodapé (`partials/footer.php`)

No arquivo `partials/header.php`:
```php
<?php $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
<header class="site-header">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 15px;">
        <a href="<?= $baseUrl ?>/" class="site-logo" style="font-size: 20px; font-weight: bold; text-decoration: none; color: #fff;">
            MeuSite
        </a>
        <nav class="site-nav" style="display: flex; gap: 15px;">
            <a href="<?= $baseUrl ?>/" style="color: #cbd5e1; text-decoration: none;">Início</a>
            <a href="<?= $baseUrl ?>/loja" style="color: #cbd5e1; text-decoration: none;">Loja</a>
            <a href="<?= $baseUrl ?>/cursos" style="color: #cbd5e1; text-decoration: none;">Cursos</a>
            <a href="<?= $baseUrl ?>/minha-conta" style="color: #cbd5e1; text-decoration: none;">Minha Conta</a>
        </nav>
    </div>
</header>
```

No arquivo `partials/footer.php`:
```php
<footer class="site-footer" style="padding: 30px 15px; text-align: center; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="container">
        <p>&copy; <?= date('Y') ?> MeuSite. Todos os direitos reservados. Feito com Domain System.</p>
    </div>
</footer>
```

---

### 4. Criando a Página Inicial (`templates/home.php`)
O arquivo `templates/home.php` utiliza `ob_start()` para capturar o conteúdo HTML e repassá-lo para a variável `$content` do layout:

```php
<?php
// Inicia a captura do conteúdo
ob_start();
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>

<section class="hero-section" style="padding: 60px 15px; text-align: center;">
    <h1 style="font-size: 36px; margin-bottom: 15px; color: #fff;">Bem-vindo ao Meu Site!</h1>
    <p style="font-size: 18px; color: #94a3b8; max-width: 600px; margin: 0 auto 25px;">
        Este site está rodando em um sistema moderno de apenas 30 MB, ultrarrápido e sem dependências pesadas.
    </p>
    <a href="<?= $baseUrl ?>/loja" style="background: #38bdf8; color: #0f172a; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;">
        Ver Catálogo
    </a>
</section>

<?php
// Finaliza a captura e carrega o layout
$content = (string)ob_get_clean();
require $themeDir . '/layouts/main.php';
?>
```

---

### 5. Ativando o Tema no Painel
1. Acesse o painel administrativo em: `http://localhost:8000/admin/themes`
2. Você verá o card do seu novo tema **"Meu Tema Incrível"**.
3. Clique em **"Ativar Tema"**.
4. Acesse a raiz do seu site (`/`) e visualize seu novo tema rodando instantaneamente!

---

## 📚 Veja Também
- [Como Criar um Plugin do Zero](Criando-Plugins.md)
- [Voltar para a Página Principal da Wiki](Home.md)
