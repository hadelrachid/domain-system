<?php

declare(strict_types=1);

$app = require_once dirname(__DIR__) . '/bootstrap.php';
$app->boot();

use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCatalogRepository;
use DomainSystem\Plugins\domain_drop\Services\AliExpressLinkScraperService;
use DomainSystem\Plugins\domain_drop\Services\CurrencyConverterService;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;

/**
 * Script CLI de Sincronização Periódica de Preços e Ofertas do Vendedor (Black Friday / Super Deals).
 * Pode ser executado manualmente ou agendado no Cron / Windows Task Scheduler a cada 2h ou 24h.
 * 
 * Uso: php bin/sync_prices.php
 */

echo "========================================================" . PHP_EOL;
echo "  RACHIDD.COM — SINCRONIZADOR DE PREÇOS DO ALIEXPRESS" . PHP_EOL;
echo "  Rastreamento de Ofertas do Vendedor & Promoções Ativas" . PHP_EOL;
echo "========================================================" . PHP_EOL;

try {
    $db = $app->getContainer()->make(DatabaseConnectionInterface::class)->getPdo();
} catch (\Throwable $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$repo = new PdoDropshipCatalogRepository($db);
$scraper = new AliExpressLinkScraperService();
$currency = new CurrencyConverterService();

$products = $repo->list([], 100);
echo "Total de produtos no catálogo para monitorar: " . count($products) . PHP_EOL . PHP_EOL;

$updatedCount = 0;
$rate = $currency->getExchangeRate('USD', 'BRL');
if ($rate <= 0.0) {
    $rate = 5.75;
}

foreach ($products as $prod) {
    $aeId = $prod->aliexpressId;
    echo "🔍 Verificando ID {$prod->id} [AE: {$aeId}] — " . mb_substr($prod->title, 0, 45) . "..." . PHP_EOL;

    $scraped = $scraper->scrape($aeId);
    if ($scraped === null) {
        echo "   ⚠️ Sem resposta ao vivo do vendedor no momento (mantendo valores atuais)." . PHP_EOL;
        continue;
    }

    $costBrl = (float)($scraped->attributes['cost_price_brl'] ?? 0.0);
    if ($costBrl <= 0.0) {
        $costBrl = round($scraped->costPriceUsd * $rate, 2);
    }

    // Regra oficial da loja: Duas vezes o valor líquido do produto no vendedor (100% markup)
    $newSalePriceBrl = round($costBrl * 2.0, 2);

    $attrs = $prod->attributes;
    $attrs['cost_price_brl'] = $costBrl;
    if (!isset($attrs['peso'])) {
        $attrs['peso'] = $prod->getFormattedWeight(1);
    }

    $stmt = $db->prepare("
        UPDATE domain_drop_catalog 
        SET cost_price_usd = ?, sale_price_brl = ?, attributes = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([
        $scraped->costPriceUsd,
        $newSalePriceBrl,
        json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $prod->id
    ]);

    echo "   ✅ Custo Fornecedor: R$ " . number_format($costBrl, 2, ',', '.') 
        . " ➔ Venda na Loja: R$ " . number_format($newSalePriceBrl, 2, ',', '.') 
        . " (2x o produto)" . PHP_EOL;

    $updatedCount++;
}

echo PHP_EOL . "========================================================" . PHP_EOL;
echo "🎉 Concluído com sucesso! {$updatedCount} produtos atualizados com as ofertas vigentes." . PHP_EOL;
echo "========================================================" . PHP_EOL;
