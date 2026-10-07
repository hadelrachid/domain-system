<?php

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Core;

use RuntimeException;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * AssetBundle — empacota os módulos front-end do Builder Flex
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Responsabilidade ÚNICA: ler o manifesto (assets/manifest.php) e concatenar,
 * na ordem declarada, os arquivos JS ou CSS de cada módulo.
 *
 * - A pasta src/ não é pública, então o Controller entrega o bundle por rota.
 * - Aberto/Fechado: para adicionar um módulo basta uma linha no manifesto.
 * - Seguro: só arquivos listados no manifesto são lidos (sem path traversal).
 */
final class AssetBundle
{
    public function __construct(private string $assetsDir)
    {
    }

    /**
     * @param string $type 'js' ou 'css'
     * @return string|null Conteúdo concatenado, ou null se o tipo não existir no manifesto.
     */
    public function build(string $type): ?string
    {
        $manifest = require $this->assetsDir . '/manifest.php';

        if (!isset($manifest[$type]) || !is_array($manifest[$type])) {
            return null;
        }

        $output = '';
        foreach ($manifest[$type] as $relativePath) {
            $file = $this->assetsDir . '/' . $type . '/' . $relativePath;

            if (!is_file($file)) {
                throw new RuntimeException("Módulo do Builder não encontrado: {$type}/{$relativePath}");
            }

            $output .= "/* ── {$relativePath} ── */\n" . file_get_contents($file) . "\n\n";
        }

        return $output;
    }
}
