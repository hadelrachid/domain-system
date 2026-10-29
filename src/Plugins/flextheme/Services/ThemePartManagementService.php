<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartValidatorInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Core\Theme\Parts\Validation\ThemePartValidator;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use RuntimeException;
use Throwable;

/**
 * Serviço de aplicação responsável pela criação, validação estrutural, persistência verificada
 * e atribuição consistente (com compensação em caso de falha) de partes visuais de tema (SOLID - SRP / DIP).
 */
class ThemePartManagementService
{
    private ThemePartRepositoryInterface $partRepo;
    private ThemePartAssociationInterface $partAssoc;
    private ThemeDiscoveryServiceInterface $themeDiscovery;
    private ThemePartValidatorInterface $validator;

    public function __construct(
        ThemePartRepositoryInterface $partRepo,
        ThemePartAssociationInterface $partAssoc,
        ThemeDiscoveryServiceInterface $themeDiscovery,
        ?ThemePartValidatorInterface $validator = null,
        ?BlockRegistryInterface $blockRegistry = null,
        ?LayoutGridInterface $layoutGrid = null
    ) {
        $this->partRepo = $partRepo;
        $this->partAssoc = $partAssoc;
        $this->themeDiscovery = $themeDiscovery;
        $this->validator = $validator ?? new ThemePartValidator($blockRegistry, $layoutGrid);
    }

    /**
     * Constrói uma nova ThemePart com o preset e blocos iniciais recomendados para o tipo.
     */
    public function createDefaultPart(string $requestedType): ThemePartInterface
    {
        $type = in_array($requestedType, [
            ThemePartInterface::TYPE_HEADER,
            ThemePartInterface::TYPE_FOOTER,
            ThemePartInterface::TYPE_SECTION,
        ], true) ? $requestedType : ThemePartInterface::TYPE_HEADER;

        $defaultPreset = match ($type) {
            ThemePartInterface::TYPE_HEADER  => '3_cols_header',
            ThemePartInterface::TYPE_FOOTER  => '4_cols_footer',
            ThemePartInterface::TYPE_SECTION => '1_col',
            default => '1_col',
        };

        $defaultSettings = [
            'is_full_width' => false,
            'max_width'     => '1280px',
            'gap'           => '24px',
            'padding'       => '20px 24px',
            'align_items'   => 'center',
            'background'    => '',
        ];

        $initialColumns = match ($defaultPreset) {
            '3_cols_header' => [
                0 => [['type' => 'logo', 'data' => ['text' => 'Domain<span style="color:var(--color-primary);">System</span>', 'height' => '32px']]],
                1 => [['type' => 'nav_menu', 'data' => ['align' => 'center']]],
                2 => [['type' => 'cta_button', 'data' => ['label' => 'Acessar', 'url' => '/login', 'variant' => 'primary', 'align' => 'right']]],
            ],
            '4_cols_footer' => [
                0 => [['type' => 'logo', 'data' => ['text' => 'DomainSystem', 'height' => '28px']], ['type' => 'paragraph', 'data' => ['text' => 'Ecossistema modular de alto desempenho.', 'font_size' => '0.85rem']]],
                1 => [['type' => 'heading', 'data' => ['text' => 'Navegação', 'level' => 'h4', 'font_size' => '1rem']], ['type' => 'paragraph', 'data' => ['text' => '<a href="/" style="color:inherit;">Início</a><br><a href="/cursos" style="color:inherit;">Cursos</a>', 'font_size' => '0.85rem']]],
                2 => [['type' => 'heading', 'data' => ['text' => 'Comunidade', 'level' => 'h4', 'font_size' => '1rem']], ['type' => 'social_icons', 'data' => []]],
                3 => [['type' => 'heading', 'data' => ['text' => 'Legal', 'level' => 'h4', 'font_size' => '1rem']], ['type' => 'copyright', 'data' => ['site_name' => 'Domain-System']]],
            ],
            default => [
                0 => [['type' => 'heading', 'data' => ['text' => 'Título da Seção', 'level' => 'h2']], ['type' => 'paragraph', 'data' => ['text' => 'Insira seu texto e blocos aqui.']]],
            ]
        };

        return new ThemePart(
            uniqid('part_', true),
            $type,
            'Novo ' . ucfirst($type),
            $defaultPreset,
            $defaultSettings,
            $initialColumns,
            MobileToggleStyleInterface::STYLE_CLASSIC,
            false
        );
    }

