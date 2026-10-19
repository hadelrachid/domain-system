<?php

declare(strict_types=1);

namespace DomainSystem\Core\Manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Adaptador que permite definir um módulo de manual de forma declarativa
 * retornando um array em `manual/module.php`.
 */
class ArrayManualModuleAdapter implements ManualModuleInterface
{
    /** @var array<string, mixed> */
    private array $config;
    private string $manualDir;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config, string $manualDir)
    {
        $this->config = $config;
        $this->manualDir = $manualDir;
    }

    public function getId(): string
    {
        return (string)($this->config['id'] ?? basename(dirname($this->manualDir)));
    }

    public function getTitle(): string
    {
        return (string)($this->config['title'] ?? ucfirst($this->getId()));
    }

    public function getSubtitle(): string
    {
        return (string)($this->config['subtitle'] ?? '');
    }

    public function getBadge(): string
    {
        return (string)($this->config['badge'] ?? 'Módulo de Plugin');
    }

    public function getAccentColor(): string
    {
        return (string)($this->config['accent_color'] ?? '#45f3ff');
    }

    public function getAdminUrl(): ?string
    {
        return isset($this->config['admin_url']) ? (string)$this->config['admin_url'] : null;
    }

    public function renderContent(string $baseUrl = ''): string
    {
        if (isset($this->config['render']) && is_callable($this->config['render'])) {
            return (string)($this->config['render'])($baseUrl);
        }

        $viewFile = $this->manualDir . DIRECTORY_SEPARATOR . (string)($this->config['view'] ?? 'content.php');
        $realView = realpath($viewFile);
        $realDir = realpath($this->manualDir);
        if ($realView !== false && $realDir !== false && str_starts_with($realView, $realDir) && is_file($realView)) {
            ob_start();
            include $realView;
            return (string)ob_get_clean();
        }

        return (string)($this->config['content'] ?? '');
    }

    public function getChecklistItems(): array
    {
        $items = $this->config['checklist'] ?? [];
        return is_array($items) ? $items : [];
    }
}
