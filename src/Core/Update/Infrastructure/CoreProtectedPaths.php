<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\Update\ProtectedPathsInterface;
use DomainSystem\Core\Update\Enum\UpdateKind;

/**
 * Política local de caminhos protegidos e raízes permitidas para atualização do núcleo e de plugins.
 *
 * Esta política reside no sistema instalado e jamais pode ser alterada pelo pacote recebido.
 */
final class CoreProtectedPaths implements ProtectedPathsInterface
{
    /**
     * @return list<string>
     */
    public function protectedPaths(UpdateKind $kind, string $slug): array
    {
        return [
            '.env',
            '.maintenance',
            'config/',
            'database.sqlite',
            'storage/',
            'temp/',
            'uploads/',
            'public/uploads/',
            'token/',
            'scratch/',
            'auditoria/',
            'src/Plugins/flextheme/themes/rachidd',
            'src/Plugins/flextheme/themes/rachidd/',
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedRoots(UpdateKind $kind, string $slug): array
    {
        if ($kind === UpdateKind::Plugin) {
            $cleanSlug = trim($slug, '/\\');
            return [
                'src/Plugins/' . $cleanSlug . '/',
            ];
        }

        return [
            'src/',
            'public/',
            'themes/',
            'vendor/',
            'bin/',
            'tools/',
            'release/',
            'bootstrap.php',
            'os-cli.php',
            'composer.json',
            '.htaccess',
            'LICENSE',
        ];
    }

    /**
     * Verifica se um caminho relativo normalizado está dentro das raízes permitidas.
     * Arquivos em `config/` só são aceitos quando o modo de entrada no manifest é `default_if_missing`.
     */
    public function isAllowedPath(UpdateKind $kind, string $slug, string $relativePath, string $mode = 'replace'): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '') {
            return false;
        }

        if ($kind === UpdateKind::Core && $mode === 'default_if_missing' && str_starts_with($normalized, 'config/')) {
            return true;
        }

        foreach ($this->allowedRoots($kind, $slug) as $root) {
            $normRoot = str_replace('\\', '/', $root);
            if (str_ends_with($normRoot, '/')) {
                if (str_starts_with($normalized, $normRoot) && strlen($normalized) > strlen($normRoot)) {
                    return true;
                }
            } else {
                if ($normalized === $normRoot) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifica se um caminho relativo normalizado está na lista de caminhos protegidos.
     */
    public function isProtectedPath(UpdateKind $kind, string $slug, string $relativePath): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '') {
            return true;
        }

        foreach ($this->protectedPaths($kind, $slug) as $protected) {
            $normProt = str_replace('\\', '/', $protected);
            if (str_ends_with($normProt, '/')) {
                $prefix = rtrim($normProt, '/');
                if ($normalized === $prefix || str_starts_with($normalized, $normProt)) {
                    return true;
                }
            } else {
                if ($normalized === $normProt || str_starts_with($normalized, $normProt . '/')) {
                    return true;
                }
            }
        }

        return false;
    }
}
