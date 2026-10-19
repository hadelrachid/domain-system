<?php

declare(strict_types=1);

/**
 * Script CLI oficial para geração do pacote de atualização do Domain System (Modelo A).
 *
 * Uso:
 *   php tools/build-release.php
 */

$rootDir = dirname(__DIR__);
$autoload = $rootDir . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, "Erro: vendor/autoload.php não encontrado. Execute composer install antes de gerar a Release.\n");
    exit(1);
}

require_once $autoload;
require_once __DIR__ . '/release/ReleaseBuilder.php';

use DomainSystem\Tools\Release\ReleaseBuilder;

try {
    $builder = new ReleaseBuilder($rootDir);
    $result = $builder->build();

    echo sprintf("✅ Pacote de Release v%s gerado com sucesso!\n", $result['version']);
    echo sprintf("   Arquivo ZIP : %s\n", $result['zipPath']);
    echo sprintf("   Arquivo Hash: %s\n", $result['sha256Path']);
    echo sprintf("   SHA-256     : %s\n", $result['sha256']);
    echo sprintf("   Arquivos    : %d\n", $result['fileCount']);
    echo sprintf("   Migrations  : %d\n", $result['migrationCount']);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "❌ Falha ao gerar Release: " . $e->getMessage() . "\n");
    exit(1);
}
