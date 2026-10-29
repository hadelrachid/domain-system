<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use RuntimeException;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * AssetBundle — empacota os módulos front-end do Builder Flex
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsabilidade ÚNICA: ler o manifesto (assets/manifest.php) e concatenar,
 * na ordem declarada, os arquivos JS ou CSS de cada módulo com cache por hash.
 */
final class AssetBundle
{
    /** @var array<string, string> */
    private array $memoryCache = [];

    public function __construct(private string $assetsDir)
    {
    }

    /**
     * @param string $type 'js' ou 'css'
     * @return string|null Conteúdo concatenado, ou null se o tipo não existir no manifesto.
     */
    public function build(string $type): ?string
    {
        $manifestFile = $this->assetsDir . '/manifest.php';
        if (!is_file($manifestFile)) {
            return null;
        }

        $manifest = require $manifestFile;

        if (!isset($manifest[$type]) || !is_array($manifest[$type])) {
            return null;
        }

        $mtimes = [];
        $filesToRead = [];
        foreach ($manifest[$type] as $relativePath) {
            $file = $this->assetsDir . '/' . $type . '/' . $relativePath;
            if (!is_file($file)) {
                throw new RuntimeException("Módulo do Builder não encontrado: {$type}/{$relativePath}");
            }
            $mtimes[] = $relativePath . ':' . (string)@filemtime($file);
            $filesToRead[$relativePath] = $file;
        }

        $cacheKey = $type . '_' . md5(implode('|', $mtimes));
        if (isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }

        $output = '';
        foreach ($filesToRead as $relativePath => $file) {
            $output .= "/* ── {$relativePath} ── */\n" . file_get_contents($file) . "\n\n";
        }

        $this->memoryCache[$cacheKey] = $output;

        return $output;
    }
}
