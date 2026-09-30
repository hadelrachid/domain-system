# Desenvolvimento de Temas (Domain-System OS)

O **Domain-System OS** adota um padrão arquitetural estrito (Design Pattern) para a criação de temas, focado em performance (SEO), componentização e separação de conceitos.

## 1. Estrutura de Diretórios
Todo tema deve ser criado dentro de `src/Plugins/flextheme/themes/{nome_do_tema}/`.

A estrutura obrigatória é:
```text
meu_tema/
├── theme.json           # Manifesto do tema (Nome, versão, autor)
├── layouts/             # Cascas HTML (ex: main.php com <html>, <head>, <body>)
├── partials/            # Componentes reutilizáveis (ex: header.php, footer.php)
├── templates/           # As páginas finais (ex: home.php, single.php, 404.php)
└── assets/              # Arquivos estáticos puros
    ├── css/
    ├── js/
    └── img/
```

## 2. O Motor de SEO (Performance Extrema)
O OS intercepta toda a saída do tema e passa por um **HtmlOptimizer**.
- **Não** se preocupe em minificar seu HTML manualmente. O motor fará isso em tempo de execução.
- Para CSS e JS, futuramente o OS fornecerá funções `enqueue_css()` e `enqueue_js()` que agregarão todos os arquivos em um único pacote minificado (cache busting).

## 3. Como criar uma Página (Template)
O arquivo `templates/home.php` é renderizado automaticamente pela rota `/`.
Para herdar o layout principal, você deve usar *Output Buffering*:

```php
<?php ob_start(); ?>

<section class="hero">
    <h1>Bem vindo ao meu site</h1>
</section>

<?php 
// Pega o conteúdo gerado acima e injeta na variável $content do layout
$content = ob_get_clean(); 
require $themeDir . '/layouts/main.php'; 
?>
```

## 4. O Layout Principal (Layouts)
O arquivo `layouts/main.php` recebe as variáveis do sistema, como o `$seo` (SeoManagerInterface).

```php
<?php
// Configurando o SEO dinamicamente
$seo->setTitle('Meu Site Incrível')
    ->setDescription('A melhor plataforma.')
    ->setCanonical('https://meusite.com');
?>
<!DOCTYPE html>
<html>
<head>
    <?= $seo->generateTags() ?>
</head>
<body>
    <?php require $themeDir . '/partials/header.php'; ?>
    
    <main>
        <?= $content ?> <!-- O conteúdo do template entra aqui -->
    </main>

    <?php require $themeDir . '/partials/footer.php'; ?>
</body>
</html>
```

Seguindo este padrão, garantimos a Arquitetura SOLID e um carregamento em milissegundos para os visitantes!
