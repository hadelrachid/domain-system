<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme;

use DomainSystem\Core\Contracts\ThemeActivatorInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use Throwable;

class ThemeActivator implements ThemeActivatorInterface
{
    private string $basePath;
    private SafeThemeResolver $resolver;
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(string $basePath, ?SafeThemeResolver $resolver = null, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->resolver = $resolver ?? new SafeThemeResolver([
            $this->basePath . '/themes',
            $this->basePath . '/src/Plugins/flextheme/themes'
        ]);
        $this->dispatcher = $dispatcher;
    }

    public function getResolver(): SafeThemeResolver
    {
        return $this->resolver;
    }

    public function getActiveTheme(): string
    {
        $configFile = $this->basePath . '/config/active_theme.json';
        if (file_exists($configFile)) {
            $data = json_decode((string)file_get_contents($configFile), true);
            if (!empty($data['active_theme'])) {
                return (string)$data['active_theme'];
            }
        }

        return getenv('ACTIVE_THEME') ?: ($_ENV['ACTIVE_THEME'] ?? 'default');
    }

    public function activate(string $themeFolder): array
    {
        $sanitized = basename(trim($themeFolder));

        if (empty($sanitized)) {
            return [
                'success' => false,
                'message' => 'Nome do tema não informado.'
            ];
        }

        if (!$this->resolver->themeExists($sanitized)) {
            return [
                'success' => false,
                'message' => "O tema '{$sanitized}' não foi encontrado no servidor."
            ];
        }

        $configDir = $this->basePath . '/config';
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0755, true);
        }

        $payload = [
            'active_theme' => $sanitized,
            'status' => 'live',
            'activated_at' => date('Y-m-d H:i:s')
        ];

        file_put_contents(
            $configDir . '/active_theme.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Atualiza variáveis em memória
        putenv("ACTIVE_THEME={$sanitized}");
        $_ENV['ACTIVE_THEME'] = $sanitized;
        putenv("SITE_STATUS=live");
        $_ENV['SITE_STATUS'] = 'live';

        // Atualiza .env se existir e for gravável
        $this->updateEnvFile($sanitized);

        if ($this->dispatcher !== null) {
            $this->dispatcher->dispatch('theme.activated', $sanitized);
        }

        return [
            'success' => true,
            'message' => "Tema '{$sanitized}' ativado com sucesso!",
            'theme' => $sanitized
        ];
    }

    private function updateEnvFile(string $theme): void
    {
        $envPath = $this->basePath . '/.env';
        if (!file_exists($envPath) || !is_writable($envPath)) {
            return;
        }

        try {
            $content = (string)file_get_contents($envPath);

            if (preg_match('/^ACTIVE_THEME=.*$/m', $content)) {
                $content = preg_replace('/^ACTIVE_THEME=.*$/m', "ACTIVE_THEME=\"{$theme}\"", $content);
            } else {
                $content .= "\nACTIVE_THEME=\"{$theme}\"";
            }

            if (preg_match('/^SITE_STATUS=.*$/m', $content)) {
                $content = preg_replace('/^SITE_STATUS=.*$/m', "SITE_STATUS=\"live\"", $content);
            } else {
                $content .= "\nSITE_STATUS=\"live\"";
            }

            file_put_contents($envPath, $content);
        } catch (Throwable $e) {
            // Não aborta a requisição caso o .env não possa ser modificado
        }
    }
}
