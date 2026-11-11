<?php

/**
 * CLI Runner para o Ciclo de Rotação Diária 24h do DomainDrop.
 * 
 * Uso:
 *   php tools/mine-daily-cron.php
 *   php tools/mine-daily-cron.php --force
 *   php tools/mine-daily-cron.php --items=5
 * 
 * Agendamento Crontab / Windows Task Scheduler:
 *   0 0 * * * php /caminho/tools/mine-daily-cron.php > /dev/null 2>&1
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Acesso proibido via web. Execute este script apenas via linha de comando (CLI).\n";
    exit(1);
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/vendor/autoload.php';

/** @var \DomainSystem\Core\Application $app */
$app = require_once $rootDir . '/bootstrap.php';
$app->boot();

$options = getopt('', ['force', 'items::', 'category::']);
$force = isset($options['force']);
$items = isset($options['items']) ? max(1, min(15, (int)$options['items'])) : 4;
$categoryFilter = $options['category'] ?? null;

echo "════════════════════════════════════════════════════════════════════\n";
echo "  DOMAINDROP • ROBÔ AUTÔNOMO DE ROTAÇÃO DIÁRIA 24H               \n";
echo "════════════════════════════════════════════════════════════════════\n";
echo " Horário: " . date('d/m/Y H:i:s') . "\n";
echo " Itens por Categoria: {$items}\n";
echo " Modo: " . ($force ? "FORÇADO (--force)" : "AUTOMÁTICO (Baseado em Tempo 24h)") . "\n";
echo "════════════════════════════════════════════════════════════════════\n\n";

try {
    /** @var \DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface $miningService */
    $miningService = $app->make(\DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface::class);

    $cycleInfo = $miningService->getDailyCycleInfo();

    if (!$force && !$cycleInfo['is_due']) {
        echo "[INFO] O ciclo diário de 24 horas ainda está vigente.\n";
        echo "       Tempo restante até o próximo ciclo: {$cycleInfo['formatted_remaining']}\n";
        echo "       Próxima rotação prevista em: {$cycleInfo['next_cycle_date']}\n";
        echo "       (Para forçar a execução imediata, use: php tools/mine-daily-cron.php --force)\n\n";
        exit(0);
    }

    echo "[+] Iniciando mineração inteligente em todas as 9 categorias oficiais...\n\n";
    $start = microtime(true);

    $result = $miningService->runDailyAutomatedRotation($items);
    $elapsed = round(microtime(true) - $start, 2);

    echo "[OK] Ciclo de Rotação 24h Finalizado em {$elapsed}s!\n";
    echo "     Total de Produtos Minerados/Atualizados: {$result['total_mined']}\n\n";

    echo " Detalhamento por Categoria:\n";
    foreach ($result['category_counts'] as $cat => $count) {
        echo "   • {$cat}: {$count} produtos sincronizados\n";
    }

    $updatedInfo = $miningService->getDailyCycleInfo();
    echo "\n Próximo ciclo agendado para: {$updatedInfo['next_cycle_date']}\n";
    echo "════════════════════════════════════════════════════════════════════\n";

    exit(0);
} catch (\Throwable $e) {
    echo "\n[ERRO CRÍTICO] Falha ao executar ciclo diário: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
