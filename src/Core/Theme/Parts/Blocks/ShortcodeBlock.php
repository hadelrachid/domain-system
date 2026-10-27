<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\ShortcodeManager;

/**
 * Bloco de Shortcode do Sistema (Execução Dinâmica com 1-Clique).
 */
class ShortcodeBlock implements BlockInterface
{
    use AllowedPartTypesTrait;
    private ?ShortcodeManager $shortcodeManager;
    /** @var (\Closure(): ?ShortcodeManager)|null */
    private ?\Closure $shortcodeResolver;

    /**
     * @param ShortcodeManager|null $shortcodeManager
     * @param (\Closure(): ?ShortcodeManager)|null $shortcodeResolver
     */
    public function __construct(?ShortcodeManager $shortcodeManager = null, ?\Closure $shortcodeResolver = null)
    {
        $this->shortcodeManager = $shortcodeManager;
        $this->shortcodeResolver = $shortcodeResolver;
    }

    public function getType(): string
    {
        return 'shortcode';
    }

    public function getName(): string
    {
        return 'Shortcode Dinâmico';
    }

    public function getIcon(): string
    {
        return '⚡';
    }

    public function getCategory(): string
    {
        return 'system';
    }

    public function render(array $data = [], array $context = []): string
    {
        $code = trim((string)($data['code'] ?? $data['tag'] ?? ''));
        if ($code === '') {
            return '';
        }

        $sm = $this->shortcodeManager;
        if ($sm === null && $this->shortcodeResolver !== null) {
            try {
                $sm = ($this->shortcodeResolver)();
            } catch (\Throwable $e) {
                $sm = null;
            }
        }

        if ($sm !== null) {
            return $sm->parse($code);
        }

        return $code;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'shortcode',
            'fields' => [
                'code' => ['type' => 'text', 'label' => 'Código do Shortcode (ex: [hub_de_cursos])', 'default' => '[hub_de_cursos]'],
            ],
        ];
    }
}
