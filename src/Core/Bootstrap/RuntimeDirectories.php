<?php

declare(strict_types=1);

namespace DomainSystem\Core\Bootstrap;

/**
 * Classe responsável por garantir a infraestrutura física de pastas de runtime.
 * Previne falhas de escrita (E_WARNING) em ambientes limpos (como Hostinger).
 */
class RuntimeDirectories
{
    private static array $requiredDirs = [
        'temp',
        'temp/cache',
        'temp/logs',
        'storage',
        'storage/state',
        'public/uploads',
        'public/uploads/academy',
    ];

    /**
     * Garante a criação dos diretórios essenciais e proteção de acesso HTTP direto.
     */
    public static function ensure(string $basePath): void
    {
        $base = rtrim($basePath, '/\\');

        foreach (self::$requiredDirs as $dir) {
            $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }

            // Protege pastas internas (temp e storage) contra execução e download direto
            if (str_starts_with($dir, 'temp') || str_starts_with($dir, 'storage')) {
                $htaccess = $path . DIRECTORY_SEPARATOR . '.htaccess';
                if (!file_exists($htaccess)) {
                    $rule = "Deny from all\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
                    @file_put_contents($htaccess, $rule);
                }
            }
        }
    }
}
