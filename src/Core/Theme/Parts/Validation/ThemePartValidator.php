<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Validation;

use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Core\Theme\Parts\Contracts\LayoutGridInterface;
use DomainSystem\Core\Theme\Parts\Contracts\MobileToggleStyleInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ThemePartValidatorInterface;
use InvalidArgumentException;

/**
 * Validador estrutural de partes visuais do Flex Builder.
 *
 * Garante que tipo, presets, seções, colunas, blocos, compatibilidade por área
 * e limites de profundidade/tamanho sejam estritamente verificados antes de qualquer gravação.
 */
final class ThemePartValidator implements ThemePartValidatorInterface
{
    public const MAX_TITLE_LENGTH = 255;
    public const MAX_ID_LENGTH = 128;
    public const MAX_SECTIONS = 50;
    public const MAX_COLUMNS_PER_SECTION = 12;
    public const MAX_BLOCKS_PER_COLUMN = 100;
    public const MAX_DATA_DEPTH = 10;

    private ?BlockRegistryInterface $blockRegistry;
    private ?LayoutGridInterface $layoutGrid;

    public function __construct(
        ?BlockRegistryInterface $blockRegistry = null,
        ?LayoutGridInterface $layoutGrid = null
    ) {
        $this->blockRegistry = $blockRegistry;
        $this->layoutGrid = $layoutGrid;
    }

    public function validate(array $payload): void
    {
        // 1. Validação do ID (quando informado)
        if (isset($payload['id']) && $payload['id'] !== '') {
            if (!is_string($payload['id']) && !is_numeric($payload['id'])) {
                throw new InvalidArgumentException('O identificador da parte visual (id) deve ser uma string válida.');
            }
            $id = trim((string)$payload['id']);
            if ($id !== '') {
                if (strlen($id) > self::MAX_ID_LENGTH || !preg_match('/^[a-zA-Z0-9_.\-]+$/', $id)) {
                    throw new InvalidArgumentException('Identificador da parte visual (id) contém caracteres inválidos ou excede o tamanho máximo.');
                }
            }
        }

        // 2. Validação estrita do tipo da parte
        $type = (string)($payload['type'] ?? ThemePartInterface::TYPE_HEADER);
        $validTypes = [
            ThemePartInterface::TYPE_HEADER,
            ThemePartInterface::TYPE_FOOTER,
            ThemePartInterface::TYPE_SECTION,
        ];
        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Tipo de parte visual inválido: '{$type}'. Valores permitidos: header, footer, section.");
        }

        // 3. Validação do título
        if (isset($payload['title'])) {
            if (!is_scalar($payload['title'])) {
                throw new InvalidArgumentException('O título da parte visual deve ser um texto válido.');
            }
            $title = trim((string)$payload['title']);
            if (mb_strlen($title, 'UTF-8') > self::MAX_TITLE_LENGTH) {
                throw new InvalidArgumentException('O título da parte visual excede o limite de ' . self::MAX_TITLE_LENGTH . ' caracteres.');
            }
        }

        // 4. Validação do estilo e alinhamento do toggle mobile
        if (isset($payload['mobile_toggle_style']) && $payload['mobile_toggle_style'] !== '') {
            $toggleStyle = (string)$payload['mobile_toggle_style'];
            $validToggleStyles = [
                MobileToggleStyleInterface::STYLE_CLASSIC,
                MobileToggleStyleInterface::STYLE_PILL,
                MobileToggleStyleInterface::STYLE_MINIMAL,
                MobileToggleStyleInterface::STYLE_DOTS,
            ];
            if (!in_array($toggleStyle, $validToggleStyles, true)) {
                throw new InvalidArgumentException("Estilo de toggle mobile inválido: '{$toggleStyle}'.");
            }
        }

        if (isset($payload['mobile_toggle_align']) && $payload['mobile_toggle_align'] !== '') {
            $toggleAlign = (string)$payload['mobile_toggle_align'];
            if (!in_array($toggleAlign, ['left', 'center', 'right'], true)) {
                throw new InvalidArgumentException("Alinhamento de toggle mobile inválido: '{$toggleAlign}'.");
            }
        }

        // 5. Validação do preset principal
        $rootPreset = (string)($payload['layout_preset'] ?? '1_col');
        $this->validatePreset($rootPreset);

        // 6. Validação de container_settings e columns_data raiz
        if (isset($payload['container_settings']) && !is_array($payload['container_settings'])) {
            throw new InvalidArgumentException('O campo container_settings deve ser um array.');
        }

