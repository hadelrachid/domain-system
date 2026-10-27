<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartAssociationInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartRepositoryInterface;
use DomainSystem\Core\Theme\Parts\Models\ThemePart;
use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;
use RuntimeException;

/**
 * Serviço de aplicação responsável pela criação, persistência verificada e atribuição
 * de partes visuais de tema e templates (SOLID - SRP / DIP).
 */
class ThemePartManagementService
{
    private ThemePartRepositoryInterface $partRepo;
    private ThemePartAssociationInterface $partAssoc;
    private ThemeDiscoveryServiceInterface $themeDiscovery;

    public function __construct(
        ThemePartRepositoryInterface $partRepo,
        ThemePartAssociationInterface $partAssoc,
        ThemeDiscoveryServiceInterface $themeDiscovery
    ) {
        $this->partRepo = $partRepo;
        $this->partAssoc = $partAssoc;
        $this->themeDiscovery = $themeDiscovery;
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
     * Salva uma ThemePart verificando obrigatoriamente o sucesso da operação de escrita e aplicando atribuições.
     *
     * @param array<string, mixed> $input
     */
    public function saveFromPayload(array $input): ThemePartInterface
    {
        $id = trim((string)($input['id'] ?? ''));
        if ($id === '') {
            $id = uniqid('part_', true);
        }

        $type = (string)($input['type'] ?? ThemePartInterface::TYPE_HEADER);
        $title = trim((string)($input['title'] ?? 'Sem Título'));
        $preset = (string)($input['layout_preset'] ?? '1_col');
        $settings = (array)($input['container_settings'] ?? []);
        $columnsData = (array)($input['columns_data'] ?? []);
        $mobileToggleStyle = (string)($input['mobile_toggle_style'] ?? MobileToggleStyleInterface::STYLE_CLASSIC);
        $mobileToggleAlign = (string)($input['mobile_toggle_align'] ?? 'right');
        $isDefault = !empty($input['is_default']);
        $sections = (array)($input['sections'] ?? []);

        $part = new ThemePart(
            $id,
            $type,
            $title !== '' ? $title : 'Sem Título',
            $preset,
            $settings,
            $columnsData,
            $mobileToggleStyle,
            $isDefault,
            isset($input['created_at']) ? (int)$input['created_at'] : null,
            time(),
            $sections,
            $mobileToggleAlign
        );

        $saved = $this->partRepo->save($part);
        if (!$saved) {
            throw new RuntimeException('Falha ao gravar os dados da parte visual no armazenamento.');
        }

        if ($isDefault) {
            $this->partAssoc->setGlobalDefault($type, $id);
        }

        if (!empty($input['assigned_theme'])) {
            $this->partAssoc->assignPart((string)$input['assigned_theme'], $type, $id);
        }

        if ($type === ThemePartInterface::TYPE_SECTION && isset($input['assigned_template'])) {
            $targetTheme = !empty($input['assigned_theme'])
                ? (string)$input['assigned_theme']
                : $this->themeDiscovery->getActiveThemeFolder();
            $templateChoice = trim((string)$input['assigned_template']);
            $this->partAssoc->assignTemplateToSection($targetTheme, $id, $templateChoice);
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

        $resolvedPartId = ($partId !== '' && $partId !== 'none' && $partId !== 'fallback') ? $partId : null;

        if (str_starts_with($partType, 'template:')) {
            $slug = substr($partType, strlen('template:'));
            $targetSectionId = $resolvedPartId ?? $sectionId;
            if ($targetSectionId === '' || $targetSectionId === 'none') {
                return false;
            }
            $effectiveSlug = ($partType !== 'template:none' && $resolvedPartId !== null) ? $slug : null;
            return $this->partAssoc->assignTemplateToSection($themeFolder, $targetSectionId, $effectiveSlug);
        }

        if ($isGlobal) {
            return $this->partAssoc->setGlobalDefault($partType, $resolvedPartId);
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
            $this->partAssoc->removePartReferences($cleanId);
        }

        return $deleted;
    }
}
