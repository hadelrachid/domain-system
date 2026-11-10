<?php

declare(strict_types=1);

if ($seo->getTitle() === 'Domain System OS') {
    $seo->setTitle('RachidD.com | O Gênio das Ofertas Internacionais & Dropship')
        ->setDescription('Descubra as melhores ofertas mundiais do AliExpress no RachidD.com. Preços de fábrica, frete com rastreamento internacional e pagamento protegido com Pix, Mercado Pago e PayPal.');
}

$seo->setCanonical('https://rachidd.com')
    ->setFavicon((defined('BASE_URL') ? BASE_URL : '') . '/assets/img/site-home/logo-lampada.svg')
    ->addMeta('theme-color', '#06090e');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->generateTags() ?>
    
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/assets/css/hero-stars.css">
    
    <style>
        :root {
            --bg-color: #080c14;
            --surface: rgba(18, 25, 38, 0.75);
            --primary: #F0D060;
            --primary-gold: #D4AF37;
            --primary-dark: #B8962E;
            --primary-glow: rgba(212, 175, 55, 0.4);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --color-border: rgba(212, 175, 55, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Subtle Luxury Dark Atmosphere */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(212, 175, 55, 0.08) 0%, transparent 60%),
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 50px 50px, 50px 50px;
            z-index: -2;
            pointer-events: none;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .text-gold {
            background: linear-gradient(135deg, #FFE082 0%, #D4AF37 50%, #B8962E 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gold-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #F0D060 0%, #D4AF37 100%);
            color: #0b0f17 !important;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 4px 16px rgba(212, 175, 55, 0.35);
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .gold-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(212, 175, 55, 0.55);
        }

        .store-card {
            background: #111722;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .store-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(0,0,0,0.6);
            border-color: rgba(212, 175, 55, 0.5);
        }
    </style>
</head>
<body>

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