        if (isset($payload['columns_data'])) {
            if (!is_array($payload['columns_data'])) {
                throw new InvalidArgumentException('O campo columns_data deve ser um array.');
            }
            $this->validateColumnsData($payload['columns_data'], $type);
        }

        // 7. Validação das seções (sections)
        if (isset($payload['sections'])) {
            if (!is_array($payload['sections'])) {
                throw new InvalidArgumentException('O campo sections deve ser uma lista de seções.');
            }
            if (count($payload['sections']) > self::MAX_SECTIONS) {
                throw new InvalidArgumentException('O documento excede o limite máximo de ' . self::MAX_SECTIONS . ' seções.');
            }

            foreach ($payload['sections'] as $secIndex => $section) {
                if (!is_array($section)) {
                    throw new InvalidArgumentException("A seção na posição {$secIndex} possui formato inválido.");
                }

                $secPreset = (string)($section['layout_preset'] ?? '1_col');
                $this->validatePreset($secPreset);

                if (isset($section['container_settings']) && !is_array($section['container_settings'])) {
                    throw new InvalidArgumentException("O campo container_settings da seção {$secIndex} deve ser um array.");
                }

                if (isset($section['columns_data'])) {
                    if (!is_array($section['columns_data'])) {
                        throw new InvalidArgumentException("O campo columns_data da seção {$secIndex} deve ser um array.");
                    }
                    $this->validateColumnsData($section['columns_data'], $type);
                }
            }
        }
    }

    private function validatePreset(string $preset): void
    {
        if (trim($preset) === '') {
            throw new InvalidArgumentException('O preset de layout não pode ser vazio.');
        }

        if ($this->layoutGrid !== null) {
            $presets = $this->layoutGrid->getPresets();
            if (!empty($presets) && !array_key_exists($preset, $presets)) {
                throw new InvalidArgumentException("Preset de layout inexistente ou não suportado: '{$preset}'.");
            }
        }
    }

    /**
     * @param array<int|string, mixed> $columnsData
     */
    private function validateColumnsData(array $columnsData, string $partType): void
    {
        if (count($columnsData) > self::MAX_COLUMNS_PER_SECTION) {
            throw new InvalidArgumentException('A seção excede o limite máximo de ' . self::MAX_COLUMNS_PER_SECTION . ' colunas.');
        }

        foreach ($columnsData as $colIndex => $blocksList) {
            if (!is_array($blocksList)) {
                throw new InvalidArgumentException("Os blocos da coluna {$colIndex} devem ser fornecidos em uma lista.");
            }
            if (count($blocksList) > self::MAX_BLOCKS_PER_COLUMN) {
                throw new InvalidArgumentException("A coluna {$colIndex} excede o limite máximo de " . self::MAX_BLOCKS_PER_COLUMN . " blocos.");
            }

            foreach ($blocksList as $blockIndex => $blockDef) {
                if (!is_array($blockDef)) {
                    throw new InvalidArgumentException("Definição de bloco inválida na coluna {$colIndex}, posição {$blockIndex}.");
                }

                $blockType = trim((string)($blockDef['type'] ?? ''));
                if ($blockType === '') {
                    throw new InvalidArgumentException("Bloco sem tipo ('type') na coluna {$colIndex}, posição {$blockIndex}.");
                }

                $blockData = $blockDef['data'] ?? [];
                if (!is_array($blockData)) {
                    throw new InvalidArgumentException("O campo 'data' do bloco '{$blockType}' deve ser um array.");
                }

                $this->assertArrayDepth($blockData, 1);

                if ($this->blockRegistry !== null) {
                    $block = $this->blockRegistry->get($blockType);
                    if ($block === null) {
                        throw new InvalidArgumentException("Bloco desconhecido ou não registrado: '{$blockType}'.");
                    }

                    $allowedPartTypes = $block->getAllowedPartTypes();
                    if (!empty($allowedPartTypes) && !in_array($partType, $allowedPartTypes, true)) {
                        throw new InvalidArgumentException(
                            "O bloco '{$blockType}' não é compatível com partes do tipo '{$partType}'."
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function assertArrayDepth(array $data, int $currentDepth): void
    {
        if ($currentDepth > self::MAX_DATA_DEPTH) {
            throw new InvalidArgumentException('A estrutura de dados do bloco excede a profundidade máxima permitida (' . self::MAX_DATA_DEPTH . ').');
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $this->assertArrayDepth($value, $currentDepth + 1);
            }
        }
    }
}
