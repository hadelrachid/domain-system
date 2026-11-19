<?php

declare(strict_types=1);

if (!empty($title)) {
    $seo->setTitle($title);
} elseif ($seo->getTitle() === 'Domain System OS') {
    $seo->setTitle('RachidD.com | O Gênio das Ofertas Internacionais & Dropship')
        ->setDescription('Descubra as melhores ofertas mundiais do AliExpress no RachidD.com. Preços de fábrica, frete com rastreamento internacional e pagamento protegido com Pix, Mercado Pago e PayPal.');
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalUrl = 'https://rachidd.com' . ($requestPath === '/' ? '' : $requestPath);
if (!empty($_GET['cat'])) {
    $canonicalUrl .= '?cat=' . urlencode((string)$_GET['cat']);
}

$seo->setCanonical($canonicalUrl)
    ->setFavicon((defined('BASE_URL') ? BASE_URL : '') . '/assets/img/site-home/logo-lampada.svg')
    ->addMeta('theme-color', '#06090e');

// Registra os arquivos CSS e JS do tema no AssetPipeline do DomainSEO
$themeDir = dirname(__DIR__);
$seo->enqueueCss($themeDir . '/assets/css/hero-stars.css')
    ->enqueueCss($themeDir . '/assets/css/theme-rachidd.css')
    ->enqueueJs($themeDir . '/assets/js/theme-rachidd.js');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->generateTags() ?>
    
    <!-- Modern Fonts com Preconnect Otimizado (Google PageSpeed / Core Web Vitals) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Otimizado e Minificado pelo DomainSEO AssetPipeline -->
    <?= $seo->renderCssTags() ?>
</head>
<body>

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <!-- JavaScript Otimizado com carregamento não-bloqueante (defer) -->
    <?= $seo->renderJsTags() ?>

</body>
</html>