    /**
     * Valida estruturalmente o documento, salva a ThemePart e aplica atribuições verificadas
     * com compensação automática caso uma atribuição posterior falhe.
     *
     * @param array<string, mixed> $input
     */
    public function saveFromPayload(array $input): ThemePartInterface
    {
        // 1. Validação estrutural completa antes de qualquer efeito colateral
        $this->validator->validate($input);

        $id = trim((string)($input['id'] ?? ''));
        if ($id === '') {
            $id = uniqid('part_', true);
        }

        $type = (string)($input['type'] ?? ThemePartInterface::TYPE_HEADER);
        $title = trim((string)($input['title'] ?? 'Sem Título'));
        $preset = (string)($input['layout_preset'] ?? '1_col');
        $settings = (array)($input['container_settings'] ?? []);
        unset($settings['hide_desktop'], $settings['hide_laptop'], $settings['hide_tablet'], $settings['hide_mobile']);
        $columnsData = (array)($input['columns_data'] ?? []);
        $mobileToggleStyle = (string)($input['mobile_toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC);
        $mobileToggleAlign = (string)($input['mobile_toggle_align'] ?? 'right');
        $isDefault = !empty($input['is_default']);
        $sections = (array)($input['sections'] ?? []);

        foreach ($sections as &$sec) {
            if (is_array($sec)) {
                if (isset($sec['container_settings']) && is_array($sec['container_settings'])) {
                    unset(
                        $sec['container_settings']['hide_desktop'],
                        $sec['container_settings']['hide_laptop'],
                        $sec['container_settings']['hide_tablet'],
                        $sec['container_settings']['hide_mobile']
                    );
                }
                if (isset($sec['settings']) && is_array($sec['settings'])) {
                    unset(
                        $sec['settings']['hide_desktop'],
                        $sec['settings']['hide_laptop'],
                        $sec['settings']['hide_tablet'],
                        $sec['settings']['hide_mobile']
                    );
                }
            }
        }
        unset($sec);

        // Captura snapshot anterior da parte e associações para compensação caso uma associação falhe após o save
        $previousPart = $this->partRepo->findById($id);
        $previousGlobalDefault = $this->partAssoc->getGlobalDefault($type);
        $assignedTheme = !empty($input['assigned_theme']) ? (string)$input['assigned_theme'] : '';
        $previousThemeAssignedId = $assignedTheme !== '' ? $this->partAssoc->getAssignedPartId($assignedTheme, $type) : null;
        $targetTheme = $assignedTheme !== ''
            ? $assignedTheme
            : $this->themeDiscovery->getActiveThemeFolder();
        $previousSectionTemplate = ($type === ThemePartInterface::TYPE_SECTION && isset($input['assigned_template']))
            ? $this->partAssoc->getAssignedTemplateForSection($targetTheme, $id)
            : null;

        $part = new ThemePart(
            $id,
            $type,
            $title !== '' ? $title : 'Sem Título',
            $preset,
            $settings,
            $columnsData,
            $mobileToggleStyle,
            $isDefault,
            isset($input['created_at']) ? (int)$input['created_at'] : ($previousPart?->getCreatedAt()),
            time(),
            $sections,
            $mobileToggleAlign
        );

        $saved = $this->partRepo->save($part);
        if (!$saved) {
            throw new RuntimeException('Falha ao gravar os dados da parte visual no armazenamento.');
        }

        try {
            if ($isDefault) {
                if (!$this->partAssoc->setGlobalDefault($type, $id)) {
                    throw new RuntimeException('Falha ao definir a parte visual como padrão global.');
                }
            }

            if ($assignedTheme !== '') {
                if (!$this->partAssoc->assignPart($assignedTheme, $type, $id)) {
                    throw new RuntimeException("Falha ao associar a parte visual ao tema '{$assignedTheme}'.");
                }
            }

            if ($type === ThemePartInterface::TYPE_SECTION && isset($input['assigned_template'])) {
                $templateChoice = trim((string)$input['assigned_template']);
                $effectiveTemplate = ($templateChoice !== '' && $templateChoice !== 'none') ? $templateChoice : null;
                if (!$this->partAssoc->assignTemplateToSection($targetTheme, $id, $effectiveTemplate)) {
                    throw new RuntimeException("Falha ao associar a seção ao template '{$templateChoice}'.");
                }
            }
        } catch (Throwable $e) {
            // Compensação: restaura integralmente o estado anterior da parte e de todas as associações
            if ($isDefault) {
                $this->partAssoc->setGlobalDefault($type, $previousGlobalDefault);
            }
            if ($assignedTheme !== '') {
                $this->partAssoc->assignPart($assignedTheme, $type, $previousThemeAssignedId);
            }
            if ($type === ThemePartInterface::TYPE_SECTION && isset($input['assigned_template'])) {
                $this->partAssoc->assignTemplateToSection($targetTheme, $id, $previousSectionTemplate);
            }
            if ($previousPart !== null) {
                $this->partRepo->save($previousPart);
            } else {
                $this->partRepo->delete($id);
            }

            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('Falha ao atualizar as associações da parte visual: ' . $e->getMessage(), 0, $e);
        }

        return $part;
    }

    /**
     * Processa uma solicitação de atribuição (tema <-> parte, global ou seção <-> template).
     *
     * @param array<string, mixed> $input
     */
    public function assignFromPayload(array $input): bool
    {
        $themeFolder = trim((string)($input['theme_folder'] ?? ''));
        $partType    = trim((string)($input['part_type'] ?? ''));
        $partId      = trim((string)($input['part_id'] ?? ''));
        $sectionId   = trim((string)($input['section_id'] ?? $partId));
        $isGlobal    = !empty($input['is_global']);

        if ($partType === '') {
            return false;
        }

        $resolvedPartId = ($partId !== '' && $partId !== 'none' && $partId !== 'fallback') ? $partId : null;

        if (str_starts_with($partType, 'template:')) {
            $slug = substr($partType, strlen('template:'));
            $targetSectionId = $resolvedPartId ?? $sectionId;
            if ($targetSectionId === '' || $targetSectionId === 'none' || $themeFolder === '') {
                return false;
            }
            $effectiveSlug = ($partType !== 'template:none' && $resolvedPartId !== null) ? $slug : null;
            return $this->partAssoc->assignTemplateToSection($themeFolder, $targetSectionId, $effectiveSlug);
        }

        if ($isGlobal) {
            return $this->partAssoc->setGlobalDefault($partType, $resolvedPartId);
        }

        if ($themeFolder === '') {
            return false;
        }

        return $this->partAssoc->assignPart($themeFolder, $partType, $resolvedPartId);
    }

    /**
     * Exclui uma parte visual e limpa quaisquer referências órfãs nas associações de temas/templates/globais.
     */
    public function deletePart(string $id): bool
    {
        $cleanId = trim($id);
        if ($cleanId === '') {
            return false;
        }

        $deleted = $this->partRepo->delete($cleanId);
        if ($deleted) {
            $cleaned = $this->partAssoc->removePartReferences($cleanId);
            if (!$cleaned) {
                error_log("[ThemePartManagementService] Aviso: referências de '{$cleanId}' podem não ter sido limpas integralmente.");
            }
        }

        return $deleted;
    }
}
